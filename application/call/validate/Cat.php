<?php
namespace app\call\validate;

use think\Validate;
use Cron\CronExpression;

/*
 * 定时任务验证器
 */
class Cat extends Validate
{
    // 定义验证规则
    protected $rule = [
        'pid|父级分类' => 'require|number|min:0',
        'level|父级' => 'require|in:1,2,3',
        'title|分类标题' => 'require',
        'cat_logo|分类图标' => 'require',
    ];

    // 定义验证提示
    protected $message = [
        'pid.require' => '父级分类不能为空',
        'title.require' => '分类标题不能为空',
        'cat_logo.require' => '分类图标不能为空',
        'level.in' => '父级不能为3级',
    ];

}