<?php


namespace app\cms\model;

use think\Model;
use think\helper\Hash;
use think\Db;

/**
 * 机构模型
 * @package app\admin\model
 */
class Trade extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__TRADE__';

     // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    public  function getUsernameAttr($v,$data)
    {
       // return 68788878;
       return db('member')->where(['id'=>$data['memberid']])->value('nickname');
    }

    public  function getMidrawAttr($v,$data)
    {
       // return 68788878;
       return db('member')->where(['id'=>$data['mid']])->value('nickname');
    }

    public  function getShopidrawAttr($v,$data)
    {
       // return 68788878;
       return db('shop_agency')->where(['id'=>$data['shopid']])->value('title');
    }

    public  function getClassidrawAttr($v,$data)
    {
       // return 68788878;
      if ($data['paytype']=='2') {
         return db('cms_classes')->where(['id'=>$data['classid']])->value('title');
      }

      if ($data['paytype']=='3') {
         return db('cms_active')->where(['id'=>$data['classid']])->value('title');
      }
       
      if ($data['paytype']<2) {
        return '';
      }
    }

    public  function getOndateAttr($v,$data)
    {
       return db('calendar')->where(['tid'=>$data['id']])->order('id DESC')->value('start_time');
    }

    public  function getProcessAttr($v,$data)
    {
       return db('calendar')->where(['tid'=>$data['id']])->count().'/'.$data['num'];
    }

    public  function getPaytyperawAttr($v,$data)
    {
      switch ($data['paytype']) {
        case '1':
          return '冲值订单';
          break;
        case '2':
          return '课程订单';
          break;
        case '3':
          return '活动订单';
          break;
        
        default:
          return '预约订单';
          break;
      }
    }

    public  function getChartrawAttr($v,$data)
    {
      switch ($data['chart']) {
        case 'wordchart':
          return '文字咨询';
          break;
        case 'speechchart':
          return '语音咨询';
          break;
        case 'facechart':
          return '面对面咨询';
          break;
        
        default:
          return '视频咨询';
          break;
      }
    }


}
