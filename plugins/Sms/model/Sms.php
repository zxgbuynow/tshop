<?php

namespace plugins\Sms\model;

use app\common\model\Plugin;

/**
 * 后台插件模型
 * @package plugins\Sms\model
 */
class Sms extends Plugin
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__PLUGIN_SMS__';

    /**
     * 获取模板数据
     * @param string $title 模板名称
     * @author 蔡伟明 <314013107@qq.com>
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public static function getTemplate($title = '')
    {
        return self::where('title', $title)->find();
    }
}