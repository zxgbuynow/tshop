<?php


namespace app\cms\model;

use think\Model as ThinkModel;

/**
 * 评价模型
 * @package app\cms\model
 */
class Evaluate extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__EVALUATE__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public  function getUsernameAttr($v,$data)
    {
       return db('member')->where(['id'=>$data['memberid']])->value('nickname');

    }
}