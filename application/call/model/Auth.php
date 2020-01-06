<?php
namespace app\call\model;

use think\Model;

/**
 * 品牌模型
 */
class Auth extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CALL_AUTH__';

    public function getIsonlineAttr($value, $data){
        // if (!$data['online']) {
        //     return '';
        // }
        if ($data['online']=='1') {
        	return '线上';
        }
        return '线下';
    }
}