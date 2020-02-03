<?php
namespace app\call\model;

use think\Model;

/**
 * 品牌模型
 */
class Projectst extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CALL_PROJECT_ST__';

    public function getProjectAttr($value, $data){
        if (!$data['project_id']) {
            return '';
        }
        
        return db('call_project_list')->where(['id'=>$data['project_id']])->value('col1');
    }
    
}