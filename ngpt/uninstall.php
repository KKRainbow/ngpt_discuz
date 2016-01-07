<?php

// 卸载插件用
if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

$sql = <<<EOF

DROP TABLE IF EXISTS `pre_ngpt_user`;
DROP TABLE IF EXISTS `pre_ngpt_seed`;
DROP TABLE IF EXISTS `pre_ngpt_ihome_sso`;

EOF;

runquery($sql);

$finish = true;

?>