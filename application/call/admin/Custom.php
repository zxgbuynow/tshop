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


        // 数据列表
        $data_list = CustomModel::where($map)->order('id desc')->paginate()->each(function($item,$key){
            $item->categorys = db('call_custom_cat')->where(['id'=>$item['category']])->value('title');

        });

        // 分页数据
        $page = $data_list->render();

        $btn_access = [
            'title' => '设置客户分类',
            'icon'  => 'fa fa-fw fa-navicon ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('cat',['id'=>'__id__'])
        ];

        $catList = db('call_custom_cat')->where(['status'=>1])->column('id,title');
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')

            ->setSearch(['tel' => '电话','mobile' => '手机','name'=>'客户'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['name', '客户名称'],
                ['tel', '客户电话'],
                ['mobile', '客户手机'],
                ['categorys', '分类'],
                ['source', '来源'],
                ['email', '邮箱'],
                ['address', '地址'],
                ['note_time', '记录时间'],
                ['note_area', '记录地区'],
                ['fee', '成本'],
                ['extend_url', '推广链接'],
                ['create_time', '创建时间','datetime'],
                // ['status', '状态', 'switch'],
                // ['right_button', '操作', 'btn']
            ])
            ->addTopButton('custom', $btn_access,true)
            // ->addRightButton('del')
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
        
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

  //       `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '客户导入日志id',
  // `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '客户导入日志表',
  // `rate` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '净得率',
  // `create_time` int(10) unsigned DEFAULT NULL,

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
            ['id', 'auto','ID'],
            ['project_id', 'auto','项目ID'],
            ['name', 'auto','客户名称'],
            ['tel', 'auto','客户电话'],
            ['mobile', 'auto','客户手机'],
            ['source', 'auto','来源'],
            ['email', 'auto','邮箱'],
            ['address', 'auto','地址'],
            ['note_time', 'auto','记录时间'],
            ['note_area','auto', '记录地区'],
            ['fee', 'auto','成本'],
            ['extend_url', 'auto','推广链接'],
            ['create_time', 'auto','创建时间']
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
                'note_time' => '记录时间',
                'note_area' => '记录地区',
                'source' => '来源',
                'extend_url' => '推广链接',
                'policy' => '政策',
                'fee' => '成本',
                'address' => '地址',
                'email' => '邮箱'
            ];
            // 调用插件('插件',[路径,导入表名,字段限制,类型,条件,重复数据检测字段])
            $import = plugin_action('Excel/Excel/import', [$full_path, 'call_custom', $fields, $type = 0, $where = null, $main_field = 'mobile'], $second_field = 'project_id');

            
            // 失败或无数据导入 计算净得率
            if ($import['error']){
                if ($import['error']==10) {
                    $s['rate'] = $import['rate'];
                    // $s['title'] = $import['tabNm'];
                    $s['title'] = get_file_name($excel_file);
                    CustomEXLogModel::create($s);
                }
                $this->error($import['message'], url('import'));
            }

            $s['rate'] = '100%';
            $s['title'] = get_file_name($excel_file);
            CustomEXLogModel::create($s);
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
        foreach ($sources as $key => $value) {
            $list_source[$value] = $value;
        }

        $btn_alloc = [
            'title' => '分配',
            'icon'  => 'fa fa-fw fa-stack-overflow',
            'class' => 'btn btn-default ajax-post',
            'href' => url('alloc')
        ];

        if ($group=='tab1') {
            $map['status'] = 1;
            // 数据列表
            $data_list = RecoverdtModel::where($map)->order('id desc')->paginate();

            // 分页数据
            $page = $data_list->render();

     
            // 使用ZBuilder快速创建数据表格
            return ZBuilder::make('table')
                // ->hideCheckbox()
                ->setPageTitle('回收列表') // 设置页面标题
                ->setTabNav($list_tab,  $group)
                ->setSearchArea([
                    ['daterange', 'create_time', '加入时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                    ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
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
                    ['user', '操作人'],
                    // ['status', '状态', 'switch'],
                    // ['right_button', '操作', 'btn']
                ])

                ->addTopButton('custom', $btn_alloc,['title' => '分配员工'])
                // ->addRightButton('delete')
                ->setRowList($data_list)// 设置表格数据
                ->raw('project,user') // 使用原值
                ->fetch(); // 渲染模板
        }
        

        if ($group=='tab2') {
            $map['status'] = 9;
            // 数据列表
            $data_list = RecoverdtModel::where($map)->order('id desc')->paginate();

            // 分页数据
            $page = $data_list->render();
            // 使用ZBuilder快速创建数据表格
            return ZBuilder::make('table')
                // ->hideCheckbox()
                ->setPageTitle('公海列表') // 设置页面标题
                ->setTabNav($list_tab,  $group)
                ->setSearchArea([
                    ['daterange', 'create_time', '加入时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                    ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
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
                    ['user', '操作人'],
                ])
                ->addTopButton('custom', $btn_alloc,['title' => '分配员工'])
                // ->addTopButton('add', ['href' => url('add')])
                // ->addRightButton('delete')
                ->setRowList($data_list)// 设置表格数据
                ->raw('project,user') // 使用原值
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

                $insert_id = $props->id;
                //log
                $sl = [];
                $ids = explode(',', $data['ids']);
                foreach ($ids as $key => $value) {
                    //回收数据id
                    db('call_recover_data')->where(['id'=>$value])->update(['status'=>0]);
                    $custom_id = db('call_recover_data')->where(['id'=>$value])->value('custom_id');
                    $sl[$key]['alloc_id'] = $insert_id;
                    $sl[$key]['custom_id'] = $custom_id;
                    $sl[$key]['create_time'] = time();
                    $sl[$key]['alloc_count'] = 1;
                }
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