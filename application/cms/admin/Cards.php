<?php


namespace app\cms\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\cms\model\Cards as CardsModel;
use app\cms\model\Counsellor as CounsellorModel;
use app\cms\model\Classes as ClassModel;
use app\cms\model\Classestemp as ClassestempModel;
use app\cms\model\Category as CategoryModel;
use app\cms\model\Agency as AgencyModel;
use util\Tree;
use think\Db;

/**
 * 属性控制器
 * @package app\cms\admin
 */
class Cards extends Admin
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
        $data_list = CardsModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '标题'],
                ['payment', '金额'],
                ['member', '所属会员'],
                ['vip', '适用等级'],
                ['start_time', '生效时间','datetime'],
                ['end_time', '结束时间','datetime'],
                ['status', '状态','','',['启用','禁用']],
                ['use', '使用','','',['未使用','已使用']],
                ['right_button', '操作', 'btn']
            ])
            ->raw('member')
            ->raw('vip')
            ->addTopButtons('add,enable,disable') // 批量添加顶部按钮
            // ->addTopButton('custom', $btnexport)
             ->addRightButton('edit' )
            ->addRightButton('delete', ['data-tips' => '删除后无法恢复。'])// 批量添加右侧按钮
            ->setRowList($data_list)// 设置表格数据
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
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $data['classid'] = implode(',', $data['classid']);
            $data['mvip'] = implode(',', $data['mvip']);
            $data['create_time'] = time();
            if ($props = CardsModel::create($data)) {

                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        $classls = db('cms_clac_temp')->column('id,title');
        $mvip = array('普通会员','周会员','年会员' );
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '标题'],
                ['text', 'payment', '面值'],
                ['datetime', 'start_time', '开始时间'],
                ['datetime', 'end_time', '结束时间'],
                ['image', 'cover', '海报'],
            ])
            ->addSelect('classid', '关联课程活动', '', $classls,'','multiple')
            ->addSelect('mvip', '会员等级', '', $mvip,'','multiple')
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
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $data['classid'] = implode(',', $data['classid']);
            $data['mvip'] = implode(',', $data['mvip']);
            $data['modify_time'] = time();
            if (CardsModel::update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        
        $classls = db('cms_clac_temp')->column('id,title');
        $mvip = array('普通会员','周会员','年会员' );
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'title', '标题'],
                ['text', 'payment', '面值'],
                ['datetime', 'start_time', '开始时间'],
                ['datetime', 'end_time', '结束时间'],
                ['image', 'cover', '海报'],
            ])
            ->addSelect('classid', '关联课程活动', '', $classls,'','multiple')
            ->addSelect('mvip', '会员等级', '', $mvip,'','multiple')
            ->setFormData(CardsModel::get($id))
            ->fetch();
    }

    /**
     * 删除菜单
     * @param null $ids 菜单id
     * @author zg
     * @return mixed
     */
    public function delete($ids = null)
    {
        
        return $this->setStatus('delete');
    }

    /**
     * 启用菜单
     * @param array $record 行为日志
     * @author zg
     * @return mixed
     */
    public function enable($record = [])
    {
        return $this->setStatus('enable');
    }

    /**
     * 禁用菜单
     * @param array $record 行为日志
     * @author zg
     * @return mixed
     */
    public function disable($record = [])
    {
        return $this->setStatus('disable');
    }

    /**
     * 设置菜单状态：删除、禁用、启用
     * @param string $type 类型：delete/enable/disable
     * @param array $record
     * @author zg
     * @return mixed
     */
    public function setStatus($type = '', $record = [])
    {
        $ids        = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $menu_title = CardsModel::where('id', 'in', $ids)->column('title');
        return parent::setStatus($type, ['CardsModel_'.$type, 'class', 0, UID, implode('、', $menu_title)]);
    }

    
}