<?php
namespace app\call\model;

use think\Model;

/**
 * 品牌模型
 */
class Custom extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CALL_CUSTOM__';

    protected $createTime = false;
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    
    public function getProjectAttr($value, $data){
        if (!$data['project_id']) {
            return '';
        }
        
        return db('call_project_list')->where(['id'=>$data['project_id']])->value('col1');
    }

    public function getCategorysAttr($value, $data){
        if (!$data['category']) {
            return '';
        }
        
        return db('call_custom_cat')->where(['id'=>$data['category']])->value('title');
    }

    public function getEmployAttr($value, $data){
        if (!$data['id']) {
            return '';
        }
        //取最后一个修改人
        $user = db('call_report_custom_cat')->where(['custom_id'=>$data['id']])->order('id desc')->value('employ_id');
        if (!$user) {
        	return '';
        }
        return  db('admin_user')->where(['id'=>$user])->value('nickname').'等';
    }

}