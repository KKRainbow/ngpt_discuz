<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 16-1-2
 * Time: 上午6:51
 */

$api_addr = getenv('SEED_API') ?: "http://127.0.0.1/seed/index.php?r=";
$password = getenv('SEED_PASSWORD') ?: 'ngpt_2333';

global $_G;
$_G['ngpt'] = [
    "api" => $api_addr,
    'dir' => DISCUZ_ROOT . "source/plugin/ngpt/",
    'add_user_password' => $password,
];
