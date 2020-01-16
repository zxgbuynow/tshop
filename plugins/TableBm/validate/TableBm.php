<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2019 广东卓锐软件有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------

namespace plugins\TableBm\validate;

use think\Validate;

/**
 * 后台插件验证器
 * @package app\plugins\TableBm\validate
 */
class TableBm extends Validate
{
    // 定义验证规则
    protected $rule = [
        'name|出处' => 'require',
        'said|名言' => 'require',
    ];
}
