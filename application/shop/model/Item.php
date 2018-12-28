<?php
namespace app\shop\model;

use think\Model;
use Cron\CronExpression;

/**
 * 定时任务模型
 */
class Item extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CRONTAB__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public function getCatAttr($value, $data){
        if (!$data['cat_id']) {
            return '';
        }
        return db('cat')->where(['cat_id'=>$data['cat_id']])->value('cat_name');
    }

    public function getBrandAttr($value, $data){
        if (!$data['brand_id']) {
            return '';
        }
        return db('brand')->where(['brand_id'=>$data['brand_id']])->value('brand_name');
    }

    public function getStoreAttr($value, $data){
        if (!$data['item_id']) {
            return '';
        }
        return db('sku_store')->where(['item_id'=>$data['item_id']])->sum('store');
    }

}