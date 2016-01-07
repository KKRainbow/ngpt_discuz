<?php
/**
 * Created by PhpStorm.
 * User: MIC
 * Date: 2015/5/7
 * Time: 19:36
 */

/**
 * @var array $_G
 * @var int $fid
 * @var int $tid
 * @var string $infohash
 * @var string $threadsubject
 * @var string $op
 * @var int $uid
 */

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}
global $_G;
if (!$_G['uid']) {
    exit();
}
// 管理组 ID，1=管理员 2=超级版主 3=版主，其他=其他用户
/**
 * @var int $adminid
 */
$adminid = $_G['adminid'];
if ($adminid <= 0) {
    showmessage('你好像看到了不该看到的东西！' . PHP_EOL . '（说着，php-cgi 掏出了一个末端亮着红光的金属棒。）');
}
if (!$_G['group']['allowdelpost']) {
    showmessage('您没有权限删除资源帖。');
}


require_once DISCUZ_ROOT . "source/plugin/ngpt/library/PTHelper.php";
require_once libfile('function/delete');

$tids = $_POST['moderate'];

foreach ($tids as &$tid) {
    if (!is_numeric($tid)) {
        throw new Exception("非法参数");
    }
    $tid = intval($tid);
}
unset($tid);

$seed_not_exists = 0;
$thread_not_exists = 0;
$succeed = 0;

foreach ($tids as $tid) {
    $tbl = DB::table("ngpt_seed");
    $sql = <<<SQL
SELECT seed_id FROM {$tbl} WHERE tid=$tid;
SQL;
    $seed = DB::fetch_first($sql);
    $reason = $_POST['reason'];
    $penalty = $_POST['penalty'];

    if (empty($seed) || empty($seed['seed_id'])) {
        deletethread(array($tid));
        $seed_not_exists++;
        continue;
    }

    if (strlen($reason) <= 0) {
        $reason = '未写明理由';
    }

    $data = [
        'penalty' => $penalty,
        'reason' => $reason,
        'seed_id' => $seed['seed_id']
    ];

    $res = PTHelper::getApiCurl("seed/delete", $data);

    if ($res['result'] != 'success') {
        if ($res['reason'] == 'not exists') {
            deletethread(array($tid));
            $seed_not_exists++;
            continue;
        } else {
            throw new Exception("删除seed_id : {$seed['seed_id']} 失败"
                . $res['extra']);
        }
    } else {
        $seed_info = $res['extra'];
        $threadinfo = DB::query("SELECT tid FROM " . DB::table('forum_thread') . " WHERE tid='{$tid}' LIMIT 1;");
        $threadnum = DB::num_rows($threadinfo);
        DB::free_result($threadinfo);
        // 如果帖子未删除
        if ($threadnum <= 0) {
            $thread_not_exists++;
        }
        // 查询发种者
        $publisheruid = $seed_info['discuz_pub_uid'];
        $threadsubject = DB::fetch_first("SELECT subject FROM " . DB::table('forum_thread') . " WHERE tid='{$tid}';")['subject'];
        $name = DB::fetch_first("SELECT username FROM " . DB::table('ucenter_members') . " WHERE uid='{$uid}';")['username'];

        // 关于用户名：
        //"alter table pre_ucenter_members change username username char(32) default '' not null;";
        //http://www.discuz.net/thread-1634979-1-1.html
        //http://www.51php.com/discuz/17191.html

        // 在发送短消息之前要对即将加入的信息进行转义
        $threadsubject = htmlentities($threadsubject, ENT_QUOTES);
        $reason = htmlentities($reason, ENT_QUOTES);

        // 给资源发布者发送短消息（资源帖）
        // subject: 帖子标题
        // tid: 帖子ID
        // operatoruid: 操作者UID
        // operatorname: 操作者名称
        // penalty: 扣罚流量（单位: GB）
        // reason: 理由
        notification_add($publisheruid, 'system', 'ngpt:pt_seed_deleted_publisher' . ($penalty != 0 ? '_penalty' : ''), array(
            'subject' => $threadsubject,
            'tid' => $tid,
            'operatoruid' => $uid,
            'operatorname' => $name,
            'penalty' => $penalty,
            'reason' => $reason,
        ), true);
        // 然后给所有曾经下载过的用户发送短消息
        $qr = $seed_info['discuz_use_uid'];
        if ($qr) {
            foreach ($qr as $qrrow) {
                $downloaderuid = $qrrow['uid'];
                notification_add($downloaderuid, 'system', 'ngpt:pt_seed_deleted_downloader', array(
                    'subject' => $threadsubject,
                    'tid' => $tid,
                    'operatoruid' => $uid,
                    'operatorname' => $name,
                    'reason' => $reason,
                ), true);
            }
        }

        // 删除帖子
        //require_once libfile('function/post');
        deletethread(array($tid));
        // 该函数在10站上运行似乎有问题，还是将记录存在种子操作记录表里吧
        // 2015-05-26 好像不是此函数的问题，注释掉后照样会发生
        // 2015-05-27 好吧确实是这个的问题，注释掉
        //updatemodlog($tid, 'DEL', 0, false, $reason_db);
        $succeed++;
    }
}
$str = "成功删除" . $succeed . "个种子</br>";
if ($seed_not_exists != 0) {
    $str .= "$seed_not_exists 个种子不存在，帖子已经被删除";
}
if ($thread_not_exists != 0) {
    $str .= "$thread_not_exists 个帖子不存在";
}
showmessage(
    $str,
    dreferer(),
    [
        'redirectmsg' => 1
    ]
);
