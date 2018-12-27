<?php


namespace app\cms\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\cms\model\Coupon as CouponModel;
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
class Coupon extends Admin
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
        $data_list = CouponModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('export')
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '标题'],
                ['price', '金额'],
                ['member', '所属会员'],
                ['created_time', '创建时间','datetime'],
                ['use', '使用状态','','',['未使用','已使用']],
                ['right_button', '操作', 'btn']
            ])
            ->raw('member')
            // ->addTopButton('add', ['href' => url('add')])
            // ->addRightButton('edit')
            ->addTopButton('custom', $btnexport)
            ->addRightButton('delete', ['data-tips' => '删除后无法恢复。'])// 批量添加右侧按钮
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * [tradexport 导出]
     * @return [type] [description]
     */
    public function export()
    {
        
        //查询数据
        $map = array();
        $data = CouponModel::where($map)->order('id desc')->select();
        $status = ['0'=>'未使用', '1'=>'已使用'];
        foreach ($data as $key => $value) {
            $data[$key]['member'] = CouponModel::getMemberAttr(null,$value);
            $data[$key]['use'] = $status[$value['status']];
            
        }
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['id','auto', 'ID'],
            ['title','auto', '标题'],
            ['price','auto', '金额'],
            ['member','auto', '所属会员'],
            ['created_time','auto', '创建时间', 'datetime'],
            ['use','auto', '审核状态'],
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['优惠券表', $cellName, $data]);
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
            $data['created_time'] = time();
            if ($props = CouponModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '标题'],
                ['text', 'price', '面值'],
                ['text', 'address', '地址'],
                ['datetime', 'start_time', '开始时间'],
                ['datetime', 'endtime', '结束时间'],
                ['image', 'pic', '课程封面'],
                ['textarea', 'intro', '目录'],
            ])
            ->addWangeditor('describe', '内容')
            ->addFile('file', '语音', '', '', '5120', 'mp3,wav')
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
            if (CouponModel::update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],

                ['text', 'title', '标题'],
                ['number', 'num', '限定人数'],
                ['text', 'price', '定价'],
                ['text', 'address', '地址'],
                ['datetime', 'start_time', '开始时间'],
                ['datetime', 'endtime', '结束时间'],
                ['image', 'pic', '课程封面'],
            ])
             ->addWangeditor('describe', '内容')
             ->addFile('file', '语音', '', '', '5120', 'mp3,wav')
            ->setFormData(CouponModel::get($id))
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
        $menu_title = CouponModel::where('id', 'in', $ids)->column('title');
        return parent::setStatus($type, ['coupon_'.$type, 'class', 0, UID, implode('、', $menu_title)]);
    }

    
}