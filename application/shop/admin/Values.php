<?php
namespace app\shop\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\shop\model\Props as PropsModel;
use app\shop\model\PropValus as PropValusModel;
use util\Tree;
use think\Db;

/**
 * 属性控制器
 * @package app\cms\admin
 */
class Values extends Admin
{
    /**
     * 菜单列表
     * @return mixed
     */
    public function index($id = null)
    {
        $id === null && $this->error('参数错误');
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 查询
        $map = $this->getMap();
        $map['prop_id'] = $id;
        //获得类型
        $show_type = PropsModel::where(['id'=>$id])->value('show_type');

        // 数据列表
        $data_list = PropValusModel::where($map)->order('order_sort desc')->paginate();


        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['value' => '属性值'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['prop_value', '属性值'],
                ['order_sort', '排序', 'text.edit'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('back', ['href' => url('props/index')]) // 批量添加顶部按钮
            ->addTopButton('add', ['href' => url('add', ['prop_id' => $id,'show_type'=>$show_type])])
            // ->addTopButtons('enable,disable')// 批量添加顶部按钮
            ->addRightButton('edit', ['href' => url('add', ['prop_id' => $id,'show_type'=>$show_type,'id'=>'__id__'])])
            ->addRightButton('delete', ['data-tips' => '删除后无法恢复。'])// 批量添加右侧按钮
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * 新增
     * @return mixed
     */
    public function add($prop_id=null,$show_type=null)
    {
        $prop_id === null && $this->error('参数错误');

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            

            // 验证
            $data['prop_id'] = $prop_id;
            $result = $this->validate($data, 'PropValues');
            if(true !== $result) $this->error($result);
            unset($data['show_type']);
            if ($props = PropValusModel::create($data)) {
                // 记录行为
                action_log('props_values_add', 'props_values', $props['id'], UID, $data['prop_value']);
                $this->success('新增成功', url('index',['id' => $prop_id]));
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'show_type',$show_type],
                ['text', 'prop_value', '标题'],
                ['text', 'order_sort', '排序', '', 100],
            ])
            ->addColorpicker('prop_image', '颜色','<code>支持颜色名称（red、blue等）、十六进制值（#ff0000）、rgba 代码</code>')
            ->setTrigger('show_type','1','prop_image')
            ->fetch();
    }

    /**
     * 编辑
     * @param null $id 属性值id
     * @return mixed
     */
    public function edit($id = null,$prop_id = null,$show_type = null)
    {
        if ($id === null) $this->error('缺少参数');
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            // 验证
            $result = $this->validate($data, 'PropsValues');
            if(true !== $result) $this->error($result);
            unset($data['show_type']);
            if (PropValusModel::update($data)) {
                // 记录行为
                action_log('props_values_edit', 'cms_props_values', $id, UID, $data['value']);
                // $this->success('编辑成功', url('index',['id' => $id]));
                $this->success('编辑成功', cookie('__forward__'));
            } else {
                $this->error('编辑失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'show_type',$show_type],
                ['text', 'prop_value', '标题'],
                ['text', 'order_sort', '排序', '', 100],
            ])
            ->addColorpicker('prop_image', '颜色','<code>支持颜色名称（red、blue等）、十六进制值（#ff0000）、rgba 代码</code>')
            ->setTrigger('show_type','1','prop_image')
            ->setFormData(PropValusModel::get($id))
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
        PropValusModel::where(['id'=>$ids])->delete();

        return $this->success('删除成功', cookie('__forward__'));

        // return $this->setStatus('delete');
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
        $menu_title = PropValusModel::where('id', 'in', $ids)->column('value');
        return parent::setStatus($type, ['props_values_'.$type, 'cms_props_values', 0, UID, implode('、', $menu_title)]);
    }

    /**
     * 快速编辑
     * @param array $record 行为日志
     * @return mixed
     */
    public function quickEdit($record = [])
    {
        $id      = input('post.pk', '');
        $field   = input('post.name', '');
        $value   = input('post.value', '');
        $menu    = PropValusModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $menu . ')，新值：(' . $value . ')';
        return parent::quickEdit(['props_values_edit', 'cms_props_values', $id, UID, $details]);
    }
}