<?php
namespace app\api\home;

use \think\Request;
use \think\Db;
use think\Model;
use think\helper\Hash;
use app\common\builder\Httpclient;
use app\common\builder\Client;
/**
 * pay控制器
 * @package app\index\controller
 */
class Mpay
{
    /**
     * @var string 支付方式名称
     */
    public $name = '支付宝支付手机H5支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '支付宝支付手机H5接口';
     /**
     * @var string 支付方式key
     */
    public $app_key = 'malipay';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'malipay';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '支付宝（手机h5）';
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
     * @支付宝固定参数
     */
    public $Service_Paychannel = "mobile.merchant.paychannel";
    public $Service1 = "alipay.wap.trade.create.direct";    //接口1
    public $Service2 = "alipay.wap.auth.authAndExecute";    //接口2
    public $format = "xml";    //http传输格式
    public $sec_id = 'MD5';    //签名方式 不需修改
    public $_input_charset = 'utf-8';    //字符编码格式
    public $_input_charset_GBK = "GBK";
    public $v = '2.0';    //版本号
    public $gateway_paychannel="https://mapi.alipay.com/cooperate/gateway.do?";
    public $gateway="https://wappaygw.alipay.com/service/rest.htm?";

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
        $this->notify_url = 'http://'.$_SERVER['HTTP_HOST'].url('Mpay/callback');
        $this->callback_url = 'http://'.$_SERVER['HTTP_HOST'].url('Mpay/callback');
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

        $where['tid'] = $params['payment_id'];
        //查询订单信息
        $payment = db('trade')->where($where)->find();

        $mer_id = $this->mer_id;
        $mer_key = $this->mer_key;
        $seller_account_name = $this->seller_account_name;

        $subject = $payment['memberid'].$payment['tid'];

        $payment['payment'] = 0.01;

        $subject = str_replace("'",'`',trim($subject));
        $subject = str_replace('"','`',$subject);
        $subject = str_replace(' ','',$subject);

        $merchant_url = '';
        $subject_tmp = $subject;
        $price = number_format($payment['payment'],2,".","");

        $pms_0 = array (
            "_input_charset" => $this->_input_charset_GBK,
            "sign_type" => $this->sec_id,
            "service" => $this->Service_Paychannel,
            "partner" => $mer_id,
            "out_user" => ''
        );

        $pms_1 = array (
            "req_data"      => '<direct_trade_create_req><subject>' . $subject_tmp . '</subject><out_trade_no>' .
           $payment['tid'] . '</out_trade_no><total_fee>' . $price  . "</total_fee><seller_account_name>" . $seller_account_name .
            "</seller_account_name><notify_url>" . $this->notify_url . "</notify_url><out_user>" . '' .
            "</out_user><merchant_url>" . $merchant_url . "</merchant_url><cashier_code>" . '' .
            "</cashier_code>" . "<call_back_url>" . $this->callback_url . "</call_back_url></direct_trade_create_req>",
            "service"       => $this->Service1,
            "sec_id"        => $this->sec_id,
            "partner"       => $mer_id,
            "req_id"        => date("Ymdhis"),
            "format"        => $this->format,
            "v"             => $this->v
        );
        $token=$this->alipay_wap_trade_create_direct($pms_1,$mer_key);

        // 验证和发送信息与跳转手机支付宝收银台.
        $req_data = '<auth_and_execute_req><request_token>'.$token.'</request_token></auth_and_execute_req>';
        $pms2 = array (
            "req_data"      => $req_data,
            "service"       => $this->Service2,
            "sec_id"        => $this->sec_id,
            "partner"       => $mer_id,
            "call_back_url" => $this->callback_url,
            "format"        => $this->format,
            "app_pay"       => 'Y',
            "v"             => $this->v
        );
        $parameter = $this->para_filter($pms2);
        $mysign    = $this->build_mysign($this->arg_sort($parameter), $mer_key, $this->sec_id);
        $this->add_field('req_data',$req_data);
        $this->add_field('service',$this->Service2);
        $this->add_field('sec_id',$this->sec_id);
        $this->add_field('partner',$mer_id);
        $this->add_field('call_back_url',$this->callback_url);
        $this->add_field('format',$this->format);
        $this->add_field('v',$this->v);
        $this->add_field('app_pay', 'Y');
        $this->add_field('sign',urlencode($mysign));

        echo $this->get_html();exit;
    }

    /**
     * 创建mobile_merchant_paychannel接口
    */
    function mobile_merchant_paychannel($pms0, $merchant_key) {
        $_key = $merchant_key;                       //MD5校验码
        $sign_type    = $pms0['sign_type'];          //签名类型，此处为MD5
        $parameter = $this->para_filter($pms0);      //除去数组中的空值和签名参数
        $sort_array = $this->arg_sort($parameter);   //得到从字母a到z排序后的签名参数数组
        $mysign = $this->build_mysign($sort_array, $_key, $sign_type); //生成签名
        $req_data = $this->create_linkstring($parameter).'&sign='.urlencode($mysign).'&sign_type='.$sign_type;  //配置post请求数据，注意sign签名需要urlencode

        //模拟get请求方法
        $url = $this->gateway_paychannel . $req_data;

        //https_post
        $result = Httpclient::get($url);
        // $result = $this->curl($url);

        //调用处理Json方法
        $alipay_channel = $this->getJson($result,$_key,$sign_type);
        return $alipay_channel;
    }

    /**
     * 验签并反序列化Json数据
     */
    function getJson($result,$m_key,$m_sign_type){
        //获取返回的Json
        // $json = $this->getDataForXML($result,'/alipay/response/alipay/result');
        $xmlData = $this->getDataForXML($result);
        $json = $xmlData['alipay']['response']['alipay']['result'];
        //拼装成待签名的数据
        $data = "result=" . $json . $m_key;
        //$json="{\"payChannleResult\":{\"supportedPayChannelList\":{\"supportTopPayChannel\":{\"name\":\"储蓄卡快捷支付\",\"cashierCode\":\"DEBITCARD\",\"supportSecPayChannelList\":{\"supportSecPayChannel\":[{\"name\":\"农行\",\"cashierCode\":\"DEBITCARD_ABC\"},{\"name\":\"工行\",\"cashierCode\":\"DEBITCARD_ICBC\"},{\"name\":\"中信\",\"cashierCode\":\"DEBITCARD_CITIC\"},{\"name\":\"光大\",\"cashierCode\":\"DEBITCARD_CEB\"},{\"name\":\"深发展\",\"cashierCode\":\"DEBITCARD_SDB\"},{\"name\":\"更多\",\"cashierCode\":\"DEBITCARD\"}]}}}}}";
        //获取返回sign
        // $aliSign = $this->getDataForXML($result,'/alipay/sign');
        $aliSign = $xmlData['alipay']['sign'];
        //转换待签名格式数据，因为此mapi接口统一都是用GBK编码的，所以要把默认UTF-8的编码转换成GBK，否则生成签名会不一致
        $data_GBK = mb_convert_encoding($data, "GBK", "UTF-8");
        //生成自己的sign
        $mySign = $this->sign($data_GBK,$m_sign_type);
        //判断签名是否一致
        if($mySign==$aliSign){
            //echo "签名相同";
            //php读取json数据
            return json_decode($json);
        }else{
            //echo "验签失败";
            return "验签失败";
        }
    }


    /**
     * 创建alipay.wap.trade.create.direct接口
     */
    public function alipay_wap_trade_create_direct($pms1, $merchant_key){
        $_key       = $merchant_key;                  //MD5校验码
        $sign_type  = $pms1['sec_id'];              //签名类型，此处为MD5
        $parameter  = $this->para_filter($pms1);      //除去数组中的空值和签名参数
        $req_data   = $pms1['req_data'];
        $format     = $pms1['format'];                //编码格式，此处为utf-8
        $sort_array = $this->arg_sort($parameter);    //得到从字母a到z排序后的签名参数数组
        $mysign     = $this->build_mysign($sort_array, $_key, $sign_type);    //生成签名
        //$req_data   = $this->create_linkstring($parameter).'&sign='.urlencode($mysign);    //配置post请求数据，注意sign签名需要urlencode
        $parameter['sign'] = urlencode($mysign);

        //Post提交请求
        $url = $this->gateway;
        
        $res = Client::get($url, ['query' => $parameter])->getBody();
        // $res =  $this->curl($url, ['query' => $parameter]);
        //调用GetToken方法，并返回token
        return $this->getToken($res,$_key,$sign_type);
    }
    function get_html()
    {
        // 简单的form的自动提交的代码。

        header("Content-Type: text/html;charset=".$this->submit_charset);
        $strHtml ="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
        <html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en-US\" lang=\"en-US\" dir=\"ltr\">
        <head>
        </head><body><div>Redirecting...</div>";
        $strHtml .= '<form action="' . $this->submit_url .  '" name="pay_form" id="pay_form">';

        // Generate all the hidden field.
        foreach ($this->fields as $key=>$value)
        {
            $strHtml .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }
        $strHtml .= '<input type="submit" name="btn_purchase" value="1" style="display:none;" />';
        $strHtml .= '</form>';
        // $strHtml .= '<script type="text/javascript">'.$this->getApJs() .'</script> ';
        $strHtml .= '<script>';
        $strHtml .= 'var queryParam = \'\';';
        $strHtml .= 'Array.prototype.slice.call(document.querySelectorAll("input[type=hidden]")).forEach(function (ele) {';
        $strHtml .= '  if(ele.name === \'req_data_tt\') {';
        $strHtml .= '     queryParam += ele.name + "=" + ele.value + \'u\'';
        $strHtml .= '  } else {';
        $strHtml .= '     queryParam += ele.name + "=" + encodeURIComponent(ele.value) + \'&\';';
        $strHtml .= '  }';
        $strHtml .= '});';
        $strHtml .= 'var gotoUrl = document.querySelector("#pay_form").getAttribute(\'action\') + \'&\' + queryParam;';
        $strHtml .= '_AP.pay(gotoUrl);';
        $strHtml .= '</script>';
        $strHtml .= '</body></html>';
        return $strHtml;
    }

    /**
     * 返回token参数
     * 参数 result 需要先urldecode
     */
    function getToken($result,$_key,$gt_sign_type){
        $result = urldecode($result);               //URL转码
        $Arr = explode('&', $result);               //根据 & 符号拆分

        $temp = array();                            //临时存放拆分的数组
        $myArray = array();                         //待签名的数组
        //循环构造key、value数组
        for ($i = 0; $i < count($Arr); $i++) {
            $temp = explode( '=' , $Arr[$i] , 2 );
            $myArray[$temp[0]] = $temp[1];
        }

        $sign = $myArray['sign'];                                               //支付宝返回签名
        $myArray = $this->para_filter($myArray);                                       //拆分完毕后的数组
        $sort_array = $this->arg_sort($myArray);                                       //排序数组
        $mysign = $this->build_mysign($sort_array,$_key,$gt_sign_type); //构造本地参数签名，用于对比支付宝请求的签名
        if($mysign == $sign)  //判断签名是否正确
        {
            $xmlData = $this->getDataForXML($myArray['res_data']);
            // return $this->getDataForXML($myArray['res_data'],'/direct_trade_create_res/request_token');    //返回token
            return $xmlData['direct_trade_create_res']['request_token'];    //返回token
        }else{
            echo('签名不正确');      //当判断出签名不正确，请不要验签通过
            return '签名不正确';
        }
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
            db('trade')->where($where)->update($data);//修改订单状态
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

    /**生成签名结果
     * $array要签名的数组
     * return 签名结果字符串
     */
    public function build_mysign($sort_array,$key,$sign_type = "MD5") {
        $prestr = $this->create_linkstring($sort_array);         //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $prestr.$key;                            //把拼接后的字符串再与安全校验码直接连接起来
        $mysgin = $this->sign($prestr,$sign_type);                //把最终的字符串签名，获得签名结果
        return $mysgin;
    }


    /**把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * $array 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    public function create_linkstring($array) {
        $arg  = "";
        while (list ($key, $val) = each ($array)) {
            $arg.=$key."=".$val."&";
        }
        $arg = substr($arg,0,count($arg)-2);             //去掉最后一个&字符
        return $arg;
    }


    /**除去数组中的空值和签名参数
     * $parameter 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    public function para_filter($parameter) {
        $para = array();
        while (list ($key, $val) = each ($parameter)) {
            if($key == "sign" || $key == "sign_type" || $val == "")continue;
            else    $para[$key] = $parameter[$key];
        }
        return $para;
    }


    /**对数组排序
     * $array 排序前的数组
     * return 排序后的数组
     */
    public function arg_sort($array) {
        ksort($array);
        reset($array);
        return $array;
    }


    /**签名字符串
     * $prestr 需要签名的字符串
     * $sign_type 签名类型，也就是sec_id
     * return 签名结果
     */
    public function sign($prestr,$sign_type) {
        $sign='';
        if($sign_type == 'MD5') {
            $sign = md5($prestr);
        }elseif($sign_type =='DSA') {
            //DSA 签名方法待后续开发
            die("DSA 签名方法待后续开发，请先使用MD5签名方式");
        }else {
            die("支付宝暂不支持".$sign_type."类型的签名方式");
        }
        return $sign;
    }

    /**
     * 发送GET请求
     * @param string $url
     * @param array $param
     * @return bool|mixed
     */
    public static function doGet($url, $param = null)
    {
        if (empty($url) or (!empty($param) and !is_array($param))) {
            throw new InvalidArgumentException('Params is not of the expected type');
        }
        // 验证url合法性
//        if (!filter_var($url, FILTER_VALIDATE_URL)) {
//            throw new InvalidArgumentException('Url is not valid');
//        }

        if (!empty($param)) {
            $url = trim($url, '?') . '?' . http_build_query($param);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);     //  不进行ssl 认证
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        if (!empty($result) and $code == 200) {
            return $result;
        }
        return false;
    }

    public function curl($url, $postFields = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $postBodyString = "";
        $encodeArray = Array();
        $postMultipart = false;


        if (is_array($postFields) && 0 < count($postFields)) {

            foreach ($postFields as $k => $v) {
                if ("@" != substr($v, 0, 1)) //判断是不是文件上传
                {

                    $postBodyString .= "$k=" . urlencode($this->characet($v, $this->postCharset)) . "&";
                    $encodeArray[$k] = $this->characet($v, $this->postCharset);
                } else //文件上传用multipart/form-data，否则用www-form-urlencoded
                {
                    $postMultipart = true;
                    $encodeArray[$k] = new \CURLFile(substr($v, 1));
                }

            }
            unset ($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeArray);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
        }

        if ($postMultipart) {

            $headers = array('content-type: multipart/form-data;charset=' . $this->postCharset . ';boundary=' . $this->getMillisecond());
        } else {

            $headers = array('content-type: application/x-www-form-urlencoded;charset=' . $this->postCharset);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);




        $reponse = curl_exec($ch);

        if (curl_errno($ch)) {

            throw new Exception(curl_error($ch), 0);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                throw new Exception($reponse, $httpStatusCode);
            }
        }

        curl_close($ch);
        return $reponse;
    }

      /**
     * POST请求
     * @param $url
     * @param $param
     * @return boolean|mixed
     */
    public static function doPost($url, $param, $method = "POST")
    {
        // json
        $headers = array(
            'Content-Type: application/json',
        );
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
        curl_setopt($curl, CURLOPT_TIMEOUT, 3);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $resp = curl_exec($curl);
        //var_dump( curl_error($curl) );//如果在执行curl的过程中出现异常，可以打开此开关查看异常内容。
        $info = curl_getinfo($curl);
        curl_close($curl);
        if (isset($info['http_code']) && $info['http_code'] == 200) {
            return $resp;
        }
        return '';
    }

    /**
     * 通过节点路径返回字符串的某个节点值
     * $res_data——XML 格式字符串
     * 返回节点参数
     */
    function getDataForXML($res_data)
    {
        return kernel::single('site_utility_xml')->xml2array($res_data);
    }
}    