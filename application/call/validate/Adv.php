<?php
namespace app\call\validate;

use think\Validate;
use Cron\CronExpression;

/*
 * 定时任务验证器
 */
class Adv extends Validate
{
    // 定义验证规则
    protected $rule = [
        'title|标题' => 'require',
        'content|内容' => 'require',
    ];

    // 定义验证提示
    protected $message = [
        'title.require' => '标题不能为空',
        'content.require' => '内容不能为空',
    ];

}