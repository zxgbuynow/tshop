<?php
namespace app\call\model;

use think\Model;

/**
 * 品牌模型
 */
class Trade extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CALL_TRADE__';

    public function getProjectAttr($value, $data){
        if (!$data['project_id']) {
            return '';
        }
        
        return db('call_project_list')->where(['id'=>$data['project_id']])->value('col1');
    }
    public function getSigncityAttr($value, $data){
        if (!$data['sign_area_city']) {
            return '';
        }
        $p = db('packet_common_area')->where(['area_code'=>$data['sign_area_province']])->value('area_name');
        $c = db('packet_common_area')->where(['area_code'=>$data['sign_area_city']])->value('area_name');
        $a = db('packet_common_area')->where(['area_code'=>$data['sign_area_area']])->value('area_name');
        return $p.' '.$c.' '.$a;
    }

    public function getStatustxAttr($value, $data){
        if (!$data['status']) {
            return '';
        }
        
        switch ($data['status']) {
        	case 'progress':
        		return '进行';
        		break;
        	case 'end':
        		return '人工结束';
        		break;
        	
        	default:
        		return '完成';
        		break;
        }
    }
}