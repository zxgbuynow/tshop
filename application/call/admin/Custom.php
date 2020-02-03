<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Custom as CustomModel;
use app\call\model\CustomEXLog as CustomEXLogModel;
use app\call\model\Recoverdt as RecoverdtModel;
use app\admin\model\Config as ConfigModel;
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
        $data_list = CustomModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')

            ->setSearch(['tel' => '电话','mobile' => '手机','name'=>'客户'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['name', '客户名称'],
                ['tel', '客户电话'],
                ['mobile', '客户手机'],
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
            // ->addTopButton('add', ['href' => url('add')])
            // ->addRightButton('del')
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
        
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

        if ($group=='tab1') {
            $map['status'] = 1;
            // 数据列表
            $data_list = RecoverdtModel::where($map)->order('id desc')->paginate();

            // 分页数据
            $page = $data_list->render();

      //       `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '品牌id',
      // `project_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
      // `custom_id` int(10) unsigned DEFAULT '0' COMMENT '客户id',
      // `create_time` int(10) unsigned DEFAULT NULL,
      // `status` tinyint(1) DEFAULT '1' COMMENT '9公海1回收',
            // 使用ZBuilder快速创建数据表格
            return ZBuilder::make('table')
                ->hideCheckbox()
                ->setPageTitle('回收列表') // 设置页面标题
                ->setTabNav($list_tab,  $group)
                // ->setSearch(['tel' => '电话','mobile' => '手机','name'=>'客户'])// 设置搜索框
                ->addColumns([ // 批量添加数据列
                    ['id', 'ID'],
                    ['project', '项目'],
                    ['custom', '客户'],
                    ['create_time', '创建时间','datetime'],
                    ['status', '状态', 'switch'],
                    ['right_button', '操作', 'btn']
                ])

                // ->addTopButton('add', ['href' => url('add')])
                ->addRightButton('delete')
                ->setRowList($data_list)// 设置表格数据
                ->raw('project,custom') // 使用原值
                ->fetch(); // 渲染模板
        }
        

        if ($group=='tab2') {
            $map['status'] = 9;
            // 数据列表
            $data_list = RecoverdtModel::where($map)->order('id desc')->paginate();

            // 分页数据
            $page = $data_list->render();

      //       `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '品牌id',
      // `project_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
      // `custom_id` int(10) unsigned DEFAULT '0' COMMENT '客户id',
      // `create_time` int(10) unsigned DEFAULT NULL,
      // `status` tinyint(1) DEFAULT '1' COMMENT '9公海1回收',
            // 使用ZBuilder快速创建数据表格
            return ZBuilder::make('table')
                ->hideCheckbox()
                ->setPageTitle('公海列表') // 设置页面标题
                ->setTabNav($list_tab,  $group)
                // ->setSearch(['tel' => '电话','mobile' => '手机','name'=>'客户'])// 设置搜索框
                ->addColumns([ // 批量添加数据列
                    ['id', 'ID'],
                    ['project', '项目'],
                    ['custom', '客户'],
                    ['create_time', '创建时间','datetime'],
                    ['status', '状态', 'switch'],
                    ['right_button', '操作', 'btn']
                ])

                // ->addTopButton('add', ['href' => url('add')])
                ->addRightButton('delete')
                ->setRowList($data_list)// 设置表格数据
                ->raw('project,custom') // 使用原值
                ->fetch(); // 渲染模板
        }

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
        return parent::quickEdit(['call_auth_edit', 'call', 0, UID, $id]);
    }
   
    
}