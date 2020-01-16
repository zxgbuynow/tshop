<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

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

    public function test()
    {
        // 获取插件的设置信息
        halt('test');
    }
}
