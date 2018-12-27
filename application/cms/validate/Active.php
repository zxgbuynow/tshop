<?php


namespace app\cms\validate;

use think\Validate;

/**
 * 菜单验证器
 * @package app\cms\validate
 */
class Active extends Validate
{
    //定义验证规则
    protected $rule = [
        'title|属性名'      => 'require',
        'cateid|分类'      => 'require',
        'price|金额'      => 'require',
        'limitnum|参与人数'      => 'require',
        'start_time|开始时间'      => 'require',
        'endtime|结束时间'      => 'require',
    ];

    //定义验证提示
    protected $message = [
        'title.require' => '标题不能为空',
        'cateid.require' => '分类不能为空',
        'price.require' => '金额不能为空',
        'limitnum.require' => '参与人数不能为空',
        'start_time.require' => '开始时间不能为空',
        'endtime.require' => '结束时间不能为空',
    ];
}
