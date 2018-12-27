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
class AliPay
{
    // protected $appId = '';//支付宝AppId
    // protected $rsaPrivateKey = '';//支付宝私钥
    // protected $aliPayRsaPublicKey = '';//支付宝公钥
    // private $seller = '';

    /*
     * 支付宝支付
     */
    public function aliPay()
    {
        echo 'http://'.$_SERVER['HTTP_HOST'].'/mobile.php/Member/trade.html';
        header('Location:http://'.$_SERVER['HTTP_HOST'].'/mobile.php/Member/trade.html');
        /**
         * 调用支付宝接口。
         */
        
        // Loader::import('Alipay\aop\AopClient', EXTEND_PATH);
        // Loader::import('Alipay\aop\request\AlipayTradeAppPayRequest', EXTEND_PATH);

        // $aop = new \AopClient();

        // $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        // $aop->appId = $this->appId;
        // $aop->rsaPrivateKey = $this->rsaPrivateKey;
        // $aop->format = "json";
        // $aop->charset = "UTF-8";
        // $aop->signType = "RSA2";
        // $aop->alipayrsaPublicKey = $this->aliPayRsaPublicKey;
        // $request = new \AlipayTradeAppPayRequest();
        // $arr['body'] = $body;
        // $arr['subject'] = $body;
        // $arr['out_trade_no'] = $product_code;
        // $arr['timeout_express'] = '30m';
        // $arr['total_amount'] = floatval($total_amount);
        // $arr['product_code'] = 'QUICK_MSECURITY_PAY';
        
        // $json = json_encode($arr);
        // $request->setNotifyUrl($notify_url);
        // $request->setBizContent($json);

        // $response = $aop->sdkExecute($request);
        // return $response;

    }


    // function createLinkstring($para)
    // {
    //     $arg = "";
    //     while (list ($key, $val) = each($para)) {
    //         $arg .= $key . "=" . $val . "&";
    //     }
    //     //去掉最后一个&字符
    //     $arg = substr($arg, 0, count($arg) - 2);

    //     //如果存在转义字符，那么去掉转义
    //     if (get_magic_quotes_gpc()) {
    //         $arg = stripslashes($arg);
    //     }

    //     return $arg;
    // }


    // function argSort($para)
    // {
    //     ksort($para);
    //     reset($para);
    //     return $para;
    // }
}    