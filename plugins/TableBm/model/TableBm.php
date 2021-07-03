<?php


namespace plugins\TableBm\model;

use app\common\model\Plugin;

/**
 * 后台插件模型
 * @package plugins\HelloWorld\model
 */
class TableBm extends Plugin
{
    // 设置当前模型对应的完整数据表名称
    protected $name = 'admin_plugin_form';

    protected $table = '__ADMIN_PLUGIN_FORM__';

    public function test()
    {
        // 获取插件的设置信息
        halt('test');
    }
}
