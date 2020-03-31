<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Speechcraft as SpeechcraftModel;

/**
 * 首页后台控制器
 */
class Speechcraft extends Admin
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
        $data_list = SpeechcraftModel::where($map)->order('sort ASC')->paginate();

        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '名称'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['project', '项目'],
                ['title', '名称'],
                ['sort', '排序'],
                ['create_time', '创建时间','datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('add')])
            ->addRightButton('edit')
            ->setRowList($data_list)// 设置表格数据
            ->raw('project') // 使用原值
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
            $data['create_time'] =  time();
            if ($props = SpeechcraftModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }
        $list_project = db('call_project_list')->where(['status'=>1])->column('id,col1');
        $list_task = db('call_alloc')->where(['status'=>1])->column('id,name');
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['select', 'project_id', '项目','',$list_project],
                ['select', 'alloc_id', '项目','',$list_task],
                ['text', 'title', '名称'],
                ['number', 'sort', '排序','<code>越小越排前</code>'],
                ['textarea', 'content', '内容'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],
            ])
            ->addTags('tags', '标签')
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
            if (SpeechcraftModel::update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        $list_project = db('call_project_list')->where(['status'=>1])->column('id,col1');
        $list_task = db('call_alloc')->where(['status'=>1])->column('id,name');
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'project_id', '项目','',$list_project],
                ['select', 'alloc_id', '项目','',$list_task],
                ['text', 'title', '名称'],
                ['number', 'sort', '排序','<code>越小越排前</code>'],
                ['textarea', 'content', '内容'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],

            ])
            ->addTags('tags', '标签')
            ->setFormData(SpeechcraftModel::get($id))
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