<?php
namespace app\call\model;

use think\Model;

/**
 * 品牌模型
 */
class Cat extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CALL_CUSTOM_CAT__';

    // public function getEmployAttr($value, $data){
    //     if (!$data['employ_id']) {
    //         return '';
    //     }
        
    //     return db('admin_user')->where(['id'=>$data['employ_id']])->value('nickname');
    // }

    // public function getCustomAttr($value, $data){
    //     if (!$data['custom_id']) {
    //         return '';
    //     }
        
    //     return db('call_custom')->where(['id'=>$data['custom_id']])->value('name');
    // }

}