<?php
//用于方便的在插件中初始化应用,用于admin.php和search.php这种
//直接由插件调用不经过discuz路由的页面
$source_dir = dirname(dirname(dirname($_SERVER['PHP_SELF'])));
$source_dir = $_SERVER['DOCUMENT_ROOT'] . $source_dir;
require_once $source_dir . '/class/class_core.php';

C::app()->cachelist = [];
C::app()->init();

//showmessage函数用到这个
$_G['siteurl'] = str_replace('source/plugin/ngpt/', '', $_G['siteurl']);

