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
        // echo 'succ';exit;
        //分配日志
        $recoverHour = config('recover_data_hour')?config('recover_data_hour'):0;
        if (!$recoverHour) {
            error_log('NOT SET RECOVER HOUR RECOVERTASK_'.time(),3,'/data/http/ringup/public/task.log');
            echo 'succ';exit;
        }
        //回收数据 分配任务状态修改 客户状态修改
        $diff = time()-$recoverHour*60*60;

        // $userInfo = db('call_alloc_log')->where(['status'=>1])->whereTime('create_time','<',$diff)->field('custom_id,user_id')->select();
        $m['a.status'] = 1;
        // $m['c.timeLength'] = array('eq',0);
        $userInfo = Db::name('call_alloc_log')->alias('a')->field('a.custom_id,a.user_id')->join(' call_log c',' c.alloc_log_id = a.id','LEFT')->whereTime('a.create_time','<',$diff)->where($m)->select();
        // echo Db::name('call_alloc_log')->getlastsql();exit;
        //新数据过滤
        if (!$userInfo) {
            error_log('NOT MODIF CUSTOM RECOVERTASK_'.time(),3,'/data/http/ringup/public/task.log');
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
        error_log('NOT MODIF CUSTOM RECOVERTASK_'.time().json_encode($sv),3,'/data/http/ringup/public/task.log');
        $Recoverdt = new RecoverdtModel;
        $Recoverdt->saveAll($sv);
        
        //提醒管理员 tags user_id title content 
        notice_log('recover',1);
    }

    /**
     * [timeLengthTash description todo]
     * @return [type] [description]
     */
    public function timeLengthTash()
    {

        $info = [
            'timeLength'=>isset(plugin_config('wechat')['timeLength'])?plugin_config('wechat')['timeLength']:''
        ];
        //如果设置了时间点
        if ($info['timeLength']) {
            $times = explode('|', $info['timeLength']);
            $h = date('H',time());
            if (in_array($h, $times)) {
                //[李四|员工]，[180秒|呼出通话时长]，[2020-2-6|时间段] 前五 后五
                $top5 = db('call_log')->where($map)->field('*,SUM(timeLength) as times')->order('times DESC')->group('user_id')->limit(5)->select();
                $bottom5 = db('call_log')->where($map)->field('*,SUM(timeLength) as times')->order('times ASC')->group('user_id')->limit(5)->select();

                //时间段
                $range = '';
                $p = array_search($h,$times);

                if ($p==0) {
                    $range ='9点至'.$p.'点';
                }else{

                    $range = $times[$p-1].'至'.$p.'点';
                }
                //内容处理
                $msg = '排名前五：'.'\n';
                foreach ($top5 as $key => $value) {
                    $msg .= get_nickname($value['user_id']).',呼出通话时长'.$value['times'].',统计时间段'.$range.'\n';
                }

                $msg = '排名后五：'.'\n';
                foreach ($bottom5 as $key => $value) {
                    $msg .= get_nickname($value['user_id']).',呼出通话时长'.$value['times'].',统计时间段'.$range.'\n';
                }
                _sendMaster($msg,'timeLength_statistics');
                echo 'succ';exit;
                // $touser = db('admin_user')->where(['id'=>1])->find();
                // //push
                // $toparty = [];
                // $totag = [];
                // $user = [];
                // array_push($user, $touser['wechat_name']);

                // $result = plugin_action('Wechat/Wechat/send',[$user , $toparty, $totag, 'text', $msg]);
                // if($result['code']){
                //     echo 'succ';exit;
                // } else {
                //     echo 'fail';exit;
                // }
            }
            echo '不在执行时间段';exit;
        }

    }

    /**
     * [FunctionName A类客户1周平均成本统计 周一中午12点 管理员]
     * @param string $value [description]
     */
    public function classareportTast()
    {
        //取配置
        $info = [
            'classAReport'=>isset(plugin_config('wechat')['classAReport'])?plugin_config('wechat')['classAReport']:''
        ];
        //取得数据
        $map['category'] = 1;
        $data_list = db('call_custom')->where($map)->field('*,avg(fee) as fees')->order('fees DESC')->group('project_id,source')->select();
        if (!$info['classAReport']) {
            echo '执行失败，没有配置安全值';exit;
        }
        //项目
        $pj =[];
        foreach ($data_list as $key => $value) {

            if ($value['fees']>$info['classAReport']) {
                $pj[$value['project_id']][] = $value['source'].'('.$value['fees'].')';
            }
        }
        if (!$pj) {
            echo '执行成功，无匹配数据';exit;
        }
        $content = '';
        //项目
        if (count($pj)>1) {//多项目组成
            foreach ($pj as $key => $value) {
                //取项目 取平均值
                $pjnm = db('call_project_list')->where(['id'=>$key])->value('col1');
                $map['project_id'] = $key;
                $pjavg = number_format(db('call_custom')->where($map)->avg('fee'),1) ;
                $content = $pjnm.'上周的A类客户成本为'.$pjavg.'，（安全值为'.$info['classAReport'].'元），其中:'.implode('，', $pj[$key]);
                _sendMaster($content,'classareport_statistics');
            }
            echo 'succ';
            exit;
        }else{
            //取项目 取平均值
            $pj_id = key($pj);
            $pjnm = db('call_project_list')->where(['id'=>$pj_id])->value('col1');
            $pjavg = number_format(db('call_custom')->where($map)->avg('fee'),1) ;
            $content .= $pjnm.'上周的A类客户成本为'.$pjavg.'，（安全值为'.$info['classAReport'].'元），其中:'.implode('，', $pj[$pj_id]);
        }

        _sendMaster($content,'classareport_statistics');
        echo 'succ';exit;

    }

    /**
     * [classnreport 单条客户平均成本 周一中午 管理员]
     * @return [type] [description]
     */
    public function classnreport()
    {
        //取配置
        $info = [
            'classNReport'=>isset(plugin_config('wechat')['classNReport'])?plugin_config('wechat')['classNReport']:''
        ];
        $data_list = db('call_custom')->field('*,avg(fee) as fees')->order('fees DESC')->group('project_id,source')->select();
        if (!$info['classNReport']) {
            echo '执行失败，没有配置安全值';exit;
        }
        //项目
        $pj =[];
        foreach ($data_list as $key => $value) {

            if ($value['fees']>$info['classNReport']) {
                $pj[$value['project_id']][] = $value['source'].'('.$value['fees'].')';
            }
        }
        if (!$pj) {
            echo '执行成功，无匹配数据';exit;
        }
        $content = '';
        //项目
        if (count($pj)>1) {//多项目组成
            foreach ($pj as $key => $value) {
                //取项目 取平均值
                $pjnm = db('call_project_list')->where(['id'=>$key])->value('col1');
                $map['project_id'] = $key;
                $pjavg = number_format(db('call_custom')->avg('fee'),1) ;
                $content = $pjnm.'上周的单条客户平均成本为'.$pjavg.'，（安全值为'.$info['classNReport'].'元），其中:'.implode('，', $pj[$key]);
                _sendMaster($content,'classnreport_statistics');
            }
            echo 'succ';
            exit;
        }else{
            //取项目 取平均值
            $pj_id = key($pj);
            $pjnm = db('call_project_list')->where(['id'=>$pj_id])->value('col1');
            $pjavg = number_format(db('call_custom')->avg('fee'),1) ;
            $content .= $pjnm.'上周的单条客户平均成本为'.$pjavg.'，（安全值为'.$info['classNReport'].'元），其中:'.implode('，', $pj[$pj_id]);
        }

        _sendMaster($content,'classnreport_statistics');
        echo 'succ';exit;
    }

    /**
     * [classfreportTask 当月签约客户平均成本 月4号中午12点 管理员]
     * @return [type] [description]
     */
    public function classfreportTask()
    {
        //取配置
        $info = [
            'classFReport'=>isset(plugin_config('wechat')['classFReport'])?plugin_config('wechat')['classFReport']:''
        ];
        if (!$info['classFReport']) {
            echo '执行失败，没有配置安全值';exit;
        }
        //当月数当月签单
        $map =[];

        $data_list = db('call_custom')->whereTime('note_time','last month')->where($map)->field('*,avg(fee) as avgffee,count(*) as counts')->order('avgffee DESC')->group('project_id,source')->select();


        //好奇小屋上月数据总数量为100条，签约5单，平均签约成本为1000元，签约成本安全值为200元，其中抖音300条，平均单条成本90元，头条400条，平均单条成本80元
        //项目
        $pj =[];
        foreach ($data_list as $key => $value) {

            $pj[$value['project_id']][] = $value['source'].$value['counts'].'条，平均单条成本'.$value['avgffee'].'元';
        }
        if (!$pj) {
            echo '执行成功，无匹配数据';exit;
        }
        $content = '';
        //项目
        if (count($pj)>1) {//多项目组成
            foreach ($pj as $key => $value) {
                //取项目 取平均值
                $pjnm = db('call_project_list')->where(['id'=>$key])->value('col1');
                $map['project_id'] = $key;
                $pjavg = number_format(db('call_custom')->where($map)->whereTime('note_time','last month')->avg('fee'),1) ;
                $pjsum = db('call_custom')->where($map)->whereTime('note_time','last month')->count();
                $pjsucc = db('call_custom')->where($map)->whereTime('note_time','last month')->where(['category'=>6])->count();
                //好奇小屋上月数据总数量为100条，签约5单，平均签约成本为1000元，签约成本安全值为200元，其中抖音300条，平均单条成本90元，头条400条，平均单条成本80元
                $content = $pjnm.'上月数据总数量为'.$pjsum.'，签约'.$pjsucc.'单，平均签约成本为'.$pjavg.'元，签约成本安全值为'.$info['classFReport'].'元，其中'.implode('，', $pj[$key]);

                _sendMaster($content,'classfreport_statistics');
            }
            echo 'succ';
            exit;
        }else{
            //取项目 签约单 总数量 平均签约成本
            $pj_id = key($pj);
            $pjnm = db('call_project_list')->where(['id'=>$pj_id])->value('col1');
            $pjavg = number_format(db('call_custom')->whereTime('note_time','last month')->avg('fee'),1) ;
            $pjsum = db('call_custom')->whereTime('note_time','last month')->count();
            $pjsucc = db('call_custom')->whereTime('note_time','last month')->where(['category'=>6])->count();
            //好奇小屋上月数据总数量为100条，签约5单，平均签约成本为1000元，签约成本安全值为200元，其中抖音300条，平均单条成本90元，头条400条，平均单条成本80元
            $content = $pjnm.'上月数据总数量为'.$pjsum.'，签约'.$pjsucc.'单，平均签约成本为'.$pjavg.'元，签约成本安全值为'.$info['classFReport'].'元，其中'.implode('，', $pj[$pj_id]);
        }

        _sendMaster($content,'classfreport_statistics');
        echo 'succ';exit;
    }

    /**
     * [classf15reportTask 15天签约数量统计 每月二次16号 30 31号 12点 管理员]
     * @return [type] [description]
     */
    public function classf15reportTask()
    {
        $map = [];

        //是否本月最后一天并且大于16号
        $today = date('t', time());
        if ($today>16) {
            $lastd = date('t', strtotime('-1 month'));
            if ($lastd!=28&&$today==28) {//2月28天
                echo 'succ28';exit;
            }
            if ($lastd!=30&&$today==30) {//本月30天
                echo 'succ30';exit;
            }
        }
        

        
        $data_list = db('call_custom')->where($map)->whereTime('note_time','-15 days')->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->select();

        $pj =[];
        foreach ($data_list as $key => $value) {
            $succ = db('call_custom')->where(['project_id'=>$value['project_id'],'source'=>$value['source']])->whereTime('note_time','-15 days')->where(['category'=>6])->count();
            $pj[$value['project_id']][] = $value['source'].' 为'.$value['counts'].'条，签单'.$succ;
        }
        if (!$pj) {
            echo '执行成功，无匹配数据';exit;
        }
        $content = '';
        //项目
        if (count($pj)>1) {//多项目组成
            foreach ($pj as $key => $value) {
                //取项目 取平均值
                $pjnm = db('call_project_list')->where(['id'=>$key])->value('col1');
                $map['project_id'] = $key;

                $pjsum = db('call_custom')->where($map)->whereTime('note_time','-15 days')->count();
                $pjsucc = db('call_custom')->where($map)->whereTime('note_time','-15 days')->where(['category'=>6])->count();
                
                //好奇小屋前15留言数据总数量为100条，其中签单数量为2条，UC 为50条，签单0，百度为50条签单2.
                $content = $pjnm.'15天留言数据总数量为'.$pjsum.'条，其中签单数量为'.$pjsucc.'条，'.implode('，', $pj[$key]);

                _sendMaster($content,'classf15report_statistics');
            }
            // echo $content;exit;
            echo 'succ';
            exit;
        }else{
            //取项目 签约单 总数量 平均签约成本
            $pj_id = key($pj);
            $map['project_id'] = $pj_id;
            $pjnm = db('call_project_list')->where(['id'=>$pj_id])->value('col1');
            $pjsum = db('call_custom')->where($map)->whereTime('note_time','-15 days')->count();
            $pjsucc = db('call_custom')->where($map)->whereTime('note_time','-15 days')->where(['category'=>6])->count();

            //好奇小屋前15留言数据总数量为100条，其中签单数量为2条，UC 为50条，签单0，百度为50条签单2.
            $content .= $pjnm.'15天留言数据总数量为'.$pjsum.'其中签单数量为'.$pjsucc.'条，'.implode('，', $pj[$pj_id]);
        }

        _sendMaster($content,'classf15report_statistics');
        echo 'succ';exit;


    }

    /**
     * [previousfeereportTask 往年同期成本分析 月1号 管理员]
     * @return [type] [description]
     */
    public function previousfeereportTask()
    {
        $map = [];
        //去年的这个时间的下一个月 如：去年的3月
        // $lasty =date('Y-m-d H:i:s', strtotime("-1 year"));
        $lasty =date('Y-m-d H:i:s', time()-86400*8);
        $lastyam = strtotime("$lasty+1 months");
        $mtime[0] = $lasty;
        $mtime[1] = date('Y-m-d H:i:s',$lastyam);

        $data_list = db('call_custom')->where($map)->whereTime('note_time','between',$mtime)->field('*,avg(fee) as fees')->group('project_id')->select();
        //好奇小屋，往年同期单条数据平均成本为100元，包含节日：元宵节
        $pj =[];
        foreach ($data_list as $key => $value) {
            $pj[$value['project_id']][] = $value;
        }
        if (!$pj) {
            echo '执行成功，无匹配数据';exit;
        }
        $content = '';
        //项目
        if (count($pj)>1) {//多项目组成
            foreach ($pj as $key => $value) {
                //好奇小屋，往年同期单条数据平均成本为100元，包含节日：元宵节
                $pjnm = db('call_project_list')->where(['id'=>$key])->value('col1');
                $map['project_id'] = $key;

                $m1['GregorianDateTime'][0] = 'between time';
                $m1['GregorianDateTime'][1][0] = $mtime[0];
                $m1['GregorianDateTime'][1][1] = $mtime[1];
                $festivals = db('call_calendar')->field('GROUP_CONCAT(GJie) as GJies ,GROUP_CONCAT(LJie) as LJies ')->where($m1)->group('LYear')->select();
                $festival = '';
                foreach ($festivals as $k => $v) {
                    $festival .= str_replace(',',' ', $v['GJies'].$v['LJies']) ;
                }

                $content = $pjnm.'，往年同期单条数据平均成本为'.number_format($value[0]['fees'],1).'元，包含节日：'.$festival;

                _sendMaster($content,'previousfeereport_statistics');
            }
            // echo $content;exit;
            echo 'succ';
            exit;
        }else{
            //往年同期单条数据平均成本为100元，包含节日：元宵节
            $pj_id = key($pj);
            $map['project_id'] = $pj_id;
            $pjnm = db('call_project_list')->where(['id'=>$pj_id])->value('col1');

            $m1['GregorianDateTime'][0] = 'between time';
            $m1['GregorianDateTime'][1][0] = $mtime[0];
            $m1['GregorianDateTime'][1][1] = $mtime[1];
            $festivals = db('call_calendar')->field('GROUP_CONCAT(GJie) as GJies ,GROUP_CONCAT(LJie) as LJies ')->where($m1)->group('LYear')->select();
            $festival = '';
            foreach ($festivals as $k => $v) {
                $festival .= str_replace(',',' ', $v['GJies'].$v['LJies']) ;
            }

            $content = $pjnm.'，往年同期单条数据平均成本为'.number_format($pj[$pj_id][0]['fees'],1).'元，包含节日：'.$festival;
        }
        _sendMaster($content,'previousfeereport_statistics');
        echo 'succ';exit;
    }
    /**
     * [customAreaTask 地区补充]
     * @return [type] [description]
     */
    public function customAreaTask()
    {
        $map['city'] = null;
        $area = db('call_custom')->field('id,name,note_area,province,city')->where($map)->select();
        foreach ($area as $key => $value) {
            if (strpos($value['note_area'],'省')) {
                $areas = explode('省', $value['note_area']);
            }
            $mm['area_name'] = array('like','%'.$areas[1].'%');
            $citycode = db('packet_common_area')->where($mm)->value('area_code');
            $m['id'] = $value['id'];
            $data['city'] = $citycode;
            $data['province'] = db('packet_common_area')->where(['area_code'=>$citycode])->value('parent_code');

            db('call_custom')->where($m)->update($data);
        }
        echo 'succ';exit;
    }

    /**
     * [updateCallLog 更新呼叫日志]
     * @return [type] [description]
     */
    public function updateCallLog()
    {
        //查找要更新的数据
        $map['status'] = 0;
        $map['code'] = array('neq','');
        // $map['id'] = 39;
        $info = db('call_log')->where($map)->select();
        error_log('updateCallLog:'.time().':'.var_export($info,1),3,'/data/http/ringup/public/task.log');
        //拉接口
        foreach ($info as $key => $value) {
            $params['transactionId'] = $value['code'];
            $status = ring_up_new('getOneRecord',$params);

            $ret = json_decode($status,true);
            if ($ret['status']==0) {
                continue;
            }
            if ($ret['status']==1&&!isset($ret['msg']['data'])) {
                continue;
            }

            //处理更新
            switch ($ret['msg']['data'][0]['calltype']) {
                case 'outcall':
                    $type = '2';
                    break;
                case 'from-internal':
                    $type = '4';
                    break;
                
                default:
                    break;
            }
            $s['callType'] = $type;
            $s['timeLength'] = $ret['msg']['data'][0]['billsec'];
            $s['addtime'] = $ret['msg']['data'][0]['addtime'];
            $s['recordURL'] = $ret['msg']['data'][0]['userfield'];
            $s['status'] = 1;

            error_log('updateCallLog update:'.time().':'.var_export($s,1),3,'/data/http/ringup/public/task.log');
            db('call_log')->where(['code'=>$value['code']])->update($s);
        }
        echo 'succ';exit;
        
    }

    /**
     * [updateCallRecord 录音下载]
     * @return [type] [description]
     */
    // public function updateCallRecord()
    // {

    // }

    /**
     * [employTask 分配任务提醒]
     * @return [type] [description]
     */
    public function employTask()
    {
        //分配任务
        //提醒 第一、二天 3次 第三、四、五提醒1次
        $m['status'] = 1;
        $ent = time();
        $date=floor((strtotime($enddate)-strtotime($startdate))/86400);
        $task = db('call_alloc_log')->where($m)->select();
        $currH = date('t',time());
        foreach ($task as $key => $value) {
            if (floor(($ent-$value['create_time'])/86400) <= 5) {
                $diff = floor(($ent-$value['create_time'])/86400);
                switch ($diff) {
                    case 1:
                        // 9点 12点 17点
                        notice_log('task',$value['user_id'],['custom'=>$value['custom_id']],true);
                        break;
                    case 2:
                        // 9点 12点 17点
                        notice_log('task',$value['user_id'],['custom'=>$value['custom_id']],true);
                        break;
                    case 3:
                        // 12点 
                        if ($currH>10&&$currH<17) {
                            notice_log('task',$value['user_id'],['custom'=>$value['custom_id']],true);
                        }

                        break;
                    case 4:
                        // 12点 
                        if ($currH>10&&$currH<17) {
                            notice_log('task',$value['user_id'],['custom'=>$value['custom_id']],true);
                        }
                        break;
                    case 5:
                        // 12点 
                        if ($currH>10&&$currH<17) {
                            notice_log('task',$value['user_id'],['custom'=>$value['custom_id']],true);
                        }
                        break;
                    
                    default:
                        # code...
                        break;
                }
            }
        }
    }

    /**
     * [roleCallTask 部门通话]
     * @return [type] [description]
     */
    public function roleCallTask()
    {
        
        $info = [
            'roleCallSetting'=>isset(plugin_config('wechat')['roleCallSetting'])?plugin_config('wechat')['roleCallSetting']:''
        ];

        if (!$info['roleCallSetting']) {
            echo '请配置达标时长';exit;
        }
        $m = date('m');
        $d = date('d');

        //过滤用户
        $manger = db('admin_user')->where(['is_maner'=>1])->colunm('id');

        $map = [];
        if ($manger) {
            $map['user_id'] = array('not in',array_column($manger, 'id'));
        }
        $data_list = db('call_log')->where()->whereTime('create_time', 'today')->field('*,SUM(timeLength) as timeLengths,count(*) as call_count')->group('user_id')->select();

        $ret = [];//组名 分配数 未满人 当日及时联系 7天未联系的具体人
        foreach ($data_list as $key => $value) {
           

           if (isset($ret[$value['role_id']]['standard_num'])) {
            $ret[$value['role_id']]['standard_num'] += $value['timeLengths']>$info['roleCallSetting']*60?1:0;
           }else{
            $ret[$value['role_id']]['standard_num'] = 0;
           }

            

            $ret[$value['role_id']]['name'] = db('admin_role')->where(['id'=>$value['role_id']])->value('name');

            if (isset($ret[$value['role_id']]['alloc'])) {
                $ret[$value['role_id']]['alloc'] += db('call_alloc_log')->where(['user_id'=>$value['user_id']])->whereTime('create_time', 'today')->count();
            }else{
                $ret[$value['role_id']]['alloc'] = 0;
            }
            
            if (isset($ret[$value['role_id']]['standard_person'])) {
                $ret[$value['role_id']]['standard_person'] .= ' '.$value['timeLengths']>$info['roleCallSetting']*60? db('admin_user')->where(['id'=>$value['user_id']])->value('nickname'):'';
            }else{
                $ret[$value['role_id']]['standard_person']  = '';
            }
            

            if (isset($ret[$value['role_id']]['contact'])) {
                $ret[$value['role_id']]['contact'] += (db('call_alloc_log')->where(['user_id'=>$value['user_id']])->whereTime('create_time', 'today')->count())-(db('call_log')->where(['user_id'=>$value['user_id']])->whereTime('create_time', 'today')->count())<0?0:1;
            }else{
                $ret[$value['role_id']]['contact'] = 0;
            }
            

            $m3['a.create_time'] = array('gt',time()-86400*7);
            $m3['a.user_id'] = $value['user_id'];
            $day_nocontact = db('call_alloc_log')->alias('a')->field('a.custom_id,a.user_id')->join(' call_log c',' c.alloc_log_id = a.id','LEFT')->where($m3)->group('a.id')->count();

            if (isset($ret[$value['role_id']]['day_nocontact'])) {
                $ret[$value['role_id']]['day_nocontact'] .= ' '.db('admin_user')->where(['id'=>$value['user_id']])->value('nickname').$day_nocontact.'条';
            }else{
                $ret[$value['role_id']]['day_nocontact']= '';
            }
            


        }

        if ($ret) {
            foreach ($ret as $key => $value) {
                $content = '';


                //例如 12月23号，张三组今日总分配新数据为60条，未满100分钟人员：李四、王二，当日数据为及时联系：1， 7天未联系数据：李刚9条
                $content = $m.'月'.$d.'号，'.$value['name'].'今日总分配新数据为'.$value['alloc'].'条，'.'未满100分钟人员：'.$value['standard_person'].'，当日数据为及时联系'.$value['contact'].'，7天未联系数据：'.$value['day_nocontact'];

                // echo $content;exit;
                _sendMaster($content,'roleCall_statistics');
            }
            
        }
        
        echo 'succ';exit;



    }
    /**
     * [_sendMaster 通用发送方法]
     * @param  [type] $msg [消息]
     * @param  [type] $tag [标识报表]
     * @return [type]       [description]
     */
    function _sendMaster($msg, $tag)
    {
        $touser = db('admin_user')->where(['id'=>1])->find();
        //push
        $toparty = [];
        $totag = [];
        $user = [];

        //根据标识配置
        $wechat = isset(plugin_config('wechat')[$tag])?plugin_config('wechat')[$tag]:'';

        if (!$wechat) {
            array_push($user, $touser['wechat_name']);
        }else{
            foreach ($wechat as $key => $value) {
                array_push($user, $value);
            }
        }
        


        $result = plugin_action('Wechat/Wechat/send',[$user , $toparty, $totag, 'text', $msg]);

    }

    /**
     * [recoverTask description]
     * @return [type] [description]
     */
    public function ondateTask()
    {
        //取需要通知的数据
        $map['status'] = 0;
        // $map['ondate'] = array('lt',time());
        $sj = [time(),(time()+300)];
        $data = db('call_ondate')->where($map)->whereTime('ondate','between',$sj)->select();
        // $data = db('call_ondate')->where($map)->order('id desc')->limit(1)->select();
        //数据
        foreach ($data as $key => $value) {
            // $s['tags'] = 'ondate';
            // $s['user_id'] = $value['user_id'];
            // $s['title'] = '预约提醒';
            $s['custom'] = db('call_custom')->where(['id'=>$value['custom_id']])->value('name'); ;
            $s['ondate'] = date('Y-m-d H:i',$value['ondate']);
            $s['content'] = $value['note'];
            // $s['user_id'] = $value['user_id'];
            //提醒管理员 tags user_id title content 
            // print_r($s);exit;
            notice_log('ondate',$value['user_id'],$s,1);
        }

        echo 'succ';
        
    }

    public function testask()
    {
        // notice_log('recover',1,['custom'=>'1','project'=>1],true);

        error_log('tsnew task:'.time(),3,'/data/http/ringup/public/task.log');
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
