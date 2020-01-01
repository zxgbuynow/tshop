<?php
namespace app\shop\validate;

use think\Validate;

/*
 * 定时任务验证器
 */
class PropValues extends Validate
{
    // 定义验证规则
    protected $rule = [
        'prop_value|属性值' => 'require',
    ];

    // 定义验证提示
    protected $message = [
        'prop_value.require' => '属性值不能为空',
    ];

}