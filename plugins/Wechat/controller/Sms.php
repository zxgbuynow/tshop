<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

namespace plugins\Sms\controller;

use app\common\controller\Common;
use plugins\Sms\model\Sms as SmsModel;
require_once(dirname(dirname(__FILE__))."/sdk/TopSdk.php");

/**
 * sms控制器
 * @package plugins\Sms\controller
 */
class Sms extends Common
{
    /**
     * 发送短信
     * @param string $rec_num 短信接收号码
     * @param array $sms_param 短信模板变量
     * @param string $sms_template 短信模板名称
     * @param string $sms_extend
     *      公共回传参数，在“消息返回”中会透传回该参数；举例：用户可以传入自己下级的会员ID，
     *      在消息返回时，该会员ID会包含在内，用户可以根据该会员ID识别是哪位会员使用了你的应用
     * @author 蔡伟明 <314013107@qq.com>
     * @return array
     *
     * $result = plugin_action('Sms/Sms/send', ['手机号码', [模板变量], '模板名称']);
     * if($result['code']){
     *     $this->error('发送失败，错误代码：'. $result['code']. ' 错误信息：'. $result['msg']);
     * } else {
     *     $this->success('发送成功');
     * }
     */
    public function send($rec_num = '', $sms_param = [], $sms_template = '', $sms_extend = '123456')
    {
        $client  = new \TopClient;
        $request = new \AlibabaAliqinFcSmsNumSendRequest;

        // 插件配置参数
        $config = plugin_config('sms');
        if ($config['status'] != '1') {
            return array('code' => 1, 'msg' => '短信功能已关闭');
        }
        if ($config['appkey'] == '') {
            return array('code' => 2, 'msg' => '请填写APPKEY');
        }
        if ($config['secret'] == '') {
            return array('code' => 3, 'msg' => '请填写SECRET');
        }
        if (!$rec_num) {
            return array('code' => 4, 'msg' => '请填写短信接收号码');
        }
        if (!$sms_param) {
            return array('code' => 5, 'msg' => '请填写短信模板变量');
        }
        if (!$sms_template) {
            return array('code' => 6, 'msg' => '没有设置短信模板');
        }

        $template = SmsModel::getTemplate($sms_template);
        if (!$template) {
            return array('code' => 7, 'msg' => '找不到短信模板');
        }

        // 模板参数
        if ($template['status'] == '0') {
            return array('code' => 8, 'msg' => '短信模板已禁用');
        }
        if ($template['code'] == '') {
            return array('code' => 9, 'msg' => '请设置模板ID');
        }
        if ($template['sign_name'] == '') {
            return array('code' => 10, 'msg' => '请设置短信签名');
        }


        // 设置客户端连接信息
        $client->method    = 'alibaba.aliqin.fc.sms.num.send';
        $client->appkey    = $config['appkey'];
        $client->secretKey = $config['secret'];
        $client->format    = "json";

        // 设置请求参数
        $request->setExtend($sms_extend);                     //公共回传参数
        $request->setSmsType("normal");                      //短信类型，传入值请填写normal
        $request->setSmsFreeSignName($template['sign_name']);//短信签名
        $request->setSmsParam(json_encode($sms_param));       //短信模板变量
        $request->setRecNum((string)$rec_num);                        //短信接收号码
        $request->setSmsTemplateCode($template['code']);     //短信模版ID

        // 执行请求
        $resp = $client->execute($request); //解析后的结果对象

        if (isset($resp->code) && $resp->code != 0) {
            // 发送失败
            $msg = isset($resp->sub_msg) ? $resp->sub_msg : $resp->msg;
            return array('code' => $resp->code, 'msg' => $msg);
        } else {
            // 发送成功
            return array('code' => 0, 'msg' => '发送成功');
        }
    }
}