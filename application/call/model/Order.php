<?php
namespace app\call\model;

use think\Model;

/**
 * 品牌模型
 */
class Order extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CALL_ORDER__';

    public function getItemAttr($value, $data){
        if (!$data['item_id']) {
            return '';
        }
        
        return db('call_item')->where(['id'=>$data['item_id']])->value('title');
    }

    public function getTradeAttr($value, $data){
        if (!$data['trade_id']) {
            return '';
        }
        
        return db('call_trade')->where(['id'=>$data['trade_id']])->value('title');
    }
}