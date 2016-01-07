<?php

// 用户信息插入页面
// 工作：显示BT统计信息、重置passkey、重置上传下载状态

/*
 * 基于紫晓暮雾的版本。
 */

/*
当前版本为V1.01
修改时间：2015年4月9日11:15:15

V1.10改动：
1.将function reset_tr中一处硬编码改为软编码。（原来的硬编码可能出问题）
*/

/**
 * @var array $_G
 */

global $_G;

/**
 * @var string $r_up Real upload
 * @var string $r_up_str
 * @var string $s_up Statistical upload
 * @var string $s_up_str
 * @var string $r_down Real download
 * @var string $r_down_str
 * @var string $s_down Statistical download
 * @var string $s_down_str
 * @var float $s_share_ratio
 * @var string $s_share_ratio_str
 * @var string $s_share_ratio_color_str
 * @var string $candownloadstatus See viewresthead.php
 * @var string $passkey
 * @var string $fulltracker
 * @var int $numuploadingseeds 上传种子数
 * @var int $numdownloadingseeds 下载种子数
 * @var int $numdownloadedseeds 下载完成数
 * @var int $numpublishedseeds 发布种子数
 * @var int $numuploadingpeers 正在上传的总peer数（同一个种子不同IP算不同peer）
 * @var int $numuploadingpeers 正在上传的总peer数（同一个种子不同IP算不同peer）
 */

$uid = $_G['uid'];

// 这些字段只有用户开启了PT功能才有意义
$qnu_stat = query_ngpt_users('uploaded,downloaded,realup,realdown,candownload', $uid);
$t_cdstate_b = !empty($qnu_stat);
if ($t_cdstate_b) {
    $candownloadstatus = $qnu_stat['candownload'];
    if ($candownloadstatus) {
        $candownloadstatus = 'normal';
    } else {
        $candownloadstatus = 'banned';
    }
} else {
    $candownloadstatus = 'notenabled';
}

$numuploadingseeds = 0;
$numdownloadingseeds = 0;
$numdownloadedseeds = 0;
$numpublishedseeds = 0;
$numuploadingpeers = 0;
$numdownloadingpeers = 0;
$passkey = '';
$fulltracker = '';
$s_share_ratio_color_str = 'darkgray';

// 上传下载
if ($t_cdstate_b) {
    $s_up = $qnu_stat['uploaded'];
    $s_down = $qnu_stat['downloaded'];
    $r_up = $qnu_stat['realup'];
    $r_down = $qnu_stat['realdown'];
} else {
    $s_up = $s_down = $r_up = $r_down = 0;
}
$s_up_str = format_byte_size_output($s_up);
$s_down_str = format_byte_size_output($s_down);
$r_up_str = format_byte_size_output($r_up);
$r_down_str = format_byte_size_output($r_down);

// 共享率
if ($s_down == 0) {
    // 设置统一共享率上限为1000
    $s_share_ratio = ($s_up != 0 ? 1000 : 0);
    $s_share_ratio_str = ($s_up != 0 ? '1000.00' : '0.00');
} else {
    $s_share_ratio = $s_up / $s_down;
    $s_share_ratio_str = sprintf('%.02lf', $s_share_ratio);
}

if ($s_share_ratio < 0 or $s_up < 0) {
    $s_share_ratio_color_str = 'gold';
} else if ($s_share_ratio == 0) {
    $s_share_ratio_color_str = 'black';
} else if ($s_share_ratio < 1) {
    $s_share_ratio_color_str = 'red';
} else if ($s_share_ratio < 2) {
    $s_share_ratio_color_str = 'navy';
} else if ($s_share_ratio < 5) {
    $s_share_ratio_color_str = 'mediumblue';
} else if ($s_share_ratio < 10) {
    $s_share_ratio_color_str = '#00A200';
} else {
    $s_share_ratio_color_str = '#00DA00';
}

// 正在上传、正在下载、完成数
if ($t_cdstate_b) {
    // Discuz 不支持 SELECT * FROM (SELECT * FROM) 查询，为了安全性，所以只好使用原生数组
    // Fixed 2015-04-12 别用原生数组啊亲，那个是 fetch 过的也降低了性能的，直接用 num_rows()。
    // Fixed 2015-04-12 问题是上了双栈后要分开统计并归并，在不支持 UNION 的情况下肯定要去 fetch 啊……
    // Converted 2015-04-14 将双表改为单表

    $sql = "SELECT COUNT(*) AS total FROM " . DB::table('ngpt_peers') . " WHERE uid='{$uid}' AND status='seeder';";
    $r1 = DB::fetch_first($sql);
    $sql = "SELECT COUNT(*) AS total FROM " . DB::table('ngpt_peers') . " WHERE uid='{$uid}' AND status='leecher';";
    $r2 = DB::fetch_first($sql);
    $sql = "SELECT COUNT(DISTINCT infohash, uid) AS total FROM " . DB::table('ngpt_peers') . " WHERE uid='{$uid}' AND status='seeder';";
    $r3 = DB::fetch_first($sql);
    $sql = "SELECT COUNT(DISTINCT infohash, uid) AS total FROM " . DB::table('ngpt_peers') . " WHERE uid='{$uid}' AND status='leecher';";
    $r4 = DB::fetch_first($sql);

    // 统计某个用户的实际上传中/下载中的种子数量
    // 由于某个用户对一个种子可能只有v4或者只有v6，所以应该将二者合起来判断
    // 由于 Discuz 不支持 UNION（安全性问题），所以只好在 PHP 中手工去重（去虫 XD）
    // 注意这么做可能会让机器开销增大！
    // 关于 $r1、$r2、$r3、$r4 的命名——确实有点作用域污染的意味，不过后面别用这么短的就可以了。

    // 正在上传下载peer数
    $numuploadingpeers = $r1['total'];
    $numdownloadingpeers = $r2['total'];
    // 正在上传下载数
    $numuploadingseeds = $r3['total'];
    $numdownloadingseeds = $r4['total'];

    $sql = "SELECT COUNT(*) AS total FROM " . DB::table('ngpt_seeds') . " WHERE publisheruid='{$uid}';";
    $r = DB::fetch_first($sql);
    $numpublishedseeds = $r['total'];

    // 下载完成数
    $sql = "SELECT COUNT(*) AS total FROM " . DB::table('ngpt_history') . " WHERE uid='{$uid}';";
    $r = DB::fetch_first($sql);
    $numdownloadedseeds = $r['total'];
}

// passkey 和 tracker
if ($t_cdstate_b) {
    $passkey = query_ngpt_users('passkey', $uid);
    $fulltracker = $_G['siteurl'] . 'announce.php?passkey=' . $passkey;
}

/**
 * @param float $num
 * @return string
 */
function format_byte_size_output($num)  //将上传下载转换为标准输出
{
    /**
     * @var string $ret
     */
    $unit = 1;
    $orignum = $num;

    while (abs($num) >= 1000 && $unit <= 5) {
        $num = $num / 1024.0;
        $unit++;
    }

    if (abs($orignum) >= 1000) {
        $ret = sprintf('%.2lf', $num);
    } else {
        $ret = strval(round($num, 2));
    }

    switch ($unit) {
        case 0:
            $ret = ' EUnit';
            break;
        case 1:
            $ret .= ' Byte';
            break;
        case 2:
            $ret .= ' KB';
            break;
        case 3:
            $ret .= ' MB';
            break;
        case 4:
            $ret .= ' GB';
            break;
        case 5:
            $ret .= ' TB';
            break;
        default:
            $ret .= ' PB';
            break;
    }

    return $ret;
}

function query_ngpt_users($query_thing, $uid)  //查询数据库
{
    /*
        $sql=<<<EOF
                SELECT {$query_thing} FROM `{$pre}ngpt_users` where uid='{$uid}';
    EOF;
    */
    $qtarr = explode(',', $query_thing);
    $sql = "SELECT {$query_thing} FROM " . DB::table('ngpt_users') . " WHERE uid='{$uid}';";
    $result = DB::fetch_first($sql);
    if (count($qtarr) == 1) {
        return $result[$query_thing];
    } else {
        return $result;
    }
}

function query_ngpt_users_exists($uid)  //查询数据库
{
    /*
        $sql=<<<EOF
                SELECT {$query_thing} FROM `{$pre}ngpt_users` where uid='{$uid}';
    EOF;
    */
    $sql = "SELECT uid FROM " . DB::table('ngpt_users') . " WHERE uid='{$uid}' LIMIT 1;";
    $queryresult = DB::query($sql);
    $ret = DB::num_rows($queryresult);
    DB::free_result($queryresult);
    return !!$ret;
}

/**
 * 用于处理类似如下的结构：
 * Array ( [0] => Array ( [infohash] => cacfe9c154fea54418d264e788d10a0e2a3ed65a ), [1] => Array ( ... ), ... )
 * 或者
 * Array ( [infohash] => (infohash) ) （一项）
 * 并返回
 * Array ( [(infohash)] => count_of(infohash), ... )
 * 这样的结构。
 * @param array $array
 * @param mixed $key
 * @return array
 */
function x_count_ret_array($array, $key)
{
    if (isset($array[$key])) {
        $ret = array($array[$key] => 1);
    } else {
        $array_2 = array();
        foreach ($array as $array_v) {
            $array_2[] = $array_v[$key];
        }
        // 将 key-value = index-value 数组转化为 key-value = value-count 数组
        $ret = array_count_values($array_2);
    }
    return $ret;
}

// TODO: userstat.htm 内部的 JavaScript 函数可以考虑用 showWindow() 代替 showmessage()，这样就可以在单一页面刷新而不用手工指定跳转回去的地址。

?>