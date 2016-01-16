<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

/**
 * Created by PhpStorm.
 * User: sunsijie
 * Date: 4/12/15
 * Time: 11:20 AM
 */

// 用作帖子钩子

class plugin_ngpt_forum extends plugin_ngpt
{

    /**
     * 浏览页面时向页面标题旁输出内容
     * @param array $params
     * @return string
     */
    function viewthread_title_extra($params)
    {
        return '';
    }

    /**
     * 用户信息栏项底部
     * @return array
     */
    function viewthread_sidebottom()
    {
        return [];
        global $_G;
        $up = $_G['user_info']['stat_up'];
        $down = $_G['user_info']['stat_down'];
        $up = PTHelper::getReadableFileSize($up);
        $down = PTHelper::getReadableFileSize($down);

        $script = <<<HTML
        <dl class="pil cl">
        <dt>统计上传</dt><dd>$up</dd>
        <dt>统计下载</dt><dd>$down</dd>
        </dl>
        <br/>
HTML;
        return array($script);
    }

    /**
     * @param array $params
     * @return string
     */
    function viewthread_modoption($params)
    {
        return [];
    }
}
