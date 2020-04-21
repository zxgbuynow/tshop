<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\user\model\Role as RoleModel;

/**
 * 首页后台控制器
 */
class Home extends Admin
{

    /**
     * 菜单列表
     * @return mixed
     */
    public function index()
    {
        
        $slidecolor = [
        	'90'=>['color'=>'background-color: rgba(251, 98, 96, 1)','max'=>100,'min'=>90],
        	'70'=>['color'=>'background-color: rgba(255, 169, 76, 1)','max'=>89,'min'=>70],
        	'60'=>['color'=>'background-color: rgba(75, 206, 208, 1)','max'=>69,'min'=>60],
        	'0'=>['color'=>'','max'=>59,'min'=>0]
        ];

        // $circlecolor = [
        // 	'90'=>'background-color: rgba(251, 98, 96, 1)',
        // 	'70'=>'background-color: rgba(255, 169, 76, 1)',
        // 	'60'=>'background-color: rgba(75, 206, 208, 1)',
        // 	'0'=>''
        // ]
        //待办事项
        if (UID==1) {
            $m4['a.status'] = 1;
            $m4['c.alloc_log_id'] = array('eq',null);
            $will_contact_custom_count = db('call_alloc_log')->alias('a')->field('a.custom_id,a.user_id')->join(' call_log c',' c.alloc_log_id = a.id','LEFT')->where($m4)->whereTime('a.create_time', 'today')->group('a.id')->count();//当天分配没有拨打记录
            // echo db('call_alloc_log')->getlastsql();exit;
        	// $will_contact_custom_count = 0;//新任务未联系客户
        	// $pass_second_contact_custom_count = 0;//超2天未联系客户
            $m1['c.status'] = 1;
            $m1['a.status'] = 1;
            // $m1['c.timeLength'] = array('eq',0);
            // $m1[] = ['a.create_time','gt',time()-86400*2];
            $m1['c.create_time'] = array('lt',time()-86400*2);
            $pass_second_contact_custom_count = db('call_alloc_log')->alias('a')->field('a.custom_id,a.user_id')->join(' call_log c',' c.alloc_log_id = a.id','LEFT')->where($m1)->group('a.id')->count();
            
        	// $no_contact_custom_count = 0;//新任务未接通未达标客户
            $m2['a.status'] = 1;
            $m2['c.timeLength'] = array('eq',0);
            $no_contact_custom_count = db('call_alloc_log')->alias('a')->field('a.custom_id,a.user_id')->join(' call_log c',' c.alloc_log_id = a.id','LEFT')->where($m2)->group('a.id')->count();//没有接通的

        	// $ondate_count = 0;//预约提醒
            $m3['status'] = 0;
            // $m3['user_id'] = UID; 
            $ondate_count = db('call_ondate')->where($m3)->count();
        }else{
            //超2天未联系客户：从最后1次电话联系时间开始计算，超过48小时的客户。（包含未接通客户）->有拨打记录，超过2天的（包含没有接通）
            //未接通客户：有拨打记录，但是没有接通的客户。（所有时间）->没有接通的
            //未联系客户：当天分配的新数据，没有拨打记录的客户。->1.当天分配没有拨打记录
            $userin =  db('admin_user')->where(['id'=>UID,'is_maner'=>1 ])->find();
            if ($userin) {
                $userids = db('admin_user')->where(['role'=>$userin['role'] ])->column('id');
            }
            //招商部 下面只设置职位 （目前只有主管和组员）
            $m4['a.status'] = 1;
            // $m4['c.timeLength'] = array('eq',0);
            $m4['a.user_id'] = UID;
            if ($userin) {
                $m4['a.user_id'] = array('in',$userids);
            }
            $m4['c.alloc_log_id'] = array('eq',null);
            $will_contact_custom_count = db('call_alloc_log')->alias('a')->field('a.custom_id,a.user_id')->join(' call_log c',' c.alloc_log_id = a.id','LEFT')->where($m4)->whereTime('a.create_time', 'today')->group('a.id')->count();//当天分配没有拨打记录
        	// $will_contact_custom_count = 0;//新任务未联系客户 新客户是没有通话时长的
            $m1['c.status'] = 1;
            $m1['a.status'] = 1;
            // $m1['c.timeLength'] = array('eq',0);
            $m1['a.user_id'] = UID;
            if ($userin) {
                $m1['a.user_id'] = array('in',$userids);
            }
            // $m1[] = ['a.create_time','gt',time()-86400*2];
            $m1['c.create_time'] = array('lt',time()-86400*2);
            $pass_second_contact_custom_count = db('call_alloc_log')->alias('a')->field('a.custom_id,a.user_id')->join(' call_log c',' c.alloc_log_id = a.id','LEFT')->where($m1)->group('a.id')->count();//有拨打记录，超过2天的（包含没有接通）

        	// $pass_second_contact_custom_count = 0;//超2天未联系客户 分配后没有通话时长的
            $m2['a.status'] = 1;
            $m2['c.timeLength'] = array('eq',0);
            $m2['a.user_id'] = UID;
            if ($userin) {
                $m2['a.user_id'] = array('in',$userids);
            }
            $no_contact_custom_count = db('call_alloc_log')->alias('a')->field('a.custom_id,a.user_id')->join(' call_log c',' c.alloc_log_id = a.id','LEFT')->where($m2)->group('a.id')->count();//没有接通的

        	// $no_contact_custom_count = 0;//新任务未接通未达标客户
            $m3['status'] = 0;
            $m3['user_id'] = UID;

            $ondate_count = db('call_ondate')->where($m3)->count();

        	// $ondate_count = 0;//预约提醒
        }
        $info['user_id'] = UID;
        $_is_show = 0;
        if (UID==1) {
            $_is_show = 1;
        }
        if (UID>1) {
           if (db('admin_user')->where(['id'=>UID,'is_maner'=>1 ])->find()){
                $_is_show = 1;
           }
        }
        $info['_is_show'] = $_is_show;
        $info['will_contact_custom_count'] = $will_contact_custom_count;
        $info['pass_second_contact_custom_count'] = $pass_second_contact_custom_count;
        $info['no_contact_custom_count'] = $no_contact_custom_count;
        $info['ondate_count'] = $ondate_count;
        $info['will_contact_custom_notice'] = isset(plugin_config('wechat')['will_contact_custom_notice'])?plugin_config('wechat')['will_contact_custom_notice']:'';
        $info['pass_second_contact_custom_notice'] = isset(plugin_config('wechat')['pass_second_contact_custom_notice'])?plugin_config('wechat')['pass_second_contact_custom_notice']:'';
        $info['no_contact_custom_notice'] = isset(plugin_config('wechat')['no_contact_custom_notice'])?plugin_config('wechat')['no_contact_custom_notice']:'';
        //排名
        $call_count = [];
        $calls = db('call_log')->whereTime('create_time', 'month')->field('sum(timeLength) as times,user_id')->order('times desc')->group('user_id')->limit(8)->select();
        $i = 1;
        $t = 10;//以最高通时数加10分钟做基数
        foreach ($calls as $key => &$value) {
        	$value['times'] = ceil($value['times']/60);
        	if ($key==0) {
        		$t +=$value['times'];
        	}
        	$value['user'] = get_nickname($value['user_id']);
        	
        	$value['aa'] = $i;
        	$value['width'] = (number_format($value['times']/$t,1)*100) ;
        	foreach ($slidecolor as $key => $v) {
        		if ($value['width'] >= $v['min'] && $value['width'] <= $v['max']) {
			        $value['color'] = $v['color'];
			    }
        	}
        	$i++;
        }
        //转化率
        $m['status'] = 1;
        $alloc = db('call_alloc_log')->field('count(*) as cts,user_id,group_concat(custom_id) as  custom_ids')->where($m)->group('user_id')->select();
        // print_r($alloc);exit;
        $b = 1;
        foreach ($alloc as $key => &$value) {
        	$value['index'] = $b;
        	$value['user'] = get_nickname($value['user_id']);
        	$mb['id'] = array('in',$value['custom_ids']);
        	$mb['category'] = 6;
        	$num = db('call_custom')->where($mb)->count();
        	if ($num==0) {
        		unset($alloc[$key]);
        	}

        	$value['num'] = $num;
        	$value['percent'] = ceil($num/$value['cts'])*100;
        	$b++;
        }
        // 使用ZBuilder快速创建数据表格
        $this->assign('info',$info);
        $this->assign('call',$calls);
        $this->assign('alloc',$alloc);
        return ZBuilder::make('form')->fetch('index2'); // 渲染模板
        
    }

    
}