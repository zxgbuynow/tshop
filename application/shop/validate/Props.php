<?php
namespace app\shop\validate;

use think\Validate;
use Cron\CronExpression;

/*
 * 定时任务验证器
 */
class Props extends Validate
{
    // 定义验证规则
    protected $rule = [
        'prop_name|品牌名称' => 'require',
    ];

    // 定义验证提示
    protected $message = [
        'prop_name.require' => '品牌名称不能为空',
    ];

}