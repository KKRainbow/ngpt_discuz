<?php
/**
 * Created by PhpStorm.
 * User: ssj
 * Date: 16-1-5
 * Time: 上午2:00
 */

//参见forum对各个mod的调用
require_once 'class_core.inc.php';
require_once 'library/PTHelper.php';

header("Content-Type: text/xml; charset=utf-8");
echo <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root><![CDATA[
XML;

$ok = true;
switch ($_POST['op']) {
    case 'fileinfo':
        /** @var array $_G */
        $res = PTHelper::getApiCurl(
            'seed/file-list',
            ['seed_id' => $_POST['seed_id']]
        );
        if ($res['result'] != 'succeed') {
            showmessage('获取信息错误:' . $res['extra']);
        }
        $json = $res['extra'];
        $flag = false;
        $tmp = $json;
        $range = [0];
        $picroot = '';
        $picrootvar = '';
        if ($tmp != null) {
            $flag = true; //下面开始处理树
            list($json,$maxdepth) = $tmp;
            $range = range(1, $maxdepth+1);
            $picroot = $_G['siteurl'] . 'source/plugin/ngpt/static/image/';
            $picrootvar = '\'' . $picroot . '\'';
        } else {
            $floatwindowid = 'resinfodisplay_file';
            $ok = false;
            include template('ngpt:err/err-infloat');
        }
        break;
    case 'labelinfo':
        $info = PTHelper::getApiCurl(
            'seed/info',
            [
                'query_json' => json_encode([
                    $_POST['seed_id'] => 0
                ]),
            ],
            true
        )['extra'];
        $fields = json_decode($info[0]['detail_info'], true);
        $labels = [
            'infohash' => $info[0]['info_hash'],
            'threadtype' => '',
            'fields' => $fields,
        ];
        break;
    case 'peerinfo':
        global $_G;
        $info = PTHelper::getApiCurl(
            'seed/peer-info',
            [
                'seed_id' => $_POST['seed_id'],
            ]
        )['extra'];
        $all = json_encode($info['all']);
        $leechers = json_encode($info['leechers']);
        $seeders = json_encode($info['seeders']);
        $history = '';
        if ($_G['adminid'] == 1) {
            //TODO 种子下载历史
        }
        break;
    default:
        showmessage('你在干嘛？');
}

if ($ok) {
    include template("ngpt:resinfodisplay/resinfodisplay-{$_POST['op']}");
}

echo <<<XML
]]></root>
XML;
