<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Alloc as AllocModel;
use app\call\model\Alloclg as AlloclgModel;
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

// `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分配id',
//   `op_id` int(10) unsigned DEFAULT '0' COMMENT '操作员id',
//   `call_count` int(10) unsigned DEFAULT '0' COMMENT '呼叫次数',
//   `alloc_count` int(10) unsigned DEFAULT '0' COMMENT '分配次数',
//   `create_time` int(10) unsigned DEFAULT NULL,
//   `status` tinyint(1) DEFAULT '1' COMMENT '0失效',
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            // ->setSearch(['domain' => '域名','custom'=>'客户'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['oper', '操作员'],
                ['call_count', '呼叫次数'],
                ['alloc_count', '分配次数'],
                ['create_time', '创建时间','datetime'],
                ['way', '分配方式','',['','平均分配','选配']],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('add')])
            ->addRightButton('edit')
            ->setRowList($data_list)// 设置表格数据
            ->raw('oper') // 使用原值
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