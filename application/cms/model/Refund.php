<?php


namespace app\cms\model;

use think\Model as ThinkModel;

/**
 * 菜单模型
 * @package app\cms\model
 */
class Refund extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__REFUND__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    static  function getMemberAttr($v,$data)
    {
       return db('member')->where(['id'=>$data['memberid']])->value('nickname');

    }

}