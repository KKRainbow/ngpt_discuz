<?php

/*
 * 说明：
 * 1、seedid 用于下载页面，infohash 用于信息检索。infohash 所用字符串为全大写。
 * 2、个人静态系数表设计需要手工激活（创建）。
 * 3、种子文件表的几个字段（filename/filesize/filecount）是为了减少查询次数而缓存的。
 * 4、下载/浏览文件列表时请通过一个文件（如 getseedinfo.php）返回缓存在文件中的种子的 <info> 节信息。
 * 5、种子ID还是改为自增的，原因是多人同时提交时可能会产生冲突，而用事务不能完全解决，可能还需要手工fallback（如果用户来不及刷新页面，弹出的提示要说明“该种子已经被修改或删除”）。
 * 6、修改资源帖时，别忘了手工 UPDATE seed_op_records。
 * 7、注意判别管理者/哪些版主有权修改种子，在种子的editpost()上判断。
 * 8、系统定义的 extracredit2 为上传(MB)，extracredit3 为下载(MB)，总积分公式内置。
 * 9、下载时出现的 < 1 MB 现象如何处理？直接计入积分可能会引发积分暴涨（若两次 tracker 请求之间传输 < 1 MB 但还是计 1 MB 的话，理论上将 X GB 的种子拆分成小块，每次产生的 tracker 流量 < 1 MB，甚至手工伪造 tracker 请求，则会引发单次 1 KB 也按照单次 1 MB 计算的bug）。
 * 10、新手任务要能随时打开关闭及其他控制。
 * 11、积分使用的是 int(10)，问题是即使采用 MB 为单位，积分还是只能统计到 2 PB。IMAX 危险了，按照平均每天 2 TB 算，就2048天（5年）而已。
 * 12、文件分享区的种子计算下载不计算上传。
 * 13、共享率保护：每次浏览特殊帖子时要执行查询，查询用户此时共享率并显示页面元素提示。
 * 14、种子上传/下载比率计算：添加计划任务更新每个种子的上传下载。注意，默认的 CRON 时间可能较短（1s），需要手工延长，见 http://blog.163.com/tfz_0611_go/blog/static/20849708420132562619807/。
 * 15、每个种子的上传/下载系数新建了一个表。读了未来花园的代码之后，反正请求一次上传/下载系数组，附带就判断一次过期时间吧。同时，系数过期时间是版主编辑种子时指定的，系统依据当前时间自动计算（注意可以有“永久”选项）。
 * 16、在种子上传下载系数过期之后，预留自动调节逻辑，可能暂时不启用。花园的逻辑为蓝种过期系数30%，30%过期系数60%，无其余逻辑。
 * 17、passkey 和种子种的 source 都为随机字符串。前者长度为32个字符（数字、小写字母，通过 PHP 默认 md5() 计算得到），后者为'NGPT-'+长度40的随机字符串。
 */

/*
 * 1、地址处理见 Discuz.Common/PTTools/PT_Tools.cs
 * 2、显示种子参考 Discuz.Web/templates/default/_showseedstitle.htm。
 * 3、种子搜索参考 Discuz.Web/templates/default/_seedsearch.htm。
 */

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

$sql = <<<SQL

-- -------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pre_ngpt_seed` (
  `seed_id` int(10) NOT NULL COMMENT '种子ID',
  `tid` mediumint(8) unsigned NOT NULL COMMENT '帖子ID',
  `fid` mediumint(8) unsigned NOT NULL COMMENT '版块ID',
  `publisheruid` mediumint(8) unsigned NOT NULL COMMENT '发布者UID',
  PRIMARY KEY `uk_seedid` (`seed_id`),
  UNIQUE KEY `uk_tid` (`tid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

# 用户静态信息表

# 单位: byte

CREATE TABLE IF NOT EXISTS `pre_ngpt_user` (
  `uid` mediumint(8) unsigned NOT NULL COMMENT '用户ID',
  `passkey` char(32) NOT NULL COMMENT '分配的passkey',
  `candownload` boolean NOT NULL DEFAULT '1' COMMENT '是否允许下载',
  PRIMARY KEY `pk_uid` (`uid`),
  UNIQUE KEY `uk_passkey` (`passkey`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

# IHome SSO单点登陆表

# IHome SSO单点登陆表
# 之所以起这个名字是因为校园统一认证很可能采用的不是同一个规范
CREATE TABLE IF NOT EXISTS `pre_ngpt_ihome_sso` (
  `uid` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'UID',
  `ssouid` INT(10) NOT NULL DEFAULT '0' COMMENT 'SSO UID 24位长度',
  `ssoname` CHAR(24) NOT NULL DEFAULT '0' COMMENT 'SSO Name 长度24,见checkUserName函数',
  `ssostatus` SMALLINT(5) NOT NULL DEFAULT '-1' COMMENT 'SSO 状态',
  `token` CHAR(64) NOT NULL DEFAULT '0' COMMENT 'Token 0 服务器端的Token',
  `token1` CHAR(64) NOT NULL DEFAULT '0' COMMENT 'Token 1 本地生成的Token',
  `tokendate` INT(10) NOT NULL DEFAULT '0' COMMENT 'Token 时间戳',
  `tokenstatus` SMALLINT(1) NOT NULL DEFAULT '-1' COMMENT 'Token状态',
  `createtime` INT(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY `ssouid` (`ssouid`),
  UNIQUE KEY `ssoname` (`ssoname`),
  UNIQUE KEY `token` (`token`),
  UNIQUE KEY `token1` (`token1`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

# 总共享率表
# 总上传表
# 总下载表
SQL;
runquery($sql);
//在plugindata中增加语言包配置文件
$dir = DISCUZ_ROOT . "data/plugindata";
@mkdir($dir, 0777, true);
//参见http://open.discuz.net/?ac=document&page=plugin_language
$content = file_get_contents(DISCUZ_ROOT . 'source/plugin/ngpt/lang.php');
file_put_contents($dir . "/ngpt.lang.php", $content);


$sql = <<<SQL
-- --------------------------------------------------------------------------------
-- 增强discuz功能

-- 改变用户名称最大长度，由 char(16) 改为 char(24)，同时保留默认的 UNIQUE 约束

ALTER TABLE `pre_ucenter_members` CHANGE username username char(24) NOT NULL DEFAULT '' COMMENT '用户名';
-- 以及其他相关字段
ALTER TABLE `pre_forum_thread` CHANGE author author char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_thread` CHANGE lastposter lastposter char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_post` CHANGE author author char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_postcomment` CHANGE author author char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_tradecomment` CHANGE rater rater char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_tradecomment` CHANGE ratee ratee char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_collectioncomment` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_comment` CHANGE author author char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_docomment` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_portal_comment` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_debate` CHANGE umpire umpire char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_groupuser` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_order` CHANGE admin admin char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_pollvoter` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_promotion` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_ratelog` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_threadmod` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_trade` CHANGE seller seller char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_trade` CHANGE lastbuyer lastbuyer char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_tradelog` CHANGE seller seller char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_tradelog` CHANGE buyer buyer char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_warning` CHANGE operator operator char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_warning` CHANGE author author char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_album` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_blog` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_doing` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_feed` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_feed_app` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_follow` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_follow` CHANGE fusername fusername char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_follow_feed` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_follow_feed_archiver` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_friend` CHANGE fusername fusername char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_friend_request` CHANGE fusername fusername char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_notification` CHANGE author author char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_pic` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_poke` CHANGE fromusername fromusername char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_share` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_show` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_specialuser` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_specialuser` CHANGE opusername opusername char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_home_visitor` CHANGE vusername vusername char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_ucenter_admins` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_ucenter_feeds` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_ucenter_protectedmembers` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_ucenter_protectedmembers` CHANGE admin admin char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_common_member` CHANGE username username char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_common_member_crime` CHANGE operator operator char(24) NOT NULL DEFAULT '';
ALTER TABLE `pre_common_invite` CHANGE fusername fusername char(24) NOT NULL DEFAULT '';

-- 改变帖子标题最大长度，由于 char(80) 改为 char(300)

ALTER TABLE `pre_forum_post` CHANGE `subject` `subject` varchar(255) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_rsscache` CHANGE `subject` `subject` char(255) NOT NULL DEFAULT '';
ALTER TABLE `pre_forum_thread` CHANGE `subject` `subject` char(255) NOT NULL DEFAULT '';

-- 改变最后回复字段类型为 TEXT，以显示完整的最后回复信息
-- 最小所需长度计算如下：（function_post.php）
-- 8(tid) + 1(tab) + 255(subject) + 1(tab) + 10(lastpostdateline) + 1(tab) + 24(username) = 290

ALTER TABLE `pre_forum_forum` CHANGE `lastpost` `lastpost` TEXT NOT NULL DEFAULT '';
SQL;

$finish = true;

?>
