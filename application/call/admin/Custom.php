<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Custom as CustomModel;
use app\call\model\CustomEXLog as CustomEXLogModel;
use app\call\model\Recoverdt as RecoverdtModel;
use app\admin\model\Config as ConfigModel;
use app\call\model\Cat as CatModel;
use app\call\model\Alloclg as AlloclgModel;
use app\call\model\Alloc as AllocModel;
use think\Cache;
use think\Db;
use \think\Request;
/**
 * 首页后台控制器
 */
class Custom extends Admin
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
        $title = '客户列表';
        if (isset($params['tag'])) {
            $title = '客户列表(2天未联系结果)';
            if ($params['tag']=='pass_second_contact_custom_count') {
                
                //处理所有者 如果用户ID不在数据中则无结果
                if (isset($map['alloc_user'])) {
                    $mmmmm['nickname'] = $map['alloc_user'];
                    $alloc_users = db('admin_user')->where($mmmmm)->column('id');
                    
                    unset($map['alloc_user']);
                }

                if (UID!=1) {
                    $userin =  db('admin_user')->where(['id'=>UID,'is_maner'=>1 ])->find();
                    if ($userin) {
                        $userids = db('admin_user')->where(['role'=>$userin['role'] ])->column('id');
                    }
                    $m1['a.user_id'] = UID;
                    

                    
                    if ($userin) {
                        // $m1['a.user_id'] = array('in',$userids);
                        if ($alloc_users) {
                            $intersect = array_intersect($userids,$alloc_users);
                            $m1['a.user_id'] = '';
                            if ($intersect) {
                                $m1['a.user_id'] = array('in',$intersect);
                            }
                        }else{
                            $m1['a.user_id'] = array('in',$userids);
                        }
                        
                    }else{
                        if ($alloc_user) {
                            if (!in_array(UID, $alloc_user)) {
                                $m1['a.user_id'] = '';
                            }
                        }
                        
                    }
                }else{
                    if (isset($alloc_users)) {
                        $m1['a.user_id'] = array('in',$alloc_users);
                    }
                    
                }
                
                $m1['a.status'] = 1;
                $m1['c.create_time'] = array('lt',time()-86400*2);
                
                $pass_second_contact_custom_count = db('call_alloc_log')->alias('a')->field('a.custom_id,a.user_id')->join(' call_log c',' c.alloc_log_id = a.id','LEFT')->where($m1)->group('a.id')->select();
                if ($pass_second_contact_custom_count) {
                    $map['id'] = array('in',array_column($pass_second_contact_custom_count, 'custom_id'));
                }else{
                    $map['id'] = '';
                }
                
            }
            
        }else{
            if (UID!=1) {
                $userin =  db('admin_user')->where(['id'=>UID,'is_maner'=>1 ])->find();
                if ($userin) {
                    $userids = db('admin_user')->where(['role'=>$userin['role'] ])->column('id');
                    $mm['status'] = 1;
                    $mm['user_id'] = array('in',$userids);

                    //处理所有者 如果权限组下用户ID在数据中则保留
                    if (isset($map['alloc_user'])) {
                        $mmmmm['nickname'] = $map['alloc_user'];
                        $alloc_users = db('admin_user')->where($mmmmm)->column('id');
                        $intersect = array_intersect($userids,$alloc_users);
                        
                        $mm['user_id'] = '';
                        if ($intersect) {
                            $mm['user_id'] = array('in',$intersect);
                        }

                        unset($map['alloc_user']);
                    }

                    
                    $customs = db('call_alloc_log')->where($mm)->column('custom_id');
                    $map['id'] = array('in',$customs);
                    
                }else{
                    $mm['status'] = 1;
                    $mm['user_id'] = UID;

                    //处理所有者 如果用户ID不在数据中则无结果
                    if (isset($map['alloc_user'])) {
                        $mmmmm['nickname'] = $map['alloc_user'];
                        $alloc_users = db('admin_user')->where($mmmmm)->column('id');
                        if (!in_array(UID, $alloc_user)) {
                            $mm['user_id'] = '';
                        }
                        unset($map['alloc_user']);
                    }
                    
                    $customs = db('call_alloc_log')->where($mm)->column('custom_id');
                    $map['id'] = array('in',$customs);
                    
                }
                if (!$map['id']) {
                    $map['id'] = '';

                }
            }else{
                if (isset($map['alloc_user'])) {
                    $mmmmm['nickname'] = $map['alloc_user'];
                    $alloc_users = db('admin_user')->where($mmmmm)->column('id');
                    $mm['user_id'] = array('in',$alloc_users);
                    $mm['status']=1;
                    $customs = db('call_alloc_log')->where($mm)->column('custom_id');
                    $map['id'] = array('in',$customs);
                    unset($map['alloc_user']);
                }
            }

        }
        //读取权限
        $roleid = db('admin_user')->where(['id'=>UID])->value('role');
        $access_moblie = db('admin_role')->where(['id'=>$roleid])->value('access_moblie');

        //资源状态
        if (isset($map['status'])) {
            $map['status'] = $map['status'];
            if ($map['status'][1]==0) {
                $map['status'] = array('in',['0','2','3']);
            }
        }
        // print_r($map);exit;
        // 数据列表
        $data_list = CustomModel::where($map)->order('id desc')->paginate()->each(function($item,$key) use ($access_moblie){
            $cate = db('call_custom_cat')->where(['id'=>$item['category']])->value('title');
            $item->categoryst = '<span title='.$cate.'> '. mb_substr($cate, 0, 6, 'gbk').'</span>';
            $item->access_mobile = $access_moblie;

            $item->alloc_status = $item['status']==1?'待分配':'已分配';

            $alloc_user = db('call_alloc_log')->where(['custom_id'=>$item['id'],'status'=>1])->value('user_id');
            $item->alloc_user = $item['status']==1?'无':($item['status']==2?get_employ($alloc_user):($item['status']==3?'公海':get_employ($alloc_user)));

            $item->project_id = db('call_project_list')->where(['id'=>$item['project_id']])->value('col1');

        });

        // 分页数据
        $page = $data_list->render();

        $btn_access = [
            'title' => '设置客户分类',
            'icon'  => 'fa fa-fw fa-navicon ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('cat',['id'=>'__id__'])
        ];
        $catelsbt = [
            'title' => '客户分类列表',
            'icon'  => 'fa fa-fw fa-navicon ',
            'href' => url('catls',['id'=>'__id__'])
        ];

        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('export',http_build_query($this->request->param()))
        ];

        $btn_call = [
            'title' => '呼叫',
            'icon'  => 'fa fa-fw fa-phone',
            'class' => 'btn btn-xs btn-default ajax-get',
            'href' => url('ringup',['id'=>'__id__'])
        ];

        $btn_msg = [
            'title' => '短信',
            'icon'  => 'fa fa-fw fa-envelope-o',
            'class' => 'btn btn-xs btn-default ajax-get',
            'href' => url('msg',['id'=>'__id__'])
        ];

        

        $catList = db('call_custom_cat')->where(['status'=>1])->column('id,title');
        $list_project = db('call_project_list')->where(['status'=>1])->column('id,col1');

        if (UID==1) {
            $searchArr = [
                ['text:6', 'name', '客户名称', 'like'],
                ['text:6', 'mobile', '客户手机', 'like'],
                ['text:6', 'source', '来源', 'like'],
                ['text:6', 'email', '邮箱', 'like'],
                ['text:6', 'address', '地址', 'like'],
                ['text:6', 'note_area', '记录地区', 'like'],
                ['text:6', 'fee', '成本', 'like'],
                ['text:6', 'extend_url', '推广链接', 'like'],
                ['text:6', 'policy', '政策', 'like'],
                ['text:6', 'alloc_user', '所有者', 'like'],
                ['select', 'category', '分类', '', '', $catList],
                ['select', 'project_id', '项目', '', '', $list_project],
                ['select', 'status', '资源状态', '','',['1'=>'待分配','0'=>'已分配']],
                ['daterange', 'note_time', '记录时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']]

            ];
        }else{
            $searchArr = [
                ['text:6', 'name', '客户名称', 'like'],
                ['text:6', 'mobile', '客户手机', 'like'],
                ['text:6', 'source', '来源', 'like'],
                ['text:6', 'email', '邮箱', 'like'],
                ['text:6', 'address', '地址', 'like'],
                ['text:6', 'note_area', '记录地区', 'like'],
                ['text:6', 'extend_url', '推广链接', 'like'],
                ['text:6', 'policy', '政策', 'like'],
                ['text:6', 'alloc_user', '所有者', 'like'],
                ['select', 'category', '分类', '', '', $catList],
                ['select', 'project_id', '项目', '', '', $list_project],
                ['select', 'status', '资源状态', '','',['1'=>'待分配','0'=>'已分配']],
                ['daterange', 'note_time', '记录时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],

            ];
        }
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')

            // ->setSearch(['tel' => '电话','mobile' => '手机','name'=>'客户'])// 设置搜索框
            ->hideCheckbox()
            ->setSearchArea($searchArr)
            ->setPageTitle($title)
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['name', '客户名称'],
                // ['tel', '客户电话'],
                ['mobile', '客户手机','callback',function($value, $data){
                    if (!$data['access_mobile']) {
                        return replaceTel($value);
                    }else{
                        return $value;
                    }
                }, '__data__'],
                ['categoryst', '分类'],
                ['project_id', '项目'],
                ['source', '来源'],
                ['alloc_user', '所有者'],
                ['alloc_status', '资源状态'],
                ['note_time', '记录时间'],
                ['note_area', '记录地区'],
                ['fee', '成本','callback',function($value, $data){
                    if (!$data['access_mobile']) {
                        return '无权限';
                    }else{
                        return $value;
                    }
                    // if (UID !=1) {
                    //     return '无权限';
                    // }
                }, '__data__'],
                
                ['policy', '政策'],
                ['create_time', '创建时间','datetime'],
                // ['status', '状态', 'switch'],
                ['extend_url', '推广链接'],
                ['email', '邮箱'],
                ['address', '地址'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('custom', $btn_access,true)
            ->addTopButton('custom', $catelsbt)
            ->addTopButton('custom', $btnexport)
            ->addRightButton('custom',$btn_call,['title'=>'呼叫','area' => ['200px', '200px']])
            ->addRightButton('custom',$btn_msg,['title'=>'短信','area' => ['800px', '500px']])
            // ->addRightButton('custom', $btn_call)
            // ->addRightButton('del')
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
        
    }

    
    /**
     * [msg 短信]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function msg($id)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $mobile = db('call_custom')->where(['id'=>$id])->value('mobile');
            if (!$mobile) {
                $this->error('手机号不对',null,'_close_pop');
            }
            $content['content'] = $data['title'];
            if (!$content['content']) {
                $this->error('短信内容不对',null,'_close_pop');
            }
            //生成日志
                // $s['content'] = $data['title'];
                // $s['custom_id'] = $id;
                // $s['user_id'] = UID;
                // $s['create_time'] = time();
                // db('call_msg_log')->insert($s);
            //发送
            $result = plugin_action('Sms/Sms/send', [$mobile, $content, 'SMS_186599008']);
            if($result['code']){
                $this->error('发送失败，错误代码：'. $result['code']. ' 错误信息：'. $result['msg'],null,'_close_pop');
            } else {
                //生成日志
                $s['content'] = $data['title'];
                $s['custom_id'] = $id;
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
     * [ringup 呼叫]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function ringup($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        $custom_id = $id;
        // $custom_id = db('call_alloc_log')->where(['id'=>$id])->value('custom_id');
        //手机号
        $params['telNum'] = get_mobile($custom_id)['mobile'];
        if (!$params['telNum']) {
            $this->error('手机号不对', null, '_close_pop');
        }
        // $params['telNum'] = '17321023222';
        //呼叫 telNum=135xxxxxxxx&extNum=801&transactionId=xxxxxxxxxxx
        // $params['extNum'] = session('user_auth_extension')?session('user_auth_extension')['exten']:'';
        // $params['extNum'] = '8801';
        $params['extNum'] = get_extension(UID)['extension'];

        if (!$params['extNum']) {
            $this->error('没有绑定分机号', null, '_close_pop');
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
        $s['role_id'] = db('admin_user')->where(['id'=>UID])->value('role_id');
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
     * [classNReportexport 导出]
     * @return [type] [description]
     */
    public function export()
    {
        $map = $this->getMaps();

        $roleid = db('admin_user')->where(['id'=>UID])->value('role');
        $access_moblie = db('admin_role')->where(['id'=>$roleid])->value('access_moblie');
        //查询数据
        // if (!$map) $this->error('缺少参数');

        $data =  CustomModel::where($map)->order('id desc')->paginate()->each(function($item,$key) use($access_moblie){
            $item->categorys = db('call_custom_cat')->where(['id'=>$item['category']])->value('title');
            $item->mobile = $access_moblie?$item['mobile']:replaceTel($item['mobile']);
            $item->fee = UID==1?$item['fee']:'无权限';

        });
        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['name','auto', '客户名称'],
            ['tel', 'auto','客户电话'],
            ['mobile', 'auto','客户手机'],
            ['categorys','auto', '分类'],
            ['source','auto', '来源'],
            ['email', 'auto','邮箱'],
            ['address', 'auto','地址'],
            ['note_time', 'auto','记录时间'],
            ['note_area','auto', '记录地区'],
            ['fee', 'auto','成本'],
            ['extend_url', 'auto','推广链接'],
            ['create_time', 'auto','创建时间','datetime'],
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['客户数据', $cellName, $data]);
    }
    /**
     * [catls 分类列表]
     * @return [type] [description]
     */
    public function catls()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = CatModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();


        $catedit_bt = [
            'title' => '编辑客户分类',
            'icon'  => 'fa fa-fw fa-navicon ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('catedit',['id'=>'__id__'])
        ];

        $catdel_bt = [
            'title' => '删除客户分类',
            'icon'  => 'fa fa-fw fa-navicon ',
            'class' => 'btn btn-default ajax-get confirm',
            'href' => url('catdelete',['id'=>'__id__']),
            'data-title' => '删除后无法恢复'
        ];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '标题'],
                ['desc', '说明'],
                ['right_button', '操作', 'btn']
            ])
            // ->addTopButton('add', ['href' => url('add')])
            ->addRightButton('custom',$catedit_bt,true)
            ->addRightButton('custom',$catdel_bt)
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * [catedit 编辑]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function catedit($id=null)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            if ($props = CatModel::update($data)) {
                $this->success('编辑成功', null,'_close_pop');
            } else {
                $this->error('编辑失败',null,'_close_pop');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'title', '分类标题'],
                ['text', 'desc', '说明'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1]
            ])
            ->setFormData(CatModel::get($id))
            ->fetch();
    }

    /**
     * [catdelete 删了]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function catdelete($id=null)
    {
        if ($id==6) {
            $this->error('已签约不能删除',null,'_close_pop');
        }
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data['id'] = $id;
            if ($props = CatModel::where($data)->delete()) {
                $this->success('删除成功', null);
            } else {
                $this->error('删除失败',null);
            }
        }
    }
    
    /**
     * [cat description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function cat($id = null)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if ($props = CatModel::create($data)) {
                $this->success('新增成功', null,'_close_pop');
            } else {
                $this->error('新增失败',null,'_close_pop');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '分类标题'],
                ['text', 'desc', '说明'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1]
            ])
            ->fetch();
    }
    /**
     * [import 导入日志]
     * @return [type] [description]
     */
    public function import()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);


        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = CustomEXLogModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

        $btn_access = [
            'title' => '导入',
            'icon'  => 'glyphicon glyphicon-cloud-upload',
            'href' => url('importCus')
        ];
        $btn_down = [
            'title' => '下载模板',
            'icon'  => 'fa fa-fw fa-cloud-download',
            'href' => url('downtmp')
        ];


        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTips('下载模板时，请填充对应的项目ID号','danger')
            ->setSearch(['title'=>'客户导入日志表'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '分批导入表名'],
                ['rate', '净得率']
            ])
            ->addTopButton('custom', $btn_access)
            ->addTopButton('custom', $btn_down)
            // ->addRightButton('del')
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }
    /**
     * [btn_down description]
     * @return [type] [description]
     */
    public function downtmp()
    {
        // 查询数据
        $data = db('call_custom')->limit(1)->select();
        foreach ($data as $key => &$value) {
            $value['project_id'] = 1;
        }
        // $data['project_id'] = 1;
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            // ['id', 'auto','ID'],
            ['project_id', 'auto','项目ID'],
            ['name', 'auto','客户名称'],
            ['tel', 'auto','客户电话'],
            ['mobile', 'auto','客户手机'],
            ['source', 'auto','来源'],
            ['email', 'auto','邮箱'],
            ['address', 'auto','地址'],
            ['note_time', 'auto','留言时间'],
            ['policy','auto', '政策'],
            ['note_area','auto', '记录地区'],
            ['fee', 'auto','成本'],
            ['extend_url', 'auto','推广链接'],
            // ['create_time', 'auto','创建时间'],
            ['record_time', 'auto','记录时间'],
            ['call_time', 'auto','最后一次通话时间'],
            ['batch_id', 'auto','批次']
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['客户模板表', $cellName, $data]);
        
    }


    /**
     * [importCus 导入]
     * @return [type] [description]
     */
    public function importCus()
    {
        // 提交数据
        if ($this->request->isPost()) {
            // 接收附件 ID
            $excel_file = $this->request->post('excel');
            // 获取附件 ID 完整路径
            $full_path = getcwd() . get_file_path($excel_file);
            // 只导入的字段列表
            $fields = [
                'project_id' => '项目ID',
                'name' => '客户名称',
                'tel' => '客户电话',
                'mobile' => '客户手机',
                'source' => '来源',
                'email' => '邮箱',
                'address' => '地址',
                'note_time' => '留言时间',
                'policy' => '政策',
                'note_area' => '记录地区',
                'fee' => '成本',
                'extend_url' => '推广链接',
                'record_time' => '记录时间',
                'call_time' => '最后一次通话时间',
                'batch_id' => '批次'
            ];
            // 调用插件('插件',[路径,导入表名,字段限制,类型,条件,重复数据检测字段])
            $import = plugin_action('Excel/Excel/import', [$full_path, 'call_custom', $fields, $type = 0, $where = null, $main_field = 'mobile', $second_field = 'project_id',['name','mobile','note_time','note_area','fee','source','policy']]);

            
            // 失败或无数据导入 计算净得率
            if ($import['error']){
                if ($import['error']==10) {
                    $s['rate'] = $import['rate'];
                    // $s['title'] = $import['tabNm'];
                    $s['batch_id'] = $import['batch_id'];
                    $s['create_time'] = time();
                    $s['title'] = get_file_name($excel_file);
                    CustomEXLogModel::create($s);
                }
                $this->error($import['message'], url('import'));
            }

            $s['rate'] = $import['rate'];
            $s['title'] = get_file_name($excel_file);
            $s['create_time'] = time();
            $s['batch_id'] = $import['batch_id'];
            CustomEXLogModel::create($s);

            //更新客户导入时间
            CustomModel::where(['batch_id'=>$import['batch_id']])->update(['create_time'=>time()]);
            
            // 导入成功
            $this->success($import['message'], url('import'));
        }
        // 创建演示用表单
        return ZBuilder::make('form')
            ->setPageTitle('导入Excel')
            ->addFormItems([ // 添加上传 Excel
                ['file', 'excel', '上传文件'],
            ])
            ->fetch();
    }

    /**
     * [backst 回收配置]
     * @return [type] [description]
     */
    public function backst()
    {
        // cookie('__forward__', $_SERVER['REQUEST_URI']);


        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // Cache::set('recover_data_hour', $data['recover_data_hour']);
            // config('recover_data_hour',$data['recover_data_hour']);
            $map['name'] = 'recover_data_hour';
            $sdata['value'] = $data['recover_data_hour'];
            ConfigModel::where($map)->update($sdata);
            // plugin_config('other.recover_data_hour',$data['recover_data_hour']);
            $this->success('操作成功');
        }else{
            // 获取数据
            $info = [
                'recover_data_hour'=>Cache::get('recover_data_hour')?Cache::get('recover_data_hour'):'',
            ];
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('回收配置') // 设置页面标题
                ->addFormItems([ // 批量添加表单项
                    ['text', 'recover_data_hour', '回收设置', '超过X小时自动回收数据',config('recover_data_hour')],
                ])
                ->setFormData() // 设置表单数据
                ->fetch();
        }

        

    }
    /**
     * [gtback 回收列表]
     * @return [type] [description]
     */
    public function gtback($group='tab1')
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        $list_tab = [
            'tab1' => ['title' => '回收列表', 'url' => url('gtback', ['group' => 'tab1'])],
            'tab2' => ['title' => '公海列表', 'url' => url('gtback', ['group' => 'tab2'])],
        ];

        $sources = db('call_custom')->column('source');
        $list_source = [];
        foreach ($sources as $key => $value) {
            $list_source[$value] = $value;
        }

        $project = db('call_project_list')->where(['status'=>1])->column('id,col1');

        $btn_alloc = [
            'title' => '分配',
            'icon'  => 'fa fa-fw fa-stack-overflow',
            'class' => 'btn btn-default ajax-post',
            'href' => url('alloc')
        ];

        if ($group=='tab1') {
            $map['status'] = 1;

            //custom_id user_id 
            // if (isset($map['custom_id'])) {
            //     $mm['name'] = $map['custom_id'];
            //     $custom_ids = db('call_custom')->where($mm)->value('id');
            //     $map['custom_id'] = array('in',$custom_ids);
            // }

            // if (isset($map['user_id'])) {
            //     $mm['nickname'] = $map['user_id'];
            //     $custom_ids = db('admin_user')->where($mm)->value('id');
            //     $map['user_id'] = array('in',$custom_ids);
            // }
            
            // 数据列表
            $data_list = RecoverdtModel::where($map)->order('id desc')->paginate();

            // 分页数据
            $page = $data_list->render();

     
            // 使用ZBuilder快速创建数据表格 客户名称 电话 所有者 呼叫次数 分配时间 分配次数 回收时间
            return ZBuilder::make('table')
                // ->hideCheckbox()
                ->setPageTitle('回收列表') // 设置页面标题
                ->setTabNav($list_tab,  $group)
                ->setSearchArea([
                    ['text:6', 'custom', '客户名称','like'],
                    ['text:6', 'user', '所有者','like'],

                    ['text:6', 'mobile', '电话','like'],
                    ['text:6', 'call_count', '呼叫次数','like'],
                    ['text:6', 'alloc_count', '分配次数','like'],
                   
                    ['daterange', 'alloc_time', '分配时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],

                    ['daterange', 'create_time', '回收时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                    ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                    ['select', 'project_id', '项目', '', '', $project],
                    ['select', 'source', '平台来源', '', '', $list_source],


                ])
                ->addColumns([ // 批量添加数据列
                    ['id', 'ID'],
                    ['project', '项目'],
                    ['source', '客户来源'],
                    ['custom', '客户名称'],
                    ['note_time', '留言时间'],
                    ['mobile', '电话'],
                    ['alloc_time', '分配时间','datetime'],
                    ['call_count', '呼叫次数'],
                    ['alloc_count', '分配次数'],

                    // ['first_standard', '第1天达标情况'],
                    // ['second_standard', '第2天达标情况'],
                    // ['third_standard', '第3天达标情况'],
                    // ['fourth_standard', '第4天达标情况'],
                    // ['fifth_standard', '第5天达标情况'],

                    ['create_time', '回收时间','datetime'],
                    ['user', '所有者'],
                    // ['status', '状态', 'switch'],
                    // ['right_button', '操作', 'btn']
                ])

                ->addTopButton('custom', $btn_alloc,['title' => '分配员工'])
                // ->addRightButton('delete')
                ->setRowList($data_list)// 设置表格数据
                ->raw('project') // 使用原值
                ->fetch(); // 渲染模板
        }
        

        if ($group=='tab2') {
            $map['status'] = 9;
            // 数据列表
            $data_list = RecoverdtModel::where($map)->order('id desc')->paginate();

            // 分页数据
            $page = $data_list->render();

            //项目政策 呼叫次数 分配次数 所有者 客户名称 电话  分配时间  
            // 使用ZBuilder快速创建数据表格
            return ZBuilder::make('table')
                // ->hideCheckbox()
                ->setPageTitle('公海列表') // 设置页面标题
                ->setTabNav($list_tab,  $group)
                ->setSearchArea([
                    ['text:6', 'custom', '客户名称','like'],
                    ['text:6', 'user', '所有者','like'],

                    ['text:6', 'mobile', '电话','like'],
                    ['text:6', 'call_count', '呼叫次数','like'],
                    ['text:6', 'alloc_count', '分配次数','like'],

                    ['daterange', 'create_time', '加入时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                    ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                    ['select', 'project_id', '项目', '', '', $project],
                    ['select', 'source', '平台来源', '', '', $list_source],

                ])
                ->addColumns([ // 批量添加数据列
                    ['id', 'ID'],
                    ['project', '项目'],
                    ['source', '客户来源'],
                    ['custom', '客户名称'],
                    ['note_time', '留言时间'],
                    ['mobile', '电话'],
                    ['alloc_time', '分配时间','datetime'],
                    ['call_count', '呼叫次数'],
                    ['alloc_count', '分配次数'],

                    ['first_standard', '第1天达标情况'],
                    ['second_standard', '第2天达标情况'],
                    ['third_standard', '第3天达标情况'],
                    ['fourth_standard', '第4天达标情况'],
                    ['fifth_standard', '第5天达标情况'],

                    ['create_time', '加入公海时间','datetime'],
                    ['user', '所有者'],
                ])
                ->addTopButton('custom', $btn_alloc,['title' => '分配员工'])
                // ->addTopButton('add', ['href' => url('add')])
                // ->addRightButton('delete')
                ->setRowList($data_list)// 设置表格数据
                ->raw('project') // 使用原值
                ->fetch(); // 渲染模板
        }

    }

    /**
     * [alloc 分配]
     * @return [type] [description]
     */
    public function alloc()
    {
        // $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if (!$data['ids']) {
                $this->error('数据缺失',  null,'_close_pop');
            }
            //user_id ids 
            $s['op_id'] = UID;
            $s['alloc_count'] = 1;
            $s['create_time'] = time();
            $s['way'] = 2;
            if ($props = AllocModel::create($s)) {
            // if (1==1) {
                $insert_id = $props->id;
                // $insert_id = 999;
                //log
                $sl = [];
                $cus = [];
                $ids = explode(',', $data['ids']);
                foreach ($ids as $key => $value) {
                    //回收数据id
                    db('call_recover_data')->where(['id'=>$value])->update(['status'=>0]);
                    $custom_id = db('call_recover_data')->where(['id'=>$value])->value('custom_id');
                    $sl[$key]['batch_id'] = db('call_custom')->where(['id'=>$custom_id])->value('batch_id');
                    db('call_custom')->where(['id'=>$custom_id])->update(['status'=>2]);
                    array_push($cus, $custom_id);
                    $sl[$key]['alloc_count'] = 1;
                    $sl[$key]['alloc_id'] = $insert_id;
                    $sl[$key]['custom_id'] = $custom_id;
                    $sl[$key]['create_time'] = time();
                    $sl[$key]['alloc_count'] = 1;
                    $sl[$key]['user_id'] = $data['employ_id'];

                    $batch_id = $sl[$key]['batch_id'];
                }
                db('call_alloc')->where(['id'=>$insert_id])->update(['batch_id'=>$batch_id]);
                // $mmm['id'] = array('in',$cus);
                // print_r($mmm);exit;
                // db('call_custom')->where($mmm)->update(['status'=>1]);
                // print_r($sl);exit;
                $RoleModel = new AlloclgModel();
                $RoleModel->saveAll($sl);


                $this->success('分配成功', null, '_parent_reload');
            } else {
                $this->error('分配失败',  null,'_close_pop');
            }
        }  

        $m['id'] = array('gt',1);
        $ls = db('admin_user')->where($m)->column('id,nickname');
        $js = <<<EOF
            <script type="text/javascript">
                $(function(){
                    var abc = parent.document.getElementsByName('ids[]');
                    var ids = [];
                    for(j=0;j<abc.length;j++)
                    {
                        if(abc[j].checked==true){ids.push(abc[j].value);}
                         
                    }

                    $('#ids').val(ids)
                });
            </script>
EOF;
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['select', 'employ_id', '员工','',$ls],
                ['hidden', 'ids'],
            ])
            ->setExtraJs($js)
            ->fetch();
    }

    /**
     * 新增
     * @return mixed
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['start_time'] =strtotime($data['start_time']);
            $data['end_time'] =  strtotime($data['end_time']);
            if ($props = AuthModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'custom', '客户名'],
                ['text', 'domain', '域名'],
                ['text', 'ip', '服务器ID'],
                ['radio', 'online', '授权方式', '', ['线下', '线上'], 1],
                ['datetime', 'start_time', '开始时间'],
                ['datetime', 'end_time', '结束时间'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],
            ])
            ->fetch();
    }
    /**
     * 编辑
     * @param null $id 菜单id
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['start_time'] =strtotime($data['start_time']);
            $data['end_time'] =  strtotime($data['end_time']);
            if (AuthModel::update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'custom', '客户名'],
                ['text', 'domain', '域名'],
                ['text', 'ip', '服务器ID'],
                ['radio', 'online', '授权方式', '', ['线下', '线上'], 1],
                ['datetime', 'start_time', '开始时间'],
                ['datetime', 'end_time', '结束时间'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],

            ])
            ->setFormData(AuthModel::get($id))
            ->fetch();
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

        //分类设置
        // $data = $this->request->post();
        // if ($data['name']=='category') {
        //     if ($data['value']==6) {//签约
        //         $toparty = [];
        //         $totag = [];
        //         $msgtype = 'text';
        //         $touser = db('admin_user')->where(['id'=>UID])->find();
        //         $info = db('call_custom')->where(['id'=>$data['pk']])->find();
        //         $content = $info['name'].'已签约,操作员工'.$touser['nickname'].date('Y-m-d H:i',time());//张三|客户名称]已签约，操作员工[李四|操作人]，[2020-2-6|修改时间
        //         $user = [];
        //         array_push($user, $touser['wechat_name']);
        //         $result = plugin_action('Wechat/Wechat/send',[$user , $toparty , $totag , $msgtype  , $content]);
        //         // $isTrue = push_24_report_msg($touser['wechat_name'] , $toparty , $totag , $msgtype  , $content);         
        //         if ($result['code']) {
        //             //生成日志
        //             $s['create_time'] = time();
        //             $s['category'] = $data['value'];
        //             $s['custom_id'] = $data['pk'];
        //             $s['export_time'] = $info['create_time'];
        //             $s['employ_id'] = UID;
        //             db('call_report_custom_cat')->insert($s);
        //         }
        //     }
        //     // $info = db('call_custom')->where(['id'=>$data['pk']])->find();
        //     //ts
        //     // $s['create_time'] = time();
        //     // $s['category'] = $data['value'];
        //     // $s['custom_id'] = $data['pk'];
        //     // $s['export_time'] = $info['create_time'];
        //     // $s['employ_id'] = UID;
        //     // db('call_report_custom_cat')->insert($s);
        // }
        // db('call_custom')->where(['id'=>$data['pk']])->update(['update_time'=>time()]);
        
        return parent::quickEdit(['call_auth_edit', 'call', 0, UID, $id]);
    }
   
    
}