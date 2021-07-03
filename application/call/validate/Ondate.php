<?php
namespace app\call\validate;

use think\Validate;
use Cron\CronExpression;

/*
 * 定时任务验证器
 */
class Ondate extends Validate
{
    // 定义验证规则
    protected $rule = [
        'custom_id|客户' => 'require',
        'ondate|预约时间' => 'require',
        'sign_time|录入时间' => 'require',
    ];

    // 定义验证提示
    protected $message = [
        'custom_id.require' => '客户不能为空',
        'ondate.require' => '预约时间不能为空',
        'sign_time.require' => '录入时间不能为空',
    ];

}