<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Custom as CustomModel;
use app\call\model\CustomEXLog as CustomEXLogModel;

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
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            // ->addTopButton('add', ['href' => url('add')])
            ->addRightButton('del')
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
            'icon'  => 'fa fa-fw fa-export',
            'href' => url('exportCus')
        ];

  //       `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '客户导入日志id',
  // `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '客户导入日志表',
  // `rate` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '净得率',
  // `create_time` int(10) unsigned DEFAULT NULL,

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title'=>'客户导入日志表'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '分批导入表名'],
                ['rate', '净得率']
            ])
            ->addTopButton('custom', $btn_access)
            // ->addRightButton('del')
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
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
            // `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '客户名称',
            //  `tel` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT '客户电话',
            //   `mobile` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT '客户手机',
            //   `note_time` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '记录时间',
            //   `note_area` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '记录地区',
            //   `source` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '来源',
            //   `extend_url` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '推广链接',
            //   `policy` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '政策',
            //   `fee` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '成本',
            //   `address` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '地址',
            //   `email` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '邮箱',
            // 只导入的字段列表
            $fields = [
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
            $import = plugin_action('Excel/Excel/import', [$full_path, 'call_custom', $fields, $type = 0, $where = null, $main_field = 'mobile']);

            
            // 失败或无数据导入 计算净得率
            if ($import['error']){
                if ($import['error']==9) {
                    $s['rate'] = $import['rate'];
                    $s['title'] = $import['tabNm'];
                    CustomEXLogModel::insert($s);
                }
                $this->error($import['message']);
            }

            $s['rate'] = '100%';
            $s['title'] = $import['tabNm'];
            CustomEXLogModel::insert($s);
            // 导入成功
            $this->success($import['message']);
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
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            config('recover_data_hour',$data['recover_data_hour']);
            $this->success('操作成功', cookie('__forward__'));
        }

        // 获取数据
        $info = [
            'recover_data_hour'=>config('recover_data_hour'),
        ];

       
        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('回收配置') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'recover_data', 'recover_data_hour', '回收设置','超过X小时自动回收数据'],
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();

    }
    /**
     * [gtback 回收列表]
     * @return [type] [description]
     */
    public function gtback()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);


        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = CustomModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

  //       `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '品牌id',
  // `project_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  // `custom_id` int(10) unsigned DEFAULT '0' COMMENT '客户id',
  // `create_time` int(10) unsigned DEFAULT NULL,
  // `status` tinyint(1) DEFAULT '1' COMMENT '9公海1回收',
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
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
            ->addRightButton('del')
            ->setRowList($data_list)// 设置表格数据
            ->raw('project,custom') // 使用原值
            ->fetch(); // 渲染模板

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