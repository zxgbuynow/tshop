<?php


namespace app\cms\model;

use think\Model;
use think\helper\Hash;
use think\Db;

/**
 * 机构模型
 * @package app\admin\model
 */
class CmsReply extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CMS_REPLY__';

     // 自动写入时间戳
    protected $autoWriteTimestamp = true;


    public  function getClactitleAttr($v,$data)
    {   

       if ($data['type']==0) {//课程
            return db('cms_classes')->where(['id'=>$data['classid']])->value('title');
       }else{
            return db('cms_active')->where(['id'=>$data['classid']])->value('title');
       }
    }

    public  function getSunameAttr($v,$data)
    {   

       return db('member')->where(['id'=>$data['suid']])->value('nickname');
    }

    public  function getRunameAttr($v,$data)
    {   

       return db('member')->where(['id'=>$data['ruid']])->value('nickname');
    }
    

}
