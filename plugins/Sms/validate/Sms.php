<?php

namespace plugins\Sms\validate;

use think\Validate;

/**
 * 后台插件验证器
 * @package app\plugins\Sms\validate
 */
class Sms extends Validate
{
    //定义验证规则
    protected $rule = [
        'title|模板名称' => 'require|unique:plugin_sms',
        'code|模板ID'  => 'require|unique:plugin_sms',
    ];
}
