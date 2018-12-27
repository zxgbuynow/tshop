<?php


namespace app\cms\model;

use think\Model;
use think\helper\Hash;
use think\Db;

/**
 * 后台用户模型
 * @package app\admin\model
 */
class Counsellor extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__MEMBER__';

     // 自动写入时间戳
    protected $autoWriteTimestamp = true;


    static function getCounsellorList($id)
    {
        $counsellor =  db('member')->alias('a')->field('a.*,b.*,b.id as bid,a.id as aid')->join(' member_counsellor b',' b.memberid = a.id','LEFT')->where(array('a.id'=>$id))->find();

        return $counsellor;
    }

    
    static  function getIncomeAttr($v,$data)
    {
        return number_format(db('trade')->where(['mid'=>$data['id'],'status'=>1])->sum('payment'),1);
    }

    static  function getTotalgetAttr($v,$data)
    {
        $t = number_format(db('cash')->where(['cid'=>$data['id'],'status'=>1])->sum('payment'),1);
        return $t;
    }

    static  function getTotalcashAttr($v,$data)
    {
        $g = number_format(db('cash')->where(['cid'=>$data['id'],'status'=>1])->sum('payment'),1);
        $t =number_format(db('trade')->where(['mid'=>$data['id'],'status'=>1])->sum('payment'),1);

        return number_format(($t-$g),1);


    }
    static  function getVerifystatusAttr($v,$data)
    {
        return $data['status'];
    }

    static  function getAgencyAttr($v,$data)
    {
        $map['id'] = $data['shopid'];
        return db('shop_agency')->where($map)->value('title');

    }

    static  function getOffnumAttr($v,$data)
    {
        $map['chart'] = 'facechart';
        $map['status'] = 1;
        $map['mid'] = $data['id'];
        return db('trade')->where($map)->count();

    }

    static  function getOffincomeAttr($v,$data)
    {
        $map['chart'] = 'facechart';
        $map['status'] = 1;
        $map['mid'] = $data['id'];
        return number_format(db('trade')->where($map)->sum('payment'),1);

    }

    static  function getWordnumAttr($v,$data)
    {
        $map['chart'] = 'wordchart';
        $map['status'] = 1;
        $map['mid'] = $data['id'];
        return db('trade')->where($map)->count();

    }

    static  function getWordincomeAttr($v,$data)
    {
        $map['chart'] = 'wordchart';
        $map['status'] = 1;
        $map['mid'] = $data['id'];
        return number_format(db('trade')->where($map)->sum('payment'),1);

    }

    static  function getVoicenumAttr($v,$data)
    {
        $map['chart'] = 'speechchart';
        $map['status'] = 1;
        $map['mid'] = $data['id'];
        return db('trade')->where($map)->count();

    }

    static  function getVoiceincomeAttr($v,$data)
    {
        $map['chart'] = 'speechchart';
        $map['status'] = 1;
        $map['mid'] = $data['id'];
        return number_format(db('trade')->where($map)->sum('payment'),1);

    }

    static  function getTalkincomeAttr($v,$data)
    {
        $map['paytype'] = 0;
        $map['status'] = 1;
        $map['mid'] = $data['id'];
        return number_format(db('trade')->where($map)->sum('payment'),1);

    }

    static  function getClassnumeAttr($v,$data)
    {
        $map['adminid'] = $data['id'];
        return db('shop_classes_allot')->alias('a')->join('cms_classes b',' b.id = a.classid','LEFT')->where($map)->count();

    }

    static  function getClassincomeAttr($v,$data)
    {
        // $map['mid'] = $data['id'];
        // return db('shop_classes_allot')->alias('a')->join('cms_classes b',' b.id = a.classid','LEFT')->where($map)->count();
        return 0;

    }

    static  function getActivenumAttr($v,$data)
    {
        $map['adminid'] = $data['id'];
        return db('shop_classes_allot')->alias('a')->join('cms_active b',' b.id = a.classid','LEFT')->where($map)->count();

    }

    static  function getActiveincomeAttr($v,$data)
    {
        // $map['mid'] = $data['id'];
        // return db('shop_classes_allot')->alias('a')->join('cms_classes b',' b.id = a.classid','LEFT')->where($map)->count();
        return 0;

    }

    static  function getClacincomeAttr($v,$data)
    {
        // $map['mid'] = $data['id'];
        // return db('shop_classes_allot')->alias('a')->join('cms_classes b',' b.id = a.classid','LEFT')->where($map)->count();
        return 0;

    }

    static  function getTotalAttr($v,$data)
    {
        // $map['mid'] = $data['id'];
        // return db('shop_classes_allot')->alias('a')->join('cms_classes b',' b.id = a.classid','LEFT')->where($map)->count();
        return 0;

    }


    static  function getOndatenumsAttr($v,$data)
    {
        $tids = db('trade')->where(['mid'=>$data['id'],'paytype'=>0])->column('id');
        $map['tid'] = array('in',$tids);
        $num =  db('calendar')->where($map)->count();
        if ($num>0) {
            db('member')->where(['id'=>$data['id']])->update(['ondatenum'=>$num]);
        }
        
        return $num;
    }

}
