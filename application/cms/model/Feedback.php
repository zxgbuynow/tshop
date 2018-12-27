<?php


namespace app\cms\model;

use think\Model as ThinkModel;

/**
 * notice模型
 * @package app\cms\model
 */
class Feedback extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CMS_FEEDBACK__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public  function getUsernameAttr($v,$data)
    {
       return db('member')->where(['id'=>$data['uid']])->value('nickname');
    }

    public  function getStatusxtAttr($v,$data)
    {
       
       return $data['status'];
    }

}