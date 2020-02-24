<?php
namespace app\call\model;

use think\Model;

/**
 * 品牌模型
 */
class Calllog extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CALL_LOG__';

    public function getUserAttr($value, $data){
        if (!$data['user_id']) {
            return '';
        }
        
        return db('admin_user')->where(['id'=>$data['user_id']])->value('nickname');
    }
}