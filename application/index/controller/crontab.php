<?php


namespace app\index\controller;

use \think\Request;
use \think\Db;
use think\Model;

/**
 * crontab控制器
 * @package app\index\controller
 */
class Crontab
{
    public function index()
    {
       //发短信
       $info = db('msg')->alias('a')->field('a.*,a.id as aid,m.*')->join('member m',' m.id = a.reciveid','LEFT')->where(['a.is_send'=>0,'a.type'=>1,'a.is_pay'=>1])->whereTime('a.create_time', 'today')->select();
        foreach ($info as $key => $value) {
            // if (!strstr($value['descrption'], '面对面')) {
            //     continue;
            // }
            if ($value['mobile']&&$this->sendmsg($value['mobile'],$value['descrption'])) {
                $umobile = db('member')->where(['id'=>$value['sendid']])->value('mobile');
                sleep(1);
                $this->sendmsg($umobile,$value['display']);
                db('msg')->where(['id'=>$value['aid']])->update(['is_send'=>1]);
            }else{
                echo 'fail';
            }
                        
        }
    }

    public function testask()
    {

        error_log('ts task:'.time(),3,'/data/httpd/daguan/public/task.log');
        echo 'succ';
    }  
    /**
     * 咨询任务
     */

    public function adivcetask()
    {
        
        //查看所有待咨询
        $map['status'] = 0;
        $map['end_time'] = array('lt',time());
        db('calendar')->where($map)->update(['status'=>1]);
        error_log('ts adivcetask:'.time(),3,'/data/httpd/daguan/public/task.log');  
    }
    //------function ----------------
    /**
     * [sendmsg description]
     * @param  [type] $mobile [description]
     * @return [type]         [description]
     */
    public function sendmsg($mobile,$content)
    {
        $apikey = "8df6ed7129c50581eecdf1e875edbaa3"; 

        $text = '【大观心理】温馨提示：您有新的心理咨询预约：'.$content; 

        // error_log($text,3,'/home/wwwroot/daguan/mobile.log');
        $ch = curl_init();
 
         /* 设置验证方式 */
         curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8',
             'Content-Type:application/x-www-form-urlencoded', 'charset=utf-8'));
         /* 设置返回结果为流 */
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         
         /* 设置超时时间*/
         curl_setopt($ch, CURLOPT_TIMEOUT, 10);
         
         /* 设置通信方式 */
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         
         // 发送短信
         $data = array('text'=>$text,'apikey'=>$apikey,'mobile'=>$mobile);
         $json_data = $this->send($ch,$data);
         // error_log($json_data,3,'/home/wwwroot/daguan/sendmsg.log');
         $array = json_decode($json_data,true); 
         // print_r($array);exit; 
         if ($array['code']==0) {
            return true;
         }else{
            return false;
         }
    }
    /**
     * [send description]
     * @param  [type] $ch   [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    function send($ch,$data){
         curl_setopt ($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/sms/single_send.json');
         curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
         $result = curl_exec($ch);
         $error = curl_error($ch);
         // checkErr($result,$error);
         return $result;
     }
}
