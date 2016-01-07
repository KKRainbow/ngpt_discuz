<?php
/**
 * Created by PhpStorm.
 * User: MIC
 * Date: 2015/4/18
 * Time: 17:59
 */

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

require_once DISCUZ_ROOT . "source/plugin/ngpt/library/PTHelper.php";

//在threadlist数组中填充种子的infohash
$arr_thread_ids = [];
/** @var array $_G */
foreach ($_G['forum_threadlist'] as $key => $thread) {
    $arr_thread_ids[] = $thread['tid'];
}

if (empty($arr_thread_ids)) {
    return;
}
$str_thread_ids = implode(',', $arr_thread_ids);
$dztbl_ngpt_seeds = DB::table("ngpt_seed");
$sql = <<<EOF
        SELECT $dztbl_ngpt_seeds.seed_id, $dztbl_ngpt_seeds.tid, $dztbl_ngpt_seeds.fid
        FROM `$dztbl_ngpt_seeds` WHERE $dztbl_ngpt_seeds.tid IN ({$str_thread_ids});
EOF;
$seeds = DB::fetch_all($sql);

$req_json = [];
foreach ($seeds as $seed) {
    $req_json[$seed['seed_id']] = $seed['tid'];
}

$data = [
    'query_json' => json_encode($req_json),
];

$res = PTHelper::getApiCurl("seed/info", $data, true);

if (empty($res) || $res['result'] == 'failed') {
    return;
    throw new Exception("种子服务器返回错误");
} else {
    $_G['threads_seeds'] = $res['extra'];
    foreach ($_G['threads_seeds'] as &$s) {
        $tmp = $s['coef_expire_time'];
        if ($tmp != 0) {
            $s['coef_expire_time'] = "剩余".PTHelper::getReadableTimeFromMinutes($tmp/60);
        } else {
            if (!($s['up_coef'] == 100 && $s['down_coef'] == 100)) {
                $s['coef_expire_time'] = '永久';
            }
            else {
                $s['coef_expire_time'] = '';
            }
        }
    }
}

?>