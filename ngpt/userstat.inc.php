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

require_once 'library/PTHelper.php';

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

$detail_info = PTHelper::getApiCurl('user/info', ['detail' => true]);

// 这些字段只有用户开启了PT功能才有意义
$t_cdstate_b = !empty($detail_info);
if ($t_cdstate_b) {
    $candownloadstatus = $detail_info['is_valid'];
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
    $s_up = $detail_info['stat_up'];
    $s_down = $detail_info['stat_down'];
    $r_up = $detail_info['real_up'];
    $r_down = $detail_info['real_down'];
} else {
    $s_up = $s_down = $r_up = $r_down = 0;
}
$s_up_str = PTHelper::getReadableFileSize($s_up);
$s_down_str = PTHelper::getReadableFileSize($s_down);
$r_up_str = PTHelper::getReadableFileSize($r_up);
$r_down_str = PTHelper::getReadableFileSize($r_down);

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
} elseif ($s_share_ratio == 0) {
    $s_share_ratio_color_str = 'black';
} elseif ($s_share_ratio < 1) {
    $s_share_ratio_color_str = 'red';
} elseif ($s_share_ratio < 2) {
    $s_share_ratio_color_str = 'navy';
} elseif ($s_share_ratio < 5) {
    $s_share_ratio_color_str = 'mediumblue';
} elseif ($s_share_ratio < 10) {
    $s_share_ratio_color_str = '#00A200';
} else {
    $s_share_ratio_color_str = '#00DA00';
}

// 正在上传、正在下载、完成数
if ($t_cdstate_b) {
    // 统计某个用户的实际上传中/下载中的种子数量
    // 由于某个用户对一个种子可能只有v4或者只有v6，所以应该将二者合起来判断
    // 由于 Discuz 不支持 UNION（安全性问题），所以只好在 PHP 中手工去重（去虫 XD）
    // 注意这么做可能会让机器开销增大！
    // 关于 $r1、$r2、$r3、$r4 的命名——确实有点作用域污染的意味，不过后面别用这么短的就可以了。

    // 正在上传下载peer数
    $numuploadingpeers = $detail_info['seeder_count'];
    $numdownloadingpeers = $detail_info['leecher_count'];
    // 正在上传下载数
    $numuploadingseeds = $detail_info['seed_up_count'];
    $numdownloadingseeds = $detail_info['seed_down_count'];

    $numpublishedseeds = $detail_info['published_seed'];

    // 下载完成数
    $numdownloadedseeds = $detail_info['completed_count'];
}

// passkey 和 tracker
if ($t_cdstate_b) {
    $passkey = $detail_info['passkey'];
    $fulltracker = PTHelper::getApiUrl('tracker/announce');
    $fulltracker = preg_replace(
        "/127.0.0.1/",
        $_SERVER['SERVER_ADDR'],
        $fulltracker
    );
}

