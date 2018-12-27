<?php
namespace app\api\home;

use \think\Request;
use \think\Db;
use think\Model;
use think\helper\Hash;

/**
 * pay控制器
 * @package app\index\controller
 */
class Pay
{
    /**
     * @var string 支付方式名称
     */
    public $name = '支付宝支付手机APP支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '支付宝支付手机APP接口';
     /**
     * @var string 支付方式key
     */
    public $app_key = 'alipayApp';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'alipayApp';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '支付宝（手机APP）';
    /**
     * @var string 货币名称
     */
    public $curname = 'CNY';
    /**
     * @var string 当前支付方式的版本号
     */
    public $ver = '1.0';
    /**
     * @var string 当前支付方式所支持的平台
     */
    public $platform = 'isapp';

    /**
     * @var array 扩展参数
     */
    public $supportCurrency = array("CNY"=>"01");

    public $mer_key = 'f403tsml33nuktahd70wph2kh9zj37p4';
    public $mer_id = '2088031695418481';
    public $seller_account_name = 'dg5889@dingtalk.com';
    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct(){
        
        $this->submit_charset = 'utf-8';
        $this->notify_url = 'http://'.$_SERVER['HTTP_HOST'].url('Pay/callback');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay()
    {
        $request = Request::instance();
        $params = $request->param();

        //优惠券
        $price =0;
        //查询订单信息
        $where['tid'] = $params['payment_id'];
        $payment = db('trade')->where($where)->find();
        if (isset($params['couponid'])) {
            $price = db('cms_coupon')->where(['id'=>$params['couponid']])->value('price');
            $data['coupon'] = $price;
            $data['couponid'] = $params['couponid'];
            $data['payment'] = floatval($payment['payment'])- floatval($price)<0?'0.01':floatval($payment['payment'])- floatval($price);
            $payment['payment'] = $data['payment'];
            db('trade')->where(['tid'=>$params['payment_id']])->update($data);
            
        }
        
        

        $mer_id = $this->mer_id;
        $mer_key = $this->mer_key;
        $seller_account_name = $this->seller_account_name;

        $payment['payment'] = 0.01;

        //if price
        // if ($price) {
        //     $payment['payment'] = floatval($payment['payment'])- floatval($price);
        // }

        $parameter = array(
            'service'        => 'mobile.securitypay.pay',                        // 必填，接口名称，固定值
            'partner'        => $mer_id,                            // 必填，合作商户号
            '_input_charset' => 'UTF-8',                                         // 必填，参数编码字符集
            'out_trade_no'   => $payment['tid'],                          // 必填，商户网站唯一订单号
            'subject'        => $payment['title'].'...',                    // 必填，商品名称
            'payment_type'   => '1',                                             // 必填，支付类型
            'seller_id'      => $seller_account_name,                                         // 必填，卖家支付宝账号
            'total_fee'      => number_format($payment['payment'],2,".",""),   // 必填，总金额，取值范围为[0.01,100000000.00]
            'body'           => $payment['title'].'...',                    // 必填，商品详情
            'notify_url'     => $this->notify_url,                               // 可选，服务器异步通知页面路径
        );

        //签名
        $orderInfo = $this->createLinkstring($parameter);
        $sign = $this->md5Sign($orderInfo, $mer_key);

        echo $orderInfo.'&sign="'.$sign.'"&sign_type="MD5"';
        exit;
    }

    /**
     * 校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad(){
        return true;
    }

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback()
    {
        #键名与pay_setting中设置的一致
        $mer_id = $this->mer_id;
        $mer_key = $this->mer_key;

        $request = Request::instance();
        $params = $request->param();
        if ($params['trade_status']=='TRADE_SUCCESS') {
            $where['tid'] = $params['out_trade_no'];
            $data['status'] = 1;
            $data['pay_type'] = 'alipay';
            $data['buyer_email'] = $params['buyer_email'];
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
            echo 'success';
            exit;
        }
        
    }

    /**
     * 检验返回数据合法性
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return boolean
     */
    public function is_return_vaild($form,$key)
    {
        ksort($form);
        foreach($form as $k=>$v){
            if($k!='sign'&&$k!='sign_type'){
                $signstr .= "&$k=$v";
            }
        }

        $signstr = ltrim($signstr,"&");
        $signstr = $signstr.$key;

        if($form['sign']==md5($signstr)){
            return true;
        }
        #记录返回失败的情况
        logger::error(app::get('ectools')->_('支付单号：') . $form['out_trade_no'] . app::get('ectools')->_('签名验证不通过，请确认！')."\n");
        logger::error(app::get('ectools')->_('本地产生的加密串：') . $signstr);
        logger::error(app::get('ectools')->_('支付宝传递打过来的签名串：') . $form['sign']);
        $str_xml .= "<alipayform>";
        foreach ($form as $key=>$value)
        {
            $str_xml .= "<$key>" . $value . "</$key>";
        }
        $str_xml .= "</alipayform>";

        return false;
    }

    // 对签名字符串转义
    public function createLinkstring($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key.'="'.$val.'"&';
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);
        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
        return $arg;
    }

    // 签名生成订单信息--MD5加密方式
    public function md5Sign($data, $key)
    {
        return md5($data.$key);
    }

    public function gen_form()
    {

        return '';
    }
}    