<?php


namespace app\index\controller;
use app\call\model\Recoverdt as RecoverdtModel;
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


    /**
     * [recoverTask description]
     * @return [type] [description]
     */
    public function recoverTask()
    {
        //分配日志
        $recoverHour = config('recover_data_hour')?config('recover_data_hour'):0;
        if (!$recoverHour) {
            error_log('NOT SET RECOVER HOUR RECOVERTASK_'.time(),3,'/data/httpd/tshop/public/task.log');
            echo 'succ';exit;
        }
        //回收数据 分配任务状态修改 客户状态修改
        $diff = time()-$recoverHour*60*60;
        $userInfo = db('call_alloc_log')->where(['status'=>1])->whereTime('create_time','<',$diff)->field('custom_id,user_id')->select();
        if (!$userInfo) {
            error_log('NOT MODIF CUSTOM RECOVERTASK_'.time(),3,'/data/httpd/tshop/public/task.log');
            echo 'succ';exit;
        }
        db('call_alloc_log')->whereTime('create_time','<',$diff)->update(['status'=>0]);
        $map['id'] = array('in',array_column($userInfo, 'custom_id'));
        db('call_custom')->where($map)->update(['status'=>1]);
        //回收列表
        $customs = array_column($userInfo, 'custom_id');
        foreach ($customs as $key => $value) {
            $sv[$key]['custom_id'] = $value;
            $sv[$key]['create_time'] = time();
        }
        error_log('NOT MODIF CUSTOM RECOVERTASK_'.time().json_encode($sv),3,'/data/httpd/tshop/public/task.log');
        $Recoverdt = new RecoverdtModel;
        $Recoverdt->saveAll($sv);
        
        //提醒管理员 tags user_id title content 
        notice_log('recover',1);
    }

    public function testask()
    {
        // notice_log('recover',1,['custom'=>'1','project'=>1],true);

        error_log('tsnew task:'.time(),3,'/data/httpd/tshop/public/task.log');
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

        $text = '【呼叫中心】温馨提示：您有新的心理咨询预约：'.$content; 

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
