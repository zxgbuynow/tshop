<?php
namespace app\call\model;

use think\Model;

/**
 * 品牌模型
 */
class Ondate extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CALL_ONDATE__';

    public function getCustomAttr($value, $data){
        if (!$data['custom_id']) {
            return '';
        }
        
        return db('call_custom')->where(['id'=>$data['custom_id']])->value('name');
    }

    public function getUserAttr($value, $data){
        if (!$data['user_id']) {
            return '';
        }
        
        return db('admin_user')->where(['id'=>$data['user_id']])->value('username');
    }
    
}
