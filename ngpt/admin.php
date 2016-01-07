<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 16-1-5
 * Time: 上午2:00
 */

//参见forum对各个mod的调用

define('APPTYPEID', 2);
define('CURSCRIPT', 'forum');

require_once 'class_core.inc.php';
require_once DISCUZ_ROOT . 'source/function/function_forum.php';

$adminid = $_G['adminid'];
if ($adminid <= 0) {
    showmessage('你好像看到了不该看到的东西！' . PHP_EOL . '（说着，php-cgi 掏出了一个末端亮着红光的金属棒。）');
}
//开始处理post的管理请求
if (empty($_POST['submit']) || $_POST['submit'] != 1) {
    //获取表单
    header("Content-Type: text/xml; charset=utf-8");
    $fid = $_GET['fid'];
    echo <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root><![CDATA[
XML;
    echo <<<HTML
<form method="post" action="source/plugin/ngpt/admin.php?fid={$fid}&submit=1" autocomplete="off"
 id="moderateform">
    <input type="hidden" name="fid" value="{$fid}"/>
    <input type="hidden" name="submit" value="1"/>
    <input type="hidden" name="operation" value="{$_POST['operation']}"/>
HTML;

    foreach ($_POST['moderate'] as $tid) {
        echo <<<HTML
<input name="moderate[]" value="{$tid}" type="hidden"/>
HTML;
    }

    include template("ngpt:resmod/resmod-{$_POST['operation']}");

    echo <<<XML
]]></root>
XML;
} else {
    //执行
    include DISCUZ_ROOT . "source/plugin/ngpt/resmod/{$_POST['operation']}" . ".php";
}
