<?php


namespace app\cms\model;

use think\Model as ThinkModel;

/**
 * 菜单模型
 * @package app\cms\model
 */
class Cards extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CARDS__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    static  function getMemberAttr($v,$data)
    {
      if (!$data['memberid']) {
        return '未分配';
      }
      return db('member')->where(['id'=>$data['memberid']])->value('nickname');

    }

    static  function getVipAttr($v,$data)
    {
       $viparr = array(
        '0'=>'普通会员',
        '1'=>'周会员',
        '2'=>'年会员'
       );
       $viparr = array('普通会员','周会员','年会员');
       $ret = [];
       $vp = explode(',', $data['mvip']);
       foreach ($viparr as $key => $value) {
        if (in_array($key, $vp)) {
          $ret[] = $value;
        }
        
       }
       return implode('|', $ret);

    }
    

}