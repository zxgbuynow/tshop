<?php
namespace app\call\model;

use think\Model;

/**
 * 品牌模型
 */
class Alloc extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CALL_ALLOC__';

    public function getOperAttr($value, $data){
        if (!$data['op_id']) {
            return '';
        }
        
        return db('admin_user')->where(['id'=>$data['op_id']])->value('username');
    }
}