<?php
namespace app\api\home;

use \think\Request;
use \think\Db;
use think\Model;
use think\helper\Hash;

/**
 * 前台首页控制器
 * @package app\index\controller
 */
class Pay
{
    public function index()
    {
        //获取订单号
        $where['id'] = input('post.order_sn');
        $reoderSn = input('post.order_sn');
        //查询订单信息
        $order_info = db('trade')->where($where)->find();
        //获取支付方式
        $pay_type = input('post.pay_type');//微信支付 或者支付宝支付
        //获取支付金额
        $money = input('post.totle_sum');
        //判断支付方式
        switch ($pay_type) {
            case 'ali';//如果支付方式为支付宝支付

                //更新支付方式为支付宝
                $type['pay_type'] = 'ali';
                $res->where($where)->update($type);

                //实例化alipay类
                $ali = new Alipay(); 

                //异步回调地址
                $url = url('Callback/aliPayBack');
             
                $array = $ali->alipay($order_info['title'], $money, $reoderSn, $url);
                
                if ($array) {
                    return $array;
                } else {
                    echo json_encode(array('status' => 0, 'msg' => '对不起请检查相关参数!@'));
                }
                break;
            case 'wx';
                
                break;
        }
    }
}    