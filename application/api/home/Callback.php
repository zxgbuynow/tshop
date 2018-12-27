<?php
namespace app\api\home;

use \think\Request;
use \think\Db;
use think\Model;
use think\helper\Hash;

/**
 * ali控制器
 * @package app\index\controller
 */
class Callback
{
    /*
     * 支付宝支付回调修改订单状态
     */
    public function aliPayBack()
    {

        if ($_POST['trade_status'] == 'TRADE_SUCCESS') {//如果支付成功
            //===============修改订单状态===========================//
            $order = new OrderGoods();//实例化
            $orderSn = $_POST['out_trade_no'];//获取订单号
                $where['order_sn'] = $orderSn;
                $data1['type'] = 2;
            $order->where($where)->update($data1);//修改订单状态
            echo 'success';
            exit;
        }
    }
}    