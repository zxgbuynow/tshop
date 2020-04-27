<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Alloc as AllocModel;
use app\call\model\Alloclg as AlloclgModel;
use app\call\model\Custom as CustomModel;
use app\user\model\User as UserModel;
use app\user\model\Role as RoleModel;//CustomEXLog
use app\call\model\CustomEXLog as CustomEXLogModel;//CustomEXLog
/**
 * 首页后台控制器
 */
class Alloc extends Admin
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

        // 数据列表
        $data_list = AllocModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();
        $btn_access = [
            'title' => '分配日志',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('alloclg', ['id' => '__id__'])
        ];

        

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setSearchArea([
                ['text:12', 'id', '任务ID', 'eq'],
                ['text:12', 'name', '任务名称', 'like'],

            ])
            // ->setSearch(['domain' => '域名','custom'=>'客户'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['name', '任务名称'],
                ['oper', '操作员'],
                // ['call_count', '呼叫次数'],
                // ['alloc_count', '分配次数'],
                ['create_time', '创建时间','datetime'],
                ['way', '分配方式',['','平均分配','选配']],
                // ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('add')])

            ->addRightButton('custom',$btn_access)
            // ->addRightButton('custom',$btn_msg,['title'=>'短信','area' => ['800px', '800px']])
            ->setRowList($data_list)// 设置表格数据
            ->raw('oper') // 使用原值
            ->fetch(); // 渲染模板
        
    }

    

    /**
     * [alloclg 分配日志]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function alloclg($id =null)
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        if ($id === null) $this->error('缺少参数');


        // 获取查询条件
        $map = $this->getMap();

        $map['alloc_id'] = $id;
        // 数据列表
        $data_list = AlloclgModel::where($map)->order('id desc')->paginate()->each(function($item, $key) use ($map){
            $item->call_count = db('call_log')->where(['custom_id'=>$item['custom_id']])->count();
            $item->alloc_count = db('call_alloc_log')->where(['custom_id'=>$item['custom_id']])->count();
        });

        // 分页数据
        $page = $data_list->render();
 
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->addTopButton('back', [
                'title' => '返回列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('index')
            ])
            // ->setSearch(['domain' => '域名','custom'=>'客户'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['user', '员工'],
                ['custom', '客户'],
                ['call_count', '呼叫次数'],
                ['alloc_count', '分配次数'],
                ['create_time', '创建时间','datetime'],
                // ['right_button', '操作', 'btn']
            ])
            // ->addTopButton('add', ['href' => url('add')])
            // ->addRightButton('delete')
            ->setRowList($data_list)// 设置表格数据
            ->raw('user,custom') // 使用原值
            ->fetch(); // 渲染模板
    }

    /**
     * [add 新增]
     */
//     public function add1()
//     {
//         // 保存数据
//         if ($this->request->isPost()) {
//             // 表单数据
//             $data = $this->request->post();
            
//         }
//         $custom =  CustomModel::where(['status'=>1])->column('id,name');
//         $map['id'] = array('>',1);
//         $user =  UserModel::where($map)->column('id,username');

//         $tips = db('call_custom')->where(['status'=>1])->count();
        
//         $checkSchedule_url = url('checkSchedule');
//         $getScheduleFuture_url = url('getScheduleFuture');
//         $js = <<<EOF
//             <script type="text/javascript">
//                  function checkSchedule() {
//                     var schedule = $("#schedule").val();
//                     $.post("{$checkSchedule_url}", { "schedule": schedule },
//                     function(data){
//                         if (data.status == false){
//                             Dolphin.notify('Cron 表达式错误', 'danger', 'glyphicon glyphicon-warning-sign');
//                             return false;
//                         }
//                         var days = $("#pickdays").val();
//                         var begin_time = $("#begin_time").val();
//                         $.post("{$getScheduleFuture_url}", { "schedule": schedule, "begin_time": begin_time, "days": days },
//                             function(data){
//                                 if (data.status == true){
//                                     var html = '';
//                                     for(var i=0; i<data.time.length; i++){
//                                         html += '<li class="list-group-item">'+data.time[i]+'<span class="badge">'+(i+1)+'</span></li>';
//                                         //console.log(data.time[i]);
//                                     }
//                                     $('#scheduleresult').html(html);
//                                 }
//                             }, "json");
//                     }, "json");
//                 }
            
//                 $(function(){
//                     checkSchedule();    // 页面加载后就执行一次
            
//                     // 检查 Cron 表达式是否正确，如果正确，则获取预计执行时间
//                     $("#schedule,#pickdays,#begin_time").blur(function(){
//                         checkSchedule();
//                     });
//                 });
//             </script>
// EOF;
//         // 显示添加页面
//         return ZBuilder::make('form')
//             ->addFormItems([
//                 ['radio', 'way', '分配方式' ,'', ['平均分配', '选配'], 0],
//                 ['text', 'task_id', '输入任务id'],
//                 ['text', 'task', '输入任务名称'],
//                 ['text', 'export_id', '输入导入id'],
//                 ['text', 'export_tab', '输入导入表名'],
//                 ['text', 'export_time', '输入导入时间'],
//                 ['static', 'custom_ids', '待客户数量','<code>当前任务总数'.$tips.'；务必不要大于该值</code>'],
//                 // ['number', 'custom_ids', '输入客户数量','<code>当前任务总数'.$tips.'；务必不要大于该值</code>'],
//                 // ['select', 'user_ids', '选择员工', '<code>可多选</code>', $user,'','multiple'],
//                 // ['select', 'user_id', '选择员工', '', $user],
//                 // ['select', 'custom_id', '选择客户数据', '<code>可多选</code>', $custom,'','multiple'],
//                 ['radio', 'status', '立即启用', '', ['否', '是'], 1],
//             ])

//             ->setTrigger('way', 1, 'custom_id,user_id')
//             ->setTrigger('way', 0, 'custom_ids,export_time,export_tab,export_id,task,task_id')
//             ->fetch('add');
//     }
    /**
     * 新增
     * @return mixed
     */
    public function add()
    {
        //超级管理员 只分配组长 
        if(UID == 1){
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $sdata['create_time'] =  time();
            //UID
            $sdata['op_id'] = UID;
            $sdata['way'] = $data['way']==0?1:2;
            // $sdata['call_count'] = $data['call_count'];
            $sdata['name'] = $data['name'];
            $sdata['batch_id'] = $data['batch_id'];
            //必填字段
            if (!$sdata['name']) {
                $this->error('任务名称必填');
            }
            if (!$sdata['batch_id']) {
                $this->error('导入批次必填');
            }
            if ($data['custom_ids']>$data['calloc_num']) {
                $this->error('不能大于可分配总数');
            }

            if ($data['calloc_num']==0) {
                $this->error('数量必须大于0');
            }
            if ($props = AllocModel::create($sdata)) {
            // if (1==1) {
                $insert_id = $props->id;
                // $insert_id = 99;
                //分配处理
                if ($sdata['way']==1) {
                    
                    if (!isset($data['user_id'])) {
                        // $data['user_id'] = implode(',', db('admin_user')->where(['id'=>$data['role_id']])->column('id'));
                        $data['user_id'] = db('admin_user')->where(['role'=>$data['role_id']])->column('id');

                    }
                    $userCts = count($data['user_id']);
                    $custCts = $data['custom_ids'];
                    //取其中数量
                    $m['status'] = 1;
                    $m['batch_id'] = $data['batch_id'];
                    $customs = db('call_custom')->where($m)->order('id ASC')->limit($custCts)->column('id');

                    $hts = [];
                    $average = ceil($custCts/$userCts);

                    $custCtarr = array_chunk($customs, $average);

                    $r = [];
                    foreach ($custCtarr as $key => $value) {
                        $cc = count($value);
                        if (!isset($data['user_id'][$key])) {
                                continue;
                            }
                        for ($i=0; $i < $cc; $i++) { 
                            $rs['custom_id'] = $value[$i];
                            $hts[] = $value[$i];
                            $rs['user_id'] = $data['user_id'][$key];
                            $rs['alloc_id'] = $insert_id;
                            $rs['create_time'] = time();
                            $rs['batch_id'] = $data['batch_id'];
                            array_push($r,$rs);
                        }
                    }


                }//平均
                if ($sdata['way']==2) {
                    if (!isset($data['user_id'])) {
                        // $data['user_id'] = implode(',', db('admin_user')->where(['id'=>$data['role_id']])->column('id'));
                        // $data['user_id'] = db('admin_user')->where(['id'=>$data['role_id']])->column('id');
                        $data['user_id'] = db('admin_user')->where(['role'=>$data['role_id']])->column('id');

                    }

                    $userCts = count($data['user_id']);
                    $custCts = count($data['custom_id']);
                    $average = ceil($custCts/$userCts);
                    $custCtarr = array_chunk($data['custom_id'], $average);


                    $r = [];
                    foreach ($custCtarr as $key => $value) {
                        $cc = count($value);
                        if (!isset($data['user_id'][$key])) {
                                continue;
                            }
                        for ($i=0; $i < $cc; $i++) { 
                            $rs['custom_id'] = $value[$i];
                            $rs['user_id'] = $data['user_id'][$key];
                            $rs['alloc_id'] = $insert_id;
                            $rs['create_time'] = time();
                            $rs['batch_id'] = $data['batch_id'];
                            array_push($r,$rs);
                        }
                    }

                }//选择
                $Alloclg = new AlloclgModel;
                $Alloclg->saveAll($r);

                if ($sdata['way']==1) {
                    if ($hts) {
                        $mp['id'] = array('in',implode(',', $hts) );
                        CustomModel::where($mp)->update(['status'=>2]);
                    }
                    
                }
                if ($sdata['way']==2) {
                     $mp['id'] = array('in',$data['custom_id']);
                    CustomModel::where($mp)->update(['status'=>2]);
                }
                // if ($sdata['way']==1) {
                //     //平均分处理
                //     $userCts = count($data['user_ids']);
                //     $custCts = count($data['custom_ids']);
                //     $average = ceil($custCts/$userCts);
                //     $custCtarr = array_chunk($data['custom_ids'], $average);
                //     // $s = [];
                //     $r = [];
                //     foreach ($custCtarr as $key => $value) {
                //         // $s[$key]['user_id'] = $data['user_ids'][$key];
                //         // $s[$key]['custom_id'] = $value;
                //         $cc = count($value);
                //         for ($i=0; $i < $cc; $i++) { 
                //             $rs['custom_id'] = $value[$i];
                //             $rs['user_id'] = $data['user_ids'][$key];
                //             $rs['alloc_id'] = $insert_id;
                //             // $rs['call_count'] = $data['call_count'];
                //             $rs['create_time'] = time();
                //             array_push($r,$rs);
                //         }
                //     }
                    
                    
                // }
                // if ($sdata['way']==2) {
                //     $custCts = count($data['custom_id']);
                //     $r = [];
                //     for ($i=0; $i < $custCts; $i++) { 
                //         $r[$i]['custom_id'] = $data['custom_id'][$i];
                //         $r[$i]['user_id'] = $data['user_id'];
                //         $r[$i]['alloc_id'] = $insert_id;
                //         // $r[$i]['call_count'] = $data['call_count'];
                //         $r[$i]['create_time'] = time();
                //     } 
                // }
                // $Alloclg = new AlloclgModel;
                // $Alloclg->saveAll($r);

                // if ($sdata['way']==1) {
                //     $mp['id'] = array('in',$data['custom_ids']);
                //     CustomModel::where($mp)->update(['status'=>2]);
                // }
                // if ($sdata['way']==2) {
                //      $mp['id'] = array('in',$data['custom_id']);
                //     CustomModel::where($mp)->update(['status'=>2]);
                // }
               
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        $custom =  CustomModel::where(['status'=>1])->column('id,name');

        $map['id'] = array('>',1);
        $map['is_maner'] = 1;//过滤非管理员
        $user =  UserModel::where($map)->column('id,username'); 

        $map1['status'] = 1;
        $map1['id'] = array('>',1);
        $role = RoleModel::where($map1)->column('id,name'); 

        $tips = db('call_custom')->where(['status'=>1])->count();
        
        $batchs = CustomEXLogModel::column('batch_id,title'); 

        $get_batch =  url('get_batch');
        $get_custom = url('get_custom');
         // <select class="js-select2 form-control" name="custom_id" id="custom_id">
                                        //     <option value="">请选择：</option>
                                        //     {notempty name="list"}
                                        //         {volist name="list" id="option"}
                                        //         <option value="{$option['id']}">{$option['value']}</option>
                                        //         {/volist}
                                        //     {/notempty}
                                        // </select>
        $js = <<<EOF
            <script type="text/javascript">
               
                $(function(){

                    $('.select-linkage').change(function (r) {
                        if($(this).data('param')=='batch_id'){
                            console.log($(this).val())
                            if(!$(this).val()){return false}

                            $.post("{$get_batch}", { "batch_id": $(this).val() },
                                function(data){
                                    console.log(data);
                                    if (data.code == 1){
                                        $('#form_group_cusids').find('div.form-control-static').html(data.value);
                                        $('#calloc_num').val(data.value)

                                    }else{
                                        $('#form_group_cusids').find('form-control-static').html('');
                                        $('#calloc_num').val(0)
                                        Dolphin.notify('该批次下没有可分配的客户', 'danger');
                                    }
                                }, "json");
                        }
                    })
                });
            </script>
EOF;
        // 显示添加页面
        return ZBuilder::make('form')
            ->addLinkage('role_id', '选择组', '', $role, '', url('get_user'), 'user_id')
            ->addSelect('user_id', '选择员工','',$user,'','multiple')
            ->addLinkage('batch_id', '选择导入任务', '', $batchs, '', url('get_batch'), 'custom_id[]')
            ->addSelect('custom_id', '选择客户数据','<code>可多选</code>','','','multiple')
            ->addFormItems([
                // ['text', 'call_count', '呼叫次数'],
                ['text', 'name', '任务名称'],
                ['radio', 'way', '分配方式' ,'', ['平均分配', '选配'], 0],
                ['hidden', 'calloc_num', '数量'],
                // ['number', 'alloc_count', '设置分配数量'],
                // ['select', 'custom_ids', '选择客户数据', '<code>可多选</code>', $custom,'','multiple'],
                ['static', 'cusids', '当前任务总数','<code>如果任务数为0即无可分配客户</code>',$tips],
                ['number', 'custom_ids', '输入客户数量'],
                // ['select', 'user_ids', '选择员工', '<code>可多选</code>', $user,'','multiple'],

                // ['select', 'user_id', '选择员工', '', $user],
                // ['select', 'role_id', '选择组', '<code>可多选</code>', $custom,'','multiple'],
                // ['select', 'custom_id', '选择客户数据', '<code>可多选</code>', $custom,'','multiple'],

                ['radio', 'status', '立即启用', '', ['否', '是'], 1],

                // ['selectTable', 'test', '测试客户', '', $columns, [], url('Custom/index')],
            ])
            ->setPageTips('超级管理员分配数据给主管', 'danger')
            ->setTrigger('way', 1, 'custom_id')
            ->setTrigger('way', 0, 'custom_ids')
            ->setExtraJs($js)
            ->fetch();

        }else{

            //是否是主管
            if (!db('admin_user')->where(['id'=>UID,'is_maner'=>1 ])->find()) {
                $this->error('无权限');
            }

            $role = db('admin_user')->where(['id'=>UID])->value('role');

            //过滤 可分配数据
            $customs = db('call_alloc_log')->where(['status'=>1,'user_id'=>UID])->column('custom_id');
            // $mmm['status'] = 2;
            $mmm['id'] = array('in',$customs);
            if (!$customs) {
                $mmm['id'] = '';
            }
            
            $custom =  CustomModel::where($mmm)->column('id,name');

            $map['role'] = $role;//取部门员工
            $map['is_maner'] = 0;
            $user =  UserModel::where($map)->column('id,username'); 


            $tips = db('call_alloc_log')->where(['status'=>1,'user_id'=>UID])->count();

            $batch_id = db('call_alloc_log')->where(['status'=>1,'user_id'=>UID])->value('batch_id');//只取一个批次；
            if ($this->request->isPost()) {
                // 表单数据
                $data = $this->request->post();
                $sdata['create_time'] =  time();
                //UID
                $sdata['op_id'] = UID;
                $sdata['way'] = $data['way']==0?1:2;
                // $sdata['call_count'] = $data['call_count'];
                $sdata['name'] = $data['name'];
                $sdata['batch_id'] = $data['batch_id'];
                //必填字段
                if (!$sdata['name']) {
                    $this->error('任务名称必填');
                }
                if (!$sdata['batch_id']) {
                    $this->error('导入批次必填');
                }
                if ($data['custom_ids']>$tips) {
                    $this->error('不能大于可分配总数');
                }

                if ($props = AllocModel::create($sdata)) {
                    $insert_id = $props->id;
                    
                    if ($sdata['way']==1) {
                        //平均分处理
                        // if (!$data['user_ids']) {
                        //     $data['user_ids'] = implode(',', db('admin_user')->where(['id'=>$data['role_id']])->column('id'));

                        // }
                        $userCts = count($data['user_ids']);
                        $custCts = count($data['custom_ids']);

                        //取其中数量

                        $customs = db('call_alloc_log')->where(['status'=>1,'user_id'=>UID])->column('custom_id');
                        $average = ceil($custCts/$userCts);

                        $hcus = [];
                        $custCtarr = array_chunk($customs, $average);
                        $r = [];
                        foreach ($custCtarr as $key => $value) {
                            $cc = count($value);
                            if (!isset($data['user_ids'][$key])) {
                                continue;
                            }
                            for ($i=0; $i < $cc; $i++) { 
                                $rs['custom_id'] = $value[$i];
                                $hcus[] = $value[$i];
                                $rs['user_id'] = $data['user_ids'][$key];
                                $rs['alloc_id'] = $insert_id;
                                $rs['create_time'] = time();
                                $rs['batch_id'] = db('call_alloc_log')->where(['status'=>1,'user_id'=>UID,'custom_id'=>$value[$i]])->value('batch_id');
                                array_push($r,$rs);
                            }
                        }
                        
                        
                    }

                    if ($sdata['way']==2) {
                        $custCts = count($data['custom_id']);
                        $r = [];
                        for ($i=0; $i < $custCts; $i++) { 
                            $r[$i]['custom_id'] = $data['custom_id'][$i];
                            $r[$i]['user_id'] = $data['user_id'];
                            $r[$i]['alloc_id'] = $insert_id;
                            $r['batch_id'] = db('call_alloc_log')->where(['status'=>1,'user_id'=>UID,'custom_id'=>$data['custom_id'][$i]])->value('batch_id');
                            // $r[$i]['call_count'] = $data['call_count'];
                            $r[$i]['create_time'] = time();
                        } 
                    }
                    $Alloclg = new AlloclgModel;
                    $Alloclg->saveAll($r);

                    if ($sdata['way']==1) {
                        if ($hcus) {
                            $mp['id'] = array('in',implode(',', $hcus) );
                            CustomModel::where($mp)->update(['status'=>2]);
                            $mm['status'] = 1;
                            $mm['custom_id'] = array('in',implode(',', $hcus) );
                            $mm['user_id'] = UID;
                            AlloclgModel::where($mm)->update(['status'=>0]);
                        }
                        
                    }
                    if ($sdata['way']==2) {
                        $mp['id'] = array('in',$data['custom_id']);
                        CustomModel::where($mp)->update(['status'=>2]);
                        $mm['status']=1;
                        $mm['custom_id']=array('in',$data['custom_id']);
                        $mm['user_id']=UID;
                        AlloclgModel::where($mm)->update(['status'=>0]);
                    }
                   
                    $this->success('新增成功', url('index'));
                } else {
                    $this->error('新增失败');
                }
            }
            // 显示添加页面
            return ZBuilder::make('form')
                ->addFormItems([
                    ['hidden', 'batch_id', $batch_id],
                    ['text', 'name', '任务名称'],
                    ['radio', 'way', '分配方式' ,'', ['平均分配', '选配'], 0],
                    ['static', 'cusids', '当前任务总数','<code>如果任务数为0即无可分配客户</code>',$tips],
                    ['number', 'custom_ids', '输入客户数量'],
                    ['select', 'user_ids', '选择员工', '<code>可多选</code>', $user,'','multiple'],
                    ['select', 'custom_id', '选择客户数据', '<code>可多选</code>', $custom,'','multiple'],
                    ['radio', 'status', '立即启用', '', ['否', '是'], 1],
                    ['select', 'user_id', '选择员工', '', $user],
                ])
                ->setPageTips('主管需按批次分配自己的数据', 'danger')
                ->setTrigger('way', 1, 'custom_id,user_id')
                ->setTrigger('way', 0, 'custom_ids,user_ids')
                // ->setExtraJs($js)
                ->fetch();
        }



    }

    /**
     * [get_custom description]
     * @param  string $batch_id [description]
     * @return [type]           [description]
     */
    // public function get_custom($batch_id='')
    // {
    //     $arr['code'] = '1'; //判断状态
    //     $arr['msg'] = '请求成功'; //回传信息

    //     $list = db('call_custom')->where(['batch_id'=>$batch_id,'status'=>1])->select();

    //     $arr['list'] = [];
    //     foreach ($list as $key => $value) {
    //       $arr['list'][$key]['key'] = $value['id']; 
    //       $arr['list'][$key]['value'] = $value['name']; 
    //     }
        
    //     return json($arr);
    // }
    /**
     * [get_batch description]
     * @param  string $batch_id [description]
     * @return [type]           [description]
     */
    public function get_batch($batch_id='')
    {
        $arr['code'] = '1'; //判断状态
        $arr['msg'] = '请求成功'; //回传信息

        $map['status'] = 1;
        $map['batch_id'] = $batch_id;
        $count = db('call_custom')->where($map)->count();
        $arr['value'] = $count;

        $list = db('call_custom')->where(['batch_id'=>$batch_id,'status'=>1])->select();
        $arr['list'] = [];
        foreach ($list as $key => $value) {
          $arr['list'][$key]['key'] = $value['id']; 
          $arr['list'][$key]['value'] = $value['name']; 
        }
        
        return json($arr);
    }
    /**
     * 获取组下员工
     * @param  string $role_id [description]
     * @return [type]          [description]
     */
    public function get_user($role_id = '')
    {
        $arr['code'] = '1'; //判断状态
        $arr['msg'] = '请求成功'; //回传信息

        $city = db('admin_user')->where(['role'=>$role_id])->field('id,username,nickname')->select();
        $arr['list'] = [];
        foreach ($city as $key => $value) {
          $arr['list'][$key]['key'] = $value['id']; 
          $arr['list'][$key]['value'] = $value['nickname']; 
        }
        
        return json($arr);
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
        
        $custom =  CustomModel::where(['status'=>1])->column('id,name');
        $map['id'] = array('>',1);
        $user =  UserModel::where($map)->column('id,username');

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'call_count', '呼叫次数'],
                ['radio', 'way', '分配方式' ,'', ['平均分配', '选配'], 0],
                // ['number', 'alloc_count', '设置分配数量'],
                ['select', 'custom_ids', '选择客户数据', '<code>可多选</code>', $custom,'','multiple'],
                ['select', 'user_ids', '选择员工', '<code>可多选</code>', $user,'','multiple'],

                ['select', 'user_id', '选择员工', '', $custom],
                ['select', 'custom_id', '选择客户数据', '<code>可多选</code>', $custom,'','multiple'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],

            ])
            ->setTrigger('way', 1, 'custom_id,user_id')
            ->setTrigger('way', 0, 'custom_ids,user_ids')
            ->setFormData(AllocModel::get($id))
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
        return parent::quickEdit(['call_auth_edit', 'call', 0, UID, $id]);
    }
   
    
}