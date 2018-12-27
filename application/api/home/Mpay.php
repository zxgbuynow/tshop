<?php
namespace app\api\home;
use app\common\controller\Common;

use \think\Request;
use \think\Db;
use think\Model;
use think\helper\Hash;
use think\Loader;
Loader::import('malipay.wappay.service.AlipayTradeService',EXTEND_PATH,'.php');
Loader::import('malipay.wappay.buildermodel.AlipayTradeWapPayContentBuilder',EXTEND_PATH,'.php');

/**
 * pay控制器
 * @package app\index\controller
 */
class Mpay extends Common
{
    public function dopay()
    {
        $config = array (
            //应用ID,您的APPID。
            'app_id' => "2018032802460598",
            //商户私钥，您的原始格式RSA私钥
            'merchant_private_key' => "MIIEowIBAAKCAQEA3LuRUFx2BL/nXp8fUd4obPzOIvr8ihn8yqiBYoR3CK3oD1uM
1P4m5SqPA70lYNbaCRsFIUg4CuGk/R0QDmP6mBY4H/spyrYUYbfFksUf1k9ZHz9n
UB/TIKdRTOsGFC6rs7eCMX4mH8KC4AqPlzl+R9QvTWMxgpvngxKzja+uXt//3C07
y/CWmA7RtHScedYKKysFid3s7w78VFFwZ6mQ9jCus9EaCFYg/WBOoVMNKE0cDlgY
Lf4yw3uba4oGle6LaVgNMm4zwe09JmlpmeLstf8U7N7TsB95Rv6CfrFC7lUlwolu
bfyccUeBeTVBxc2RVRMCNfomlkHP6QP6Kqy+gwIDAQABAoIBAQCbVAqwCECsmvaP
6V262KCOAWB10TUBYcQ4QFe8Igc5vlc852m1QJvSTB6TvPieqtKH8dGsWAvH7H2T
+G2iEsz3VTttlesU0QiKsy1/WORMhU3r5UwoYBzML7HQdNq0PRtqvkoJ1gGx+8jp
K8Vb34NzvGcFCDaA+ID0BacAn44PCDcv0+OMnF6ipNK7bOJoN+QkEdkadoz1mO/+
seGUoie6djERIqbRnIr3sP3U5ZYvLBto0EuBLblMJd1GLUmJfuVYik1weGyBsuEk
/kTFcNZCwlexc7gQac9LNkBIlS0uUomIcnuePAkXVFOZW3FwouWUYklVLbbKq5fE
vyJzbvpxAoGBAPkzzFGP3yIEd4DFzaiYOKgRv05Kh6z8+L9zm20KOW3rRoXh3DqE
GNjyKKm00hJ0Pw6UhrHk2ctefVX7KhUQaNPLuijBn4bapkgBmpqdCwzJZpSc7BDY
sYhlBfs6PNC5KfqJB5rBzC7XfDyWueYIaW6+YWJGxbFc3Db9ez+weKstAoGBAOLA
9qOwpnhSx+baBt/elRDa+8HWQkIknMjgH47TLP2A5Ub3N1zsPCVTormptOLUdbsH
Ed3phnyte4mutYqGPdao7n4e6LFmJ1tTrv3cmviVea3/fAzyQ3pDg98wtR3HtHTY
64c4dtzYi6ZUDJFIutzFvqNHb/ycRMy7IrplgF5vAoGAfzkUjGc48TL6l4FkdzgP
ZK56zkt6bLRRgdxRcx+PJjDBNkSSnEUoMkmevNUVklpKfvUQtu0wy4SX1Dd/ynUw
L0CI75m6CazCy2wWM+0M4SBJAIIEeq1GJW392b5nod+GMOOYQEfEJ/3W7U+95FRT
Dziemv+qmdvgiSprq546XJkCgYBk5SmZkluwRF1Qegj/CgJYGqhVCqo21iWxOBCy
s4JcVkMuvYez4CWvEjTg2gNzvseX7cBkdqlxxpummseKmMrhPg/IrKYrcWHnwCeo
K8YFADXBV2HyPMYLnAkMgZbFZnwEVhUO+O/iurQA0Xs6FhuXaqG1825//2SZmFcO
i2WAHwKBgBs6+n19m4o0A/u1GIlbUI+T0OC/BuN4D4M5xil/K0fKTM1LBlfrvAlf
25vscHdO4jReAdSyyeC/YC3Dw25iW4X/rUiowzdZUoU0FrRGDxgscJwwIIltnlPi
CPALTKotDuQ30dilaQ85V/otiACFF+raOTTGYePigun2ZdWheEbJ",

            //异步通知地址
            'notify_url' =>  'http://'.$_SERVER['HTTP_HOST'].url('Mpay/callback'),

            //同步跳转
            'return_url' =>  'http://'.$_SERVER['HTTP_HOST'].url('Mpay/callback1'),

            //编码格式
            'charset' => "UTF-8",

            //签名方式
            'sign_type'=>"RSA2",

            //支付宝网关
            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
            'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA3LuRUFx2BL/nXp8fUd4o
bPzOIvr8ihn8yqiBYoR3CK3oD1uM1P4m5SqPA70lYNbaCRsFIUg4CuGk/R0QDmP6
mBY4H/spyrYUYbfFksUf1k9ZHz9nUB/TIKdRTOsGFC6rs7eCMX4mH8KC4AqPlzl+
R9QvTWMxgpvngxKzja+uXt//3C07y/CWmA7RtHScedYKKysFid3s7w78VFFwZ6mQ
9jCus9EaCFYg/WBOoVMNKE0cDlgYLf4yw3uba4oGle6LaVgNMm4zwe09JmlpmeLs
tf8U7N7TsB95Rv6CfrFC7lUlwolubfyccUeBeTVBxc2RVRMCNfomlkHP6QP6Kqy+
gwIDAQAB",


        );

        $request = Request::instance();
        $params = $request->param();

        $where['tid'] = $params['payment_id'];
        //查询订单信息
        $payment = db('trade')->where($where)->find();

        //优惠券
        $price =0;
        //查询订单信息
        if (isset($params['couponid'])) {
            $price = db('cms_coupon')->where(['id'=>$params['couponid']])->value('price');
            $data['coupon'] = $price;
            $data['couponid'] = $params['couponid'];
            $data['payment'] = floatval($payment['payment'])- floatval($price)<0?'0.01':floatval($payment['payment'])- floatval($price);
            $payment['payment'] = $data['payment'];
            db('trade')->where(['tid'=>$params['payment_id']])->update($data);
            // db('cms_coupon')->where(['id'=>$params['couponid']])->update(['use'=>1]);
        }
        

        //商户订单号，商户网站订单系统中唯一订单号，必填
        
        $out_trade_no = $payment['tid'];

        //订单名称，必填
        $subject = $payment['title'].'...';

        //付款金额，必填
        $total_amount = number_format($payment['payment'],2,".","");
        // $total_amount = 0.01;

        //商品描述，可空
        $body = $payment['title'].'...';

        //超时时间
        $timeout_express="1m";
        $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setTimeExpress($timeout_express);

        $payResponse = new \AlipayTradeService($config);
        $result = $payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);

        return ;
    }

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback()
    {

        $request = Request::instance();
        $params = $request->param();
        if (isset($params['trade_status'])&&$params['trade_status']=='TRADE_SUCCESS') {
            $where['tid'] = $params['out_trade_no'];
            $data['status'] = 1;
            $data['pay_type'] = 'alipay';
            $data['buyer_email'] = $params['buyer_logon_id'];
            $data['trade_no'] = $params['trade_no'];

            //是否是充值订单
            $info = db('trade')->where($where)->find();
            if ($info['paytype']==1) {
                //是否注册
                if ($info['username']) {
                    if (db('member')->where(['username'=>$info['username']])->find()) {
                        //周年会员
                        if ($info['payment']==7) {
                            $indata['vipday'] = 1;
                        }else{
                            $indata['vipday'] = 12;
                        }
                        $indata['is_diamonds'] = 1;
                        $indata['viptime'] = time();
                        db('member')->where(['username'=>$info['username']])->update($indata);
                    }else{
                        //支付日志表
                        $sdata['memberid'] = $info['memberid'];
                        $sdata['create_time'] = time();
                        $sdata['account'] = $info['username'];
                        $sdata['vip'] = $info['payment']==7?1:12;
                        db('vip_log')->insert($sdata);
                    }   
                }else{
                    //周年会员
                    if ($info['payment']==7) {
                        $indata['vipday'] = 1;
                    }else{
                        $indata['vipday'] = 12;
                    }
                    $indata['is_diamonds'] = 1;
                    $indata['viptime'] = time();
                    $indata['viplastt'] = $indata['vipday']==12?30879000:604800;
                    db('member')->where(['id'=>$info['memberid']])->update($indata);
                }
                
            }

            if ($info['paytype']==2) {
                db('cms_classes')->where(['id'=>$info['classid']])->setInc('num');
            }

            if ($info['paytype']==3) {
                db('cms_active')->where(['id'=>$info['classid']])->setInc('num');
            }
            db('cms_coupon')->where(['id'=>$info['couponid']])->update(['use'=>1]);
            db('trade')->where($where)->update($data);//修改订单状态
            db('msg')->where(['tid'=>$where['tid']])->update(['is_pay'=>1]);//修改订单状态
            
            // db('trade')->where($where)->update($data);//修改订单状态
            echo 'success';
            exit;
        }
        
    }
    public function callback1()
    {

        $request = Request::instance();
        $params = $request->param();
        header('Location:http://'.$_SERVER['HTTP_HOST'].'/mobile.php/Member/trade.html');
        // $this->redirect(url('mobile/Member/trade'));
        // error_log(json_encode($params),3,'/home/wwwroot/daguan/pay.log');
        
    }
    
}    