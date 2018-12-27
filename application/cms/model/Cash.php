<?php


namespace app\cms\model;

use think\Model as ThinkModel;

/**
 * 菜单模型
 * @package app\cms\model
 */
class Cash extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CASH__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    static  function getOperAttr($v,$data)
    {
      if (!$data['opuid']) {
        return '未操作';
      }
      if ($data['opuid']==1) {
        return '管理员';
      }
      return db('admin_user')->where(['id'=>$data['opuid']])->value('nickname');

    }
    

}