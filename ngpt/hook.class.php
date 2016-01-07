<?php

// 资源帖管理类

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

require_once 'global.inc.php';
require_once 'library/PTHelper.php';

// 接下来定义的是页面钩子
// 以下内容参考 http://bbs.zb7.com/discuz/dx25/plug/plugin/plugin_hook.htm

class plugin_ngpt
{

    /**
     * 当一个帖子（不仅仅是资源帖）被删除时触发
     * @param array $params
     * @return array
     */
//    function deletethread($params)
//    {
//        return array();
//    }

    /**
     * 全局页面顶端（原来花园显示快捷上传下载的位置）。
     * @param array $params
     * @return string
     */
    function global_cpnav_extra1($params)
    {
        /**
         * @var array $_G
         * @var string $return
         */
        global $_G;
        if ($_G['uid']) {
            // 仅当用户登录后才显示
            $s_up = $_G['user_info']['stat_up'];
            $s_down = $_G['user_info']['stat_down'];
            $s_share_ratio_str = null;
            if ($s_down == 0) {
                // 设置统一共享率上限为1000
                $s_share_ratio = ($s_up != 0 ? 1000 : 0);
                $s_share_ratio_str = ($s_up != 0 ? '1000.00' : '0.00');
            } else {
                $s_share_ratio = $s_up / $s_down;
                $s_share_ratio_str = sprintf('%.02lf', $s_share_ratio);
            }
            $s_share_ratio_color_str = null;
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
            $imgroot = $_G['ngpt_root'] . 'static/image/';
            $u_str = PTHelper::getReadableFileSize($s_up);
            $d_str = PTHelper::getReadableFileSize($s_down);
            include template('ngpt:hooks/global/cpnav_extra1');
            return $return;
        } else {
            return '';
        }
    }

    /**
     * 原来花园“您正在使用**地址登录”的位置
     * @param array $params
     * @return string
     */
    function global_cpnav_extra2($params)
    {
        $ip = PTHelper::GetIPString();
        $iptype = PTHelper::GetIPType($ip);
        $ip = htmlentities($ip);

        /**
         * @var string $return
         */
        include template('ngpt:hooks/global/cpnav_extra2');
        return $return;
    }

}

require_once DISCUZ_ROOT . 'source/plugin/ngpt/hooks/hook-thread.php';

?>