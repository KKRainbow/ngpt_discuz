<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 16-1-6
 * Time: 上午12:08
 */

//由header_common模板调用，定义一些全局变量，全局函数等，在其他地方就可以直接用了

require_once 'config.inc.php';
require_once  'library/PTHelper.php';

$_isThreadSeed = null;
function isThreadSeed()
{
    global $_isThreadSeed;
    if (empty($_isThreadSeed)) {
        global $_G;
        $tid = $_G['tid'];
        $_isThreadSeed = PTHelper::isThreadHasSeed($tid);
    }
    return $_isThreadSeed;
}
if ($_G['uid']) {
    $key_u = 'ngpt_user_info_' . $_G['uid'];
    $key_t = 'ngpt_user_info_' . $_G['uid'] . 'time';
    loadcache([$key_t, $key_u]);
    $user_info = null;
    if (!empty($_G['cache'][$key_t]) && !empty($_G['cache'][$key_u])) {
        $user_info = json_decode($_G['cache'][$key_u], true);
        $time = intval($_G['cache'][$key_t]);
        if ($time < time()) {
            $user_info = null;
        }
    }
    if (empty($user_info)) {
        $user_info = PTHelper::getApiCurl('user/info', ['detail' => true]);
        savecache($key_u, json_encode($user_info));
        savecache($key_t, time() + 120);
    }
    $_G['user_info'] = $user_info;
}
$_G['ngpt_root'] = $_G['siteurl'] . 'source/plugin/ngpt/';
