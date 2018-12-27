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
class Wxpay
{
    /**
     * @var string 支付方式名称
     */
    public $name = '微信支付App接口';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '微信支付App接口';
     /**
     * @var string 支付方式key
     */
    public $app_key = 'wxpayApp';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'wxpayApp';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '微信支付App接口';
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

    /**
     * @微信支付固定参数
     */
    public $init_url = 'https://api.mch.weixin.qq.com/pay/unifiedorder?';

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct(){

        $this->notify_url = 'http://daguanxl.com:88/'.url('Wxpay/callback');
        #test
        $this->submit_charset = 'UTF-8';
        $this->signtype = 'MD5';

        // $certdir = DATA_DIR . '/cert/payment_plugin_wxpayApp/';
        // $this->SSLCERT_PATH = $certdir;
        // $this->SSLKEY_PATH = $certdir;
    }


    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    function dopay()
    {
        $appid      = 'wx704f02d6e4b18396';
        $mch_id     = '1516779871';
        $key        = 'daguanxldaguanxldaguanxl12345678';

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
            // db('cms_coupon')->where(['id'=>$params['couponid']])->update(['use'=>1]);
        }
        
        


        $payment['payment'] = 0.01;

        //获取详细内容
        $subject = '商品名1';

        $parameters = array(
            'appid'            => strval($appid),
            'body'             => $payment['title'].'...',
            'out_trade_no'     => strval( $params['payment_id'] ),
            'total_fee'        => bcmul($payment['payment'], 100, 0),
            'notify_url'       => strval( $this->notify_url ),
            'trade_type'       => 'APP',
            'mch_id'           => strval($mch_id),
            'nonce_str'        => $this->create_noncestr(),
            'spbill_create_ip' => strval( $_SERVER['SERVER_ADDR'] ),
        );
        $parameters['sign'] = $this->getSign($parameters, $key);
        $xml                = $this->arrayToXml($parameters);
        $url                = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $response           = $this->postXmlCurl($xml, $url, 30);
        $result             = $this->xmlToArray($response);
        $prepay_id          = $result['prepay_id'];

        if($prepay_id == '')
        {
            if($result['return_code'] != 'SUCCESS')
                throw new RuntimeException($result['return_msg']);
            if($result['result_code'] != 'SUCCESS')
                throw new RuntimeException($result['err_code_des']);
        }

        // 用于微信支付后跳转页面传order_id,不作为传微信的字段
        $this->add_field("appid",           $appid);
        $this->add_field("noncestr",        $this->create_noncestr());
        $this->add_field("package",         "Sign=WXPay");
        $this->add_field("partnerid",       $mch_id);
        $this->add_field("prepayid",        $prepay_id);
        $this->add_field("timestamp",       strval(time()));
        $this->add_field("sign",         $this->getSign($this->fields,$key));

        echo $this->get_html($params['payment_id']);exit;
    }

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    function callback(){
        $mch_id     = '1516779871';
        $key        = 'daguanxldaguanxldaguanxl12345678';
        $xml = file_get_contents("php://input");
        $params = $this->xmlToArray($xml);
        // $request = Request::instance();
        // $params = $request->param();
        error_log(json_encode($params),3,'/home/wwwroot/daguan/wx.log');
        
        if( $params['return_code'] == 'SUCCESS' && $params['result_code'] == 'SUCCESS' )
        {
            $where['tid'] = $params['out_trade_no'];
            $data['status'] = 1;
            $data['pay_type'] = 'wxpayApp';
            $data['buyer_email'] = $params['openid'];
            $data['trade_no'] = $params['transaction_id'];
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
     * 支付成功回打支付成功信息给支付网关
     */
    function ret_result($paymentId){
        $ret = array('return_code'=>'SUCCESS','return_msg'=>'');
        $ret = $this->arrayToXml($ret);
        echo $ret;exit;
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
     * 生成支付表单 - 自动提交
     * @params null
     * @return null
     */
    public function gen_form(){
        return '';
    }

    protected function get_html($payment_id){

        $arr = [];
        $arr['appid']       = $this->fields['appid']    ;
        $arr['noncestr']    = $this->fields['noncestr'] ;
        $arr['package']     = $this->fields['package']  ;
        $arr['partnerid']   = $this->fields['partnerid'];
        $arr['prepayid']    = $this->fields['prepayid'] ;
        $arr['timestamp']   = $this->fields['timestamp'];
        $arr['sign']        = $this->fields['sign']     ;


        $json = json_encode($arr);
        echo $json;exit;
        return $json;
    }

//↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓公共函数部分↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

    /**
     *  作用：将xml转为array
     */
    public function xmlToArray($xml)
    {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    /**
     *  作用：array转xml
     */
    function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
             if (is_numeric($val))
             {
                $xml.="<".$key.">".$val."</".$key.">";

             }
             else
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     *  作用：以post方式提交xml到对应的接口url
     */
    public function postXmlCurl($xml,$url,$second=30)
    {
        // $response = client::post($url, array(
        //     'body'   => $xml,
        // ));
        $response = $this->https_post($url,array(
            'body'   => $xml,
        ));

        // 获取guzzle返回的值的body部分
        // $body = $response->getBody();
        return  $response;
    }

    /**
     *  作用：设置标配的请求参数，生成签名，生成接口参数xml
     */
    function createXml($parameters)
    {
        $this->parameters["appid"] = 'wx704f02d6e4b18396';//公众账号ID
        $this->parameters["mch_id"] = '1516779871';//商户号
        $this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
        $this->parameters["sign"] = $this->getSign($this->parameters);//签名
        return  $this->arrayToXml($this->parameters);
    }

    /**
     *  作用：post请求xml
     */
    function postXml()
    {
        $xml = $this->createXml();
        $this->response = $this->postXmlCurl($xml,$this->url,$this->curl_timeout);
        return $this->response;
    }

    /**
     *  作用：生成签名
     */
    public function getSign($Parameters, $key)
    {
        ksort($Parameters); //签名步骤一：按字典序排序参数
        $buff = "";
        foreach ($Parameters as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v))
            {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $String = trim($buff, "&");
        $String = $String."&key=".$key; //签名步骤二：在string后加入KEY
        $String = md5($String); //签名步骤三：MD5加密
        $result = strtoupper($String); //签名步骤四：所有字符转为大写
        return $result;
    }

    public function create_noncestr( $length = 16 ){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }

        return $str;
    }

    function https_post($url,$data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_NOBODY, FALSE); //表示需要response body
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
           return 'Errno'.curl_error($curl);
        }

        curl_close($curl);
        return $result;
    }

    /**
     * 设置属性
     * @params string key
     * @params string value
     * @return null
     */
    protected function add_field($key, $value='')
    {
        if (!$key)
        {
            return '';
        }

        $this->fields[$key] = $value;
    }
//↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑公共函数部分↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑


   
}   