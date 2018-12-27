<?php


namespace app\cms\model;

use think\Model;
use think\helper\Hash;
use think\Db;

/**
 * 机构模型
 * @package app\admin\model
 */
class Casetab extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CASE__';

     // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public  function getUsernameAttr($v,$data)
    {
       return db('member')->where(['id'=>$data['memberid']])->value('nickname');

    }
    

}
