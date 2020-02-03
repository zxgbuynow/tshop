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

namespace plugins\Wechat\controller;

use app\common\controller\Common;
require_once(dirname(dirname(__FILE__))."/sdk/api/src/CorpAPI.class.php");
require_once(dirname(dirname(__FILE__))."/sdk/api/src/ServiceCorpAPI.class.php");
require_once(dirname(dirname(__FILE__))."/sdk/api/src/ServiceProviderAPI.class.php");

/**
 * Wechat控制器
 * @package plugins\Wechat\controller
 */
class Wechat extends Common
{
    /**
     * 发送企业微信
     * @param string $rec_num 接受人
     * @param array $toparty 部门
     * @param string $totag 标签
     * @param string $content 消息
     * @author 蔡伟明 <314013107@qq.com>
     * @return array
     *
     * $result = plugin_action('Wechat/Wechat/send', [接受人], [部门], [标签], '消息类型','消息内容']);
     * if($result['code']){
     *     $this->error('发送失败，错误代码：'. $result['code']. ' 错误信息：'. $result['msg']);
     * } else {
     *     $this->success('发送成功');
     * }
     */
    public function send($touser = [], $toparty = [], $totag = [], $msgtype = 'text' , $content)
    {
        // 插件配置参数
        $config = plugin_config('wechat');
        if ($config['status'] != '1') {
            return array('code' => 1, 'msg' => '企业微信功能已关闭');
        }

        if ($config['CORP_ID'] == '') {
            return array('code' => 2, 'msg' => '请填写CORP_ID');
        }
        if ($config['APP_ID'] == '') {
            return array('code' => 3, 'msg' => '请填写APP_ID');
        }
        if ($config['APP_SECRET'] == '') {
            return array('code' => 4, 'msg' => '请填写APP_SECRET');
        }
        if (!$touser) {
            return array('code' => 5, 'msg' => '发送人必填');
        }
        if (!$content) {
            return array('code' => 6, 'msg' => '发送消息必填');
        }

        $agentId = $config['APP_ID'];
        $api = new \CorpAPI($config['CORP_ID'], $config['APP_SECRET']);

        try { 
            //
            $message = new \Message();
            {
                $message->sendToAll = false;
                $message->touser = $touser;
                $message->toparty = $toparty;
                $message->totag= $totag;
                $message->agentid = $agentId;
                $message->msgtype = $msgtype;
                $message->safe = 0;


                if ($msgtype=='text') {
                   
                    $message->messageContent = new \TextMessageContent(
                        $content
                    );
                    
                }else{
                    //todo
                    $message->messageContent = new \NewsMessageContent(
                        array(
                            new NewsArticle(
                                $title = "Got you !", 
                                $description = "Who's this cute guy testing me ?", 
                                $url = "https://work.weixin.qq.com/wework_admin/ww_mt/agenda", 
                                $picurl = "https://p.qpic.cn/pic_wework/167386225/f9ffc8f0a34f301580daaf05f225723ff571679f07e69f91/0", 
                                $btntxt = "btntxt"
                            ),
                        )
                    );
                }
                
            }
            $invalidUserIdList = null;
            $invalidPartyIdList = null;
            $invalidTagIdList = null;

            $api->MessageSend($message, $invalidUserIdList, $invalidPartyIdList, $invalidTagIdList);
            // var_dump($invalidUserIdList);
            // var_dump($invalidPartyIdList);
            // var_dump($invalidTagIdList);
            return array('code' => 0, 'msg' => '发送成功');
        } catch (Exception $e) { 
            return array('code' => -1, 'msg' => $e->getMessage());
        }

        

    }
}