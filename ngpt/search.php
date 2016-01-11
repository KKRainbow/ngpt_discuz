<?php
/**
 * Created by PhpStorm.
 * User: sunsijie
 * Date: 4/18/15
 * Time: 10:12 PM
 */

define('APPTYPEID', 2);
define('CURSCRIPT', 'search');
define('CURMODULE', 'seed');


require 'class_core.inc.php';

define('NOROBOT', TRUE);

if(!$_G['setting']['search']['forum']['status']) {
    showmessage('search_forum_closed');
}

if(!$_G['adminid'] && !($_G['group']['allowsearch'] & 2)) {
    showmessage('group_nopermission', NULL, array('grouptitle' => $_G['group']['grouptitle']), array('login' => 1));
}

$_G['setting']['search']['forum']['searchctrl'] = intval($_G['setting']['search']['forum']['searchctrl']);

require_once libfile('function/forumlist');
require_once libfile('function/forum');
require_once libfile('function/search');
require_once libfile('function/post');
require_once DISCUZ_ROOT . 'source/plugin/ngpt/library/PTHelper.php';


function ShowSearchFailMessage($msg = "")
{
    dheader('Location: search.php?mod=seed&msg=' . $msg);
}
//获取各项搜索参数
$srchmod = 30;
$cachelife_time = 1;		// Life span for cache of searching in specified range of time
$cachelife_text = 3600;		// Life span for cache of text searching
$mod = $_GET['mod'];

//SearchID 见后面的详细说明
$searchid = getgpc('searchid');
//表单的hash值
//搜索框中的内容
$srchtxt = trim(getgpc('srchtxt'));
//作者
$author = (getgpc('author') != '') ? trim(str_replace('|', '', getgpc('author'))) : '';;
//搜索时间
$srchfrom = intval(getgpc('srchfrom'));
$srchfrom /= 3600 * 24;//换算成天数
//之前还是之后?
$before = intval(getgpc('before'));
//搜索的板块
$srchfid = getgpc('srchfid');
//是否只要蓝种
$onlyblue = getgpc('bluefilter') == 'onlyblue';
//不查看死种
$nodead = getgpc('deadfilter') == 'nodead';
//上传系数
$upcoe = intval(getgpc('upcoe'));
//是否要求大于选择的上传系数
$upcoelarger = getgpc('upcoeorder') == null||getgpc('upcoeorder') != 0;
//下载系数
$downcoe = intval(getgpc('downcoe'));
//是否要求大于选择的下载系数
$downcoelarger =  getgpc('downcoeorder') == null||getgpc('downcoeorder') != 0;
//排序方式
$orderby = getgpc('orderby');
$orderby = in_array($orderby, array('dateline', 'replies', 'views','lastpost'))
    ? $orderby : 'lastpost';
//是否是降序
$ascdesc = getgpc('ascdesc');
//是否是表单的提交
$resource_fids = PTHelper::getResourceForumList();
$forumselect = forumselect(0,0,$resource_fids);
if(!empty($srchfid) && !is_numeric($srchfid)) {
    $forumselect = str_replace('<option value="'.$srchfid.'">', '<option value="'.$srchfid.'" selected="selected">', $forumselect);
}

$searchsubmit = getgpc('searchsubmit') == 'yes' || getgpc('searchsubmit') == 'true';

//分解查询框内容形成关键字
$keyword = isset($srchtxt) ? dhtmlspecialchars(trim($srchtxt)) : '';
$keyword = $keyword?:getgpc('kw');

if(!$searchsubmit)
{
    include template('search/seed-main');
    exit;
}

$_G['fid'] = $resource_fids[0];
//搜索分两次进行,第一次时POST请求,服务器对请求进行判断并把搜索结果作为缓存放入数据库,
//并把浏览器重定向为一次GET请求
//第二次针对GET请求,GET请求有searchid,服务器根据Searchid来构造返回的内容

//第二次的GET请求的处理/////////////////////////////////////////////////////////////////////
if(!empty($searchid)) {
    require_once libfile('function/misc');

    $page = max(1, intval($_GET['page']));
    $start_limit = ($page - 1) * $_G['tpp'];

    $index = C::t('common_searchindex')->fetch_by_searchid_srchmod($searchid, $srchmod);
    if(!$index) {
        showmessage('search_id_invalid');
    }

    $keyword = dhtmlspecialchars($index['keywords']);
    $keyword = $keyword != '' ? str_replace('+', ' ', $keyword) : '';

    $index['keywords'] = rawurlencode($index['keywords']);
    $searchstring = explode('|', $index['searchstring']);
    $index['searchtype'] = $searchstring[0];//preg_replace("/^([a-z]+)\|.*/", "\\1", $index['searchstring']);
    //把搜索字符串转换,下标应该是1
    $searchstring[1] = base64_decode($searchstring[1]);
    $srchuname = $searchstring[3];
    $modfid = 0;
    if($keyword) {
        $modkeyword = str_replace(' ', ',', $keyword);
        $fids = explode(',', str_replace('\'', '', $searchstring[5]));
        if(count($fids) == 1 && in_array($_G['adminid'], array(1,2,3))) {
            $modfid = $fids[0];
            if($_G['adminid'] == 3 && !C::t('forum_moderator')->fetch_uid_by_fid_uid($modfid, $_G['uid'])) {
                $modfid = 0;
            }
        }
    }

    $todaytime = strtotime(dgmdate(TIMESTAMP, 'Ymd'));
    $threadlist = $posttables = array();
    foreach(C::t('forum_thread')->fetch_all_by_tid_fid_displayorder(explode(',',$index['ids']), null, 0, null, $start_limit, $_G['tpp'], '>=', $ascdesc) as $thread) {
        $thread['subject'] = bat_highlight($thread['subject'], $keyword);
        $thread['realtid'] = $thread['isgroup'] == 1 ? $thread['closed'] : $thread['tid'];

        $thread['allreplies'] = $thread['replies'] + $thread['comments'];

        $threadlist[$thread['tid']] = procthread($thread, 'dt');
        $posttables[$thread['posttableid']][] = $thread['tid'];
    }
    if($threadlist) {
        foreach($posttables as $tableid => $tids) {
            foreach(C::t('forum_post')->fetch_all_by_tid($tableid, $tids, true, '', 0, 0, 1) as $post) {
                $threadlist[$post['tid']]['message'] = bat_highlight(messagecutstr($post['message'], 200), $keyword);
            }
        }

    }
    $multipage = multi($index['num'], $_G['tpp'], $page, "search.php?".
        "mod=seed&".
        "searchid=$searchid&".
        "orderby=$orderby&".
        "ascdesc=$ascdesc&".
        "searchsubmit=yes&".
        "kw=".urlencode($keyword).
        "&author=$author&".
        "oblue={$onlyblue}&".
        "nodead={$nodead}");

    $url_forward = 'search.php?mod=forum&'.$_SERVER['QUERY_STRING'];

    $fulltextchecked = $searchstring[1] == 'fulltext' ? 'checked="checked"' : '';

    $_G['forum_threadlist'] = $threadlist;
    $_G['forum_threadcount'] = count($threadlist);
    include template('search/seed-main');
}
///////////////////////////////////////////////////////////////////////////////////////////
//第一次POST请求的处理//////////////////////////////////////////////////////////////////////
else
{
    $orderby = in_array($_GET['orderby'], array('dateline', 'replies', 'views')) ? $_GET['orderby'] : 'lastpost';
    $ascdesc = isset($_GET['ascdesc']) && $_GET['ascdesc'] == 'asc' ? 'asc' : 'desc';
    $srchtype = 'title';
    $specials = '';
    $srchfilter = in_array($_GET['srchfilter'], array('all', 'digest', 'top')) ? $_GET['srchfilter'] : 'all';

    //把所有允许搜索的板块放到$forumarray中///////////////////////////
    $forumsarray = array();
    if(!empty($srchfid)) {
        foreach((is_array($srchfid) ? $srchfid : explode('_', $srchfid)) as $forum) {
            if($forum = intval(trim($forum))) {
                $forumsarray[] = $forum;
            }
        }
    } else {
        $forumsarray = $resource_fids;
    }

    $fids = $comma = '';
    foreach($_G['cache']['forums'] as $fid => $forum) {
        if($forum['type'] != 'group' && (!$forum['viewperm'] && $_G['group']['readaccess']) || ($forum['viewperm'] && forumperm($forum['viewperm']))) {
            if(!$forumsarray || in_array($fid, $forumsarray)) {
                $fids .= "$comma'$fid'";
                $comma = ',';
            }
        }
    }
    ///////////////////////////////////////////////////////////////

    //处理此次search的所有表单内容,加入数据库//////////////////////////
    //这就是表单的所有内容了
    $searchstring = 'seed|'.
        base64_encode($srchtxt).'|'.
        $author.'|'.
        intval($srchfrom).'|'.
        intval($before).'|'.
        addslashes(implode('M',$srchfid)).'|'.
        $onlyblue.'|'.
        $nodead. '|'.
        $upcoe . '|'.
        $upcoelarger.'|'.
        $downcoe . '|'.
        $downcoelarger.'|'.
        $orderby . '|'.
        $ascdesc;

    //看数据库里面有没有,如果有,把它的最后使用时间更新以下
    //说明这条搜索比较常用,防止这条记录过早被替换出去
    $searchindex = array('id' => 0, 'dateline' => TIMESTAMP);
    //寻找可用的搜索记录
    foreach(C::t('common_searchindex')->fetch_all_search($_G['setting']['search']['forum']['searchctrl'], $_G['clientip'], $_G['uid'], $_G['timestamp'], $searchstring, $srchmod) as $index) {
        if($index['indexvalid'] && $index['dateline'] > $searchindex['dateline']) {
            $searchindex = array('id' => $index['searchid'], 'dateline' => $index['dateline']);
            break;
        } elseif($_G['adminid'] != '1' && $index['flood']) {
            showmessage('search_ctrl', 'search.php?mod=seed', array('searchctrl' => $_G['setting']['search']['forum']['searchctrl']));
        }
    }
    //如果在数据库里找到了这条记录,我们就直接跳到第二步:GET请求
    if($searchindex['id']) {
        $searchid = $searchindex['id'];
    }
    ///////////////////////////////////////////////////////////////

    //没有在数据库利找到,我们就必须做一次查询,并且把结果放到数据库利//////
    else
    {
        !($_G['group']['exempt'] & 2) && checklowerlimit('search');

        if (!$srchtxt && !$author && !$srchfrom && !$onlyblue && !is_array($special)) {
            ShowSearchFailMessage("请填写足够的信息");
        } elseif(isset($srchfid) && !empty($srchfid) && $srchfid != 'all' && !(is_array($srchfid) && in_array('all', $srchfid)) && empty($forumsarray)) {
            ShowSearchFailMessage("您选择的板块/论坛范围不合法");
        } elseif(!$fids) {
            showmessage('您所在的用户组不允许搜索您所选的板块', NULL, array('grouptitle' => $_G['group']['grouptitle']), array('login' => 1));
        }

        if($_G['adminid'] != '1' && $_G['setting']['search']['forum']['maxspm']) {
            if(C::t('common_searchindex')->count_by_dateline($_G['timestamp'], $srchmod) >= $_G['setting']['search']['forum']['maxspm']) {
                ShowSearchFailMessage("您所在用户组的最小搜索间隔: " .$_G['setting']['search']['forum']['maxspm'] . "秒");
            }
        }

        //查询种子服务器获取seed_id列表
        $type_subtype_pairs = [];
        foreach ($forumsarray as $fid) {
            if (!empty($fid)) {
                $type_subtype_pairs[$fid] = null;
            }
        }
        $type_subtype_pairs = json_encode($type_subtype_pairs);

        $form = [
            'search_text' => $srchtxt,
            'type_subtype_assoc' => $type_subtype_pairs,
            'order_by' => ['seeder_count'],
            'order_type' => ['desc'],
            'downcoe_min' => 0,
            'downcoe_max' => 999999,
            'upcoe_min' => 0,
            'upcoe_max' => 999999,
            'limit' => 300,
        ];

        if ($upcoelarger) {
            $form['upcoe_min'] = $upcoe * 100;
        } else {
            $form['upcoe_max'] = $upcoe * 100;
        }

        if ($onlyblue) {
            $form['downcoe_min'] = 0;
            $form['downcoe_max'] = 0;
        } else {
            if ($downcoelarger) {
                $form['downcoe_min'] = $downcoe * 100;
            } else {
                $form['downcoe_max'] = $downcoe * 100;
            }
        }

        if ($nodead) {
            $form['nodead'] = true;
        }

        $api = PTHelper::getApiCurl('seed/search', http_build_query($form), true);

        if (empty($api) || $api['result'] != 'succeed') {
            throw new Exception('搜索失败，请检查种子服务器配置');
        }

        $seed_ids = $api['extra'];

//获取所有的种子对应的tid
        $tbl = DB::table("ngpt_seed");
        $ids = implode(',', $seed_ids);
        $res = [];
        if (!empty($ids)) {
            $sql = <<<SQL
SELECT seed_id,tid FROM $tbl WHERE seed_id IN ($ids);
SQL;
            $res = DB::fetch_all($sql);
        }
        //上面查到的是乱序的，必须重新排一遍序。
        $seed_ordered_list = [];
        foreach ($seed_ids as $id) {
            foreach ($res as $seed) {
                if ($seed['seed_id'] == $id) {
                    $seed_ordered_list[] = $seed['tid'];
                    continue;
                }
            }
        }

        $expiration = TIMESTAMP + $cachelife_time;
        $num = count($seed_ordered_list);

        $keywords = str_replace('%', '+', $srchtxt);

        $searchid = C::t('common_searchindex')->insert(array(
            'srchmod' => $srchmod,
            'keywords' => $keywords,
            'searchstring' => $searchstring,
            'useip' => $_G['clientip'],
            'uid' => $_G['uid'],
            'dateline' => $_G['timestamp'],
            'expiration' => $expiration,
            'num' => $num,
            'ids' => implode(',', $seed_ordered_list)
        ), true);

        !($_G['group']['exempt'] & 2) && updatecreditbyaction('search');

    }
    ///////////////////////////////////////////////////////////////
    $get = http_build_query($_POST);
    dheader("location: search.php?".
        "mod=seed&".
        "searchid=$searchid&".
        "searchsubmit=yes&".
        "kw=".urlencode($keyword).
        '&' .
        $get);
}
///////////////////////////////////////////////////////////////////////////////////////////


