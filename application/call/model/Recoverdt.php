<?php
namespace app\call\model;

use think\Model;

/**
 * 品牌模型
 */
class Recoverdt extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CALL_RECOVER_DATA__';

    public function getProjectAttr($value, $data){
        if (!$data['project_id']) {
            return '';
        }
        
        return db('call_project_list')->where(['id'=>$data['project_id']])->value('col1');
    }

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
        
        return db('admin_user')->where(['id'=>$data['user_id']])->value('nickname');
    }
}