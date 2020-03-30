<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Payment as PaymentModel;
use app\call\model\Alloclg as AlloclgModel;
use app\call\model\Custom as CustomModel;
use \think\Request;
use \think\Db;
/**
 * 首页后台控制器
 */
class Task extends Admin
{

    /**
     * 菜单列表
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();
        $request = Request::instance();
        $params = $request->param();

        // $map['user_id'] = UID;
        // $map['status'] = 1;
        // // 数据列表
        // $data_list = AlloclgModel::where($map)->order('id desc')->paginate()->each(function($item, $key) use ($map){

        //     $item->mobile = replaceTel(db('call_custom')->where(['id'=>$item['custom_id']])->value('mobile'));
        //     $item->alloc_count = db('call_alloc_log')->where(['custom_id'=>$item['custom_id']])->count();
        // });

        
        $map['call_alloc_log.user_id'] = UID;
        $map['call_alloc_log.status'] = 1;
        if (isset($map['tag'])) {
            if ($map['tag'][1]=='will_contact_custom_count') {
                $map['call_log.alloc_log_id'] = array('eq',null);
            }
            if ($map['tag'][1]=='pass_second_contact_custom_count') {
                $map['call_log.timeLength'] = array('eq',0);
                $map['call_alloc_log.create_time'] = array('gt',time()-86400*2);
            }
            if ($map['tag'][1]=='no_contact_custom_count') {
                $map['call_log.timeLength'] = array('eq',0);
            }
            unset($map['tag']);
        }else{
            if (isset($params['tag'])) {
                if ($params['tag']=='will_contact_custom_count') {
                    $map['call_log.alloc_log_id'] = array('eq',null);
                }
                if ($params['tag']=='pass_second_contact_custom_count') {
                    $map['call_log.timeLength'] = array('eq',0);
                    $map['call_alloc_log.create_time'] = array('gt',time()-86400*2);
                }
                if ($params['tag']=='no_contact_custom_count') {
                    $map['call_log.timeLength'] = array('eq',0);
                }
            }

        }
        $data_list = AlloclgModel::view('call_alloc_log', '*')->view('call_log', 'alloc_log_id,timeLength', 'call_alloc_log.id=call_log.alloc_log_id','LEFT')->where($map)->order('call_alloc_log.id desc')->group('call_alloc_log.id')->paginate()->each(function($item, $key) use ($map){
            $item->mobile = replaceTel(db('call_custom')->where(['id'=>$item['custom_id']])->value('mobile'));
            $item->alloc_count = db('call_alloc_log')->where(['custom_id'=>$item['custom_id']])->count();
        });
        

        $mpsel = '';
        if (isset($params['tag'])) {
            $mpsel = $params['tag'];
        }
        // print_r($map);exit;

        // 分页数据
        $page = $data_list->render();
    
        $btn_access = [
            'title' => '客户信息',
            // 'icon'  => 'fa fa-fw fa-whatsapp ',
            'icon'  => 'fa fa-fw fa-user ',
            'class' => 'btn btn-xs btn-default ajax-get get-user-info',
            'href' => url('call',['id'=>'__id__'])
        ];

        $btn_discard = [
            'title' => '丢弃公海',
            'icon'  => 'fa fa-fw  fa-trash-o',
            'class' => 'btn btn-xs btn-default ajax-get confirm',
            'href' => url('discard',['id'=>'__id__'])
        ];

        $btn_getback = [
            'title' => '回收',
            'icon'  => 'fa fa-fw  fa-trash-o',
            'class' => 'btn btn-xs btn-default ajax-get confirm',
            'href' => url('getback')
        ];

        $btn_call = [
            'title' => '呼叫',
            'icon'  => 'fa fa-fw fa-phone',
            'class' => 'btn btn-xs btn-default ajax-get',
            'href' => url('ringup',['id'=>'__id__'])
        ];
        $btn_hangup = [
            'title' => '分机挂断',
            'icon'  => 'fa fa-fw fa-microphone-slash',
            'class' => 'btn btn-xs btn-default ajax-get',
            'href' => url('hangup',['id'=>'__id__'])
        ];

        $msel = [
            'will_contact_custom_count'=>'新任务未联系客户',
            'pass_second_contact_custom_count'=>'超2天未联系客户',
            'no_contact_custom_count'=>'新任务未接通客户'
        ];

        $btn_msg = [
            'title' => '短信',
            'icon'  => 'fa fa-fw fa-navicon',
            'class' => 'btn btn-xs btn-default ajax-get',
            'href' => url('msg',['id'=>'__id__'])
        ];
        // print_r($msel);exit;
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearchArea([
                ['select', 'tag', '类型', '', $mpsel, $msel],

            ])
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                // ['user', '员工'],
                ['custom', '客户'],
                ['mobile', '电话'],
                ['alloc_count', '分配次数'],
                ['create_time', '创建时间','datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->addTopButton('custom', $btn_getback)
            ->addRightButton('custom',$btn_access, ['title' => '客户信息'])
            ->addRightButton('custom',$btn_discard)
            ->addRightButton('custom',$btn_call,['title'=>'呼叫','area' => ['200px', '200px']])
            ->addRightButton('custom',$btn_hangup,['title'=>'分机挂断','area' => ['200px', '200px']])
            ->addRightButton('custom',$btn_msg,['title'=>'短信','area' => ['800px', '500px']])
            ->setRowList($data_list)// 设置表格数据
            ->raw('custom') // 使用原值
            ->fetch(); // 渲染模板
        
    }

    /**
     * [msg 短信]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function msg($id)
    {
        $map['id'] = $id;
        $custom_id = AlloclgModel::where($map)->value('custom_id');
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $mobile = db('call_custom')->where(['id'=>$custom_id])->value('mobile');
            if (!$mobile) {
                $this->error('手机号不对',null,'_close_pop');
            }
            $content['content'] = $data['title'];
            if (!$content['content']) {
                $this->error('短信内容不对',null,'_close_pop');
            }
            //发送
            $result = plugin_action('Sms/Sms/send', [$mobile, $content, 'SMS_186599008']);
            if($result['code']){
                $this->error('发送失败，错误代码：'. $result['code']. ' 错误信息：'. $result['msg'],null,'_close_pop');
            } else {
                //生成日志
                $s['content'] = $data['title'];
                $s['custom_id'] = $custom_id;
                $s['user_id'] = UID;
                $s['create_time'] = time();
                db('call_msg_log')->insert($s);
                $this->success('发送成功',null,'_close_pop');
            }
            
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '内容'],
            ])
            ->setBtnTitle('submit', '发送')
            ->setFormData()
            ->fetch();
    }
    /**
     * 回收
     * @return [type] [description]
     */
    public function getback()
    {
        $ids        = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        //为空判断
        if ($ids === null) $this->error('缺少参数');
        //客户状态修改
        $map['id'] = array('in',$ids);
        $cids = db('call_alloc_log')->where($map)->column('custom_id');
        $map1['id'] = array('in',$cids);
        db('call_custom')->where($map1)->update(['status'=>1]);
        //清空分配日志
        db('call_alloc_log')->where($map)->update(['status'=>2]);//2为特殊处理掉的分配


    }
    public function hangup($id=null)
    {
        if ($id === null) $this->error('缺少参数');

        //手机号
        // $params['agent'] = session('user_auth_extension')?session('user_auth_extension')['exten']:'';
        // $params['agent'] = '8801';
        $params['agent'] = get_extension(UID)['extension'];
        if (!$params['agent']) {
            $this->error('没有绑定分机号');
        }
        $status = ring_up_new('hangUp',$params);
        //弹框
        // $ret = json_decode($status,true);
        // if ($ret['status']==0) {
        //     $this->error($ret['msg'], null, '_close_pop');
        // }
        // if ($ret['status']==true&&!isset($ret['data'])) {
        //     $this->error($ret['msg'], null, '_close_pop');
        // }
        
        // 显示添加页面
        return ZBuilder::make('form')
            ->fetch('hangup');
    }
    /**
     * [ringup 呼叫]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function ringup($id = null)
    {
        if ($id === null) $this->error('缺少参数');


        $custom_id = db('call_alloc_log')->where(['id'=>$id])->value('custom_id');
        //手机号
        $params['telNum'] = get_mobile($custom_id)['mobile'];
        if (!$params['telNum']) {
            $this->error('手机号不对');
        }
        // $params['telNum'] = '17321023222';
        //呼叫 telNum=135xxxxxxxx&extNum=801&transactionId=xxxxxxxxxxx
        // $params['extNum'] = session('user_auth_extension')?session('user_auth_extension')['exten']:'';
        // $params['extNum'] = '8801';
        $params['extNum'] = get_extension(UID)['extension'];
        if (!$params['extNum']) {
            $this->error('没有绑定分机号');
        }
        $params['transactionId'] = get_auth_call_sign(['uid'=>UID,'calltime'=>time()]);
        // $params['transactionId'] = UID.time();
        // print_r($params);exit;
        $status = ring_up_new('ClickCall',$params);
        //弹框
        $ret = json_decode($status,true);
        if ($ret['status']==0) {
            // return json(['code' => 0, 'msg' => $ret['msg']]);
            $this->error($ret['msg'], null, '_close_pop');
        }
        if ($ret['status']==true&&!isset($ret['data'])) {
            // return json(['code' => 1, 'msg' => $ret['msg'] ]);
            $this->error($ret['msg'], null, '_close_pop');
        }
        //创建空的通话记录
        $s['alloc_log_id'] = $id;
        $s['user_id'] = UID;
        $s['callType'] = 2;//按实际更新
        $s['calledNum'] = $params['telNum'];
        $s['create_time'] = time();
        $s['custom_id'] = $custom_id;
        $s['extension'] = $params['extNum'];
        $s['code'] = $params['transactionId'];

        db('call_log')->insert($s);

        // 显示添加页面
        return ZBuilder::make('form')
            ->fetch('ringup');


    }

    /**
     * [discard 丢弃]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function discard($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        $custom_id = db('call_alloc_log')->where(['id'=>$id])->value('custom_id');
        //客户更新 1 正常 2 回收 3 公海 异常数据
        db('call_custom')->where(['id'=>$custom_id])->update(['status'=>3]);
        db('call_alloc_log')->where(['id'=>$id])->update(['status'=>0]);
        //公海数据更新
        $custom = db('call_custom')->where(['id'=>$custom_id])->find(); 
        $data['project_id'] = $custom['project_id'];
        $data['custom_id'] = $custom_id;
        $data['create_time'] = time();
        $data['status'] = 9;
        $data['source'] = $custom['source'];
        $data['custom'] = $custom['name'];
        $data['note_time'] = $custom['note_time'];
        $data['mobile'] = $custom['mobile'];

        $alloc = db('call_alloc_log')->where(['id'=>$id])->find();//$alloc_id
        $data['alloc_time'] = $alloc['create_time'];
        $data['alloc_count'] = db('call_alloc_log')->where(['custom_id'=>$custom_id])->count();

        $calllog = db('call_log')->where(['custom_id'=>$custom_id])->count();
        $data['call_count'] = $calllog;

        $alloc_time = $alloc['create_time'];//分配当天 
        $ct = strtotime(date('Y-m-d',$alloc['create_time']));
        $first_time[0] = 'between time';
        $first_time[1][0] = $ct;
        $first_time[1][1] = $ct+86400;
        $map1['custom_id'] = $custom_id;
        $map1['timeLength'] = array('gt',1);
        $map1['create_time'] = $first_time;

        $second_time[0] = 'between time';
        $second_time[1][0] = $ct+86400;
        $second_time[1][1] = $ct+86400*2;
        $map2['custom_id'] = $custom_id;
        $map2['timeLength'] = array('gt',1);
        $map2['create_time'] = $second_time;

        $third_time[0] = 'between time';
        $third_time[1][0] = $ct+86400*2;
        $third_time[1][1] = $ct+86400*3;
        $map3['custom_id'] = $custom_id;
        $map3['timeLength'] = array('gt',1);
        $map3['create_time'] = $third_time;

        $fourth_time[0] = 'between time';
        $fourth_time[1][0] = $ct+86400*3;
        $fourth_time[1][1] = $ct+86400*4;
        $map4['custom_id'] = $custom_id;
        $map4['timeLength'] = array('gt',1);
        $map4['create_time'] = $fourth_time;

        $fifth_time[0] = 'between time';
        $fifth_time[1][0] = $ct+86400*4;
        $fifth_time[1][1] = $ct+86400*5;
        $map5['custom_id'] = $custom_id;
        $map5['timeLength'] = array('gt',1);
        $map5['create_time'] = $fifth_time;

        $data['first_standard'] = db('call_log')->where($map1)->count();
        $data['second_standard'] = db('call_log')->where($map2)->count();
        $data['third_standard'] = db('call_log')->where($map3)->count();
        $data['fourth_standard'] = db('call_log')->where($map4)->count();
        $data['fifth_standard'] = db('call_log')->where($map5)->count();
        $data['user_id'] = UID;
        //是否异常
        $data['is_bad'] = time()-strtotime(($ct+86400*5))>0?0:1;
        //只存在一条数据
        if (db('call_recover_data')->where(['custom_id'=>$custom_id])->find()) {
            $this->success('操作成功', null, '_parent_reload');
        }
        db('call_recover_data')->insert($data);
        $this->success('操作成功', null, '_parent_reload');
        
    }
    /**
     * [call description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function call($id = null)
    {
        if ($id === null) $this->error('缺少参数');


        $custom_id = db('call_alloc_log')->where(['id'=>$id])->value('custom_id');
        $custom = db('call_custom')->where(['id'=>$custom_id])->find();
        $category = db('call_custom_cat')->column('id,title');

        if ($this->request->isPost()) {
            //验证签约后不能再修改
            if ($custom['category']==6) {
                $this->error('客户已签约不能修改！请联系管理员');
            }
            // 表单数据
            $data = $this->request->post();
            $data['update_time'] =  time();
            $data['id'] = $custom_id;
            $data['category'] = $data['category'];
            if ($props = CustomModel::update($data)) {
                //生成日志
                $s['create_time'] = time();
                $s['category'] = $data['category'];
                $s['custom_id'] = $custom_id;
                $s['export_time'] = $custom['create_time'];
                $s['employ_id'] = UID;
                db('call_report_custom_cat')->insert($s);
                if ($data['category']==6) {//当签约时
                    //生成推送任务 $tag $content $aciton
                    $ep = db('admin_user')->where(['id'=>UID])->find();
                    $admin = db('admin_user')->where(['id'=>1])->find();
                    $content = $custom['name'].'已签约,操作员工'.$ep['nickname'].date('Y-m-d H:i',time());//张三|客户名称]已签约，操作员工[李四|操作人]，[2020-2-6|修改时间
                    make_crontab('push_msg_24','call/index',$content,$admin['wechat_name']);
                }
                
                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }
        }

        $calllog = db('call_report_custom_cat')->where(['custom_id'=>$custom_id,'employ_id'=>UID])->select();
        foreach ($calllog as $key => &$value) {
            $value['custom'] = $custom['name'];
            $value['create_time'] = date('Y-m-d H:i',$value['create_time']);
            $value['category'] = $category[$value['category']];
        }
        $aba = db('call_speechcraft')->where(['status'=>1])->order('sort ASC')->select(); 
        foreach ($aba as $key => &$value) {
            $value['custom'] = $value['title'];
            $value['create_time'] = $value['tags'];
            $value['category'] = $value['content'];
        }
        $custom['calllog']['body'] = $calllog;
        $custom['calllog']['header'] = ['客户','分类','时间'];
        $custom['aba']['body'] = $aba;
        $custom['aba']['header'] = ['标题','内容','标识'];
        // print_r($calllog);exit;
        //用户信息
        return ZBuilder::make('form')
            ->addFormItems([
                ['static','name', '客户名称'],
                ['static','tel', '客户电话'],
                ['static','mobile', '客户手机'],
                ['static','fee', '成本'],
                ['static','source', '来源'],
                ['static','email', '邮箱'],
                ['static','note_time', '记录时间'],
                ['static','note_area', '记录地区'],
                ['select', 'category', '设置客户分类', '', $category],
                ['mtable', 'calllog', '客户分类轨迹'],
                ['mtable', 'aba', '销售话术']
                
            ])
            ->setFormData($custom)
            ->layout(['tel' => 3, 'name' => 3, 'mobile' => 3, 'source' => 3,'email' => 3, 'address' => 3,'note_time'=>3,'note_area'=>3,'fee'=>3])
            ->fetch();
        // //通话 
        // $data['phone'] = isset(get_mobile($custom_id)['mobile'])?get_mobile($custom_id)['mobile']:get_mobile($custom_id)['tel'];
        // $data['callback'] = 'cb_callout';
        
        // $ret = ring_up('callout',$data);

        // if ($ret) {
        //     $result = [];
        //     preg_match_all("/(?:\()(.*)(?:\))/i",$ret, $result); 
        //     $json =json_decode($result[1][0],true);

        //     if ($json['status']==1) {
        //         //显示客户信息
        //         echo '<h1>呼叫成功</h1>';exit;
        //     }else{
        //         echo '<h1>呼叫失败</h1>';exit;
        //     }
        // }
        
    }
    

    /**
     * 设置用户状态：删除、禁用、启用
     * @param string $type 类型：delete/enable/disable
     * @param array $record
     * @author zg
     * @return mixed
     */
    public function setStatus($type = '', $record = [])
    {
        $ids        = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $menu_title = AuthModel::where('id', 'in', $ids)->column('custom');
        return parent::setStatus($type, ['call_auth_'.$type, 'call', 0, UID, implode('、', $menu_title)]);
    }
    /**
     * 快速编辑
     * @param array $record 行为日志
     * @author zg
     * @return mixed
     */
    public function quickEdit($record = [])
    {
        $id = input('post.pk', '');
        return parent::quickEdit(['call_auth_edit', 'call', 0, UID, $id]);
    }
   
    
}