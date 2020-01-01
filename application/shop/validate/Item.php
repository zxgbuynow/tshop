<?php
namespace app\shop\validate;

use think\Validate;
use Cron\CronExpression;

/*
 * 定时任务验证器
 */
class Item extends Validate
{
    // 定义验证规则
    protected $rule = [
        'cat_id|分类' => 'require|number|min:0',
        'title|商品名' => 'require',
        'sub_title|商品子标题' => 'require',
        'bn|分类图标' => 'require',
        'brand_id|分类标题' => 'require',
        'list_image|品牌' => 'require',
    ];

    // 定义验证提示
    protected $message = [
        'cat_id.require' => '分类不能为空',
        'title.require' => '分类标题不能为空',
        'list_image.require' => '商品图片不能为空',
        'bn.require' => '编码不能为空',
        'sub_title.require' => '商品子标题不能为空',
        'brand_id.require' => '品牌不能为空',
    ];

}