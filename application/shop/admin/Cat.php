<?php
namespace app\shop\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\shop\model\Cat as CatModel;
use app\shop\model\Brand as BrandModel;
use app\shop\model\Props as PropsModel;
use app\shop\model\CatRelBrands as CatRelBrandsModel;
use app\shop\model\CatRelProps as CatRelPropsModel;
use util\Tree;
use think\Db;

/**
 * 定时任务日志后台控制器
 */
class Cat extends Admin
{

    // 分类列表
    public function index()
    {
        // 查询
        $map = $this->getMap();

        // 数据列表
        $data_list = Db::view('cat', true)
            ->order('cat.id')
            ->select();
        if (empty($map)) {
            $data_list = Tree::toList($data_list);
        }
        $btnAdd = ['icon' => 'fa fa-plus', 'title' => '新增子菜单', 'href' => url('add', ['pid' => '__id__'])];

        $propAdd = ['icon' => 'fa fa-fw fa-institution', 'title' => '属性', 'href' => url('props', ['id' => '__id__'])];
        $brandAdd = ['icon' => 'fa fa-fw fa-navicon', 'title' => '品牌', 'href' => url('brand', ['id' => '__id__'])];

        return ZBuilder::make('table')
            ->setSearch(['title' => '标题'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '分类标题', 'callback', function($value, $data){
                    return isset($data['title_prefix']) ? $data['title_display'] : $value;
                }, '__data__'],
                ['modified_time', '更新时间', 'datetime'],
                ['order_sort', '排序', 'text.edit'],
                ['status', '状态', 'status','',['关闭','启用']],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('add')])
            ->addTopButtons('enable,disable')// 批量添加顶部按钮
            // ->addRightButton('custom', $btnAdd)
            ->addRightButton('custom', $propAdd)
            ->addRightButton('custom', $brandAdd)
            ->replaceRightButton(['level' => ['<', 3]], '', ['custom'])
            ->addRightButton('edit')
            ->addRightButton('delete', ['data-tips' => '删除后无法恢复。'])// 批量添加右侧按钮
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }
    /**
     * [brand 品牌]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function brand($id=null)
    {
        if ($id === null) $this->error('缺少参数');
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if ($data['id']) {
                $data['cat_id'] = $id;
                $data['brand_id'] = implode(',', $data['brand_id']);

                if (CatRelBrandsModel::update($data)) {
                    $this->success('编辑成功', url('index'));
                } else {
                    $this->error('编辑失败');
                }
            }else{
                $data['cat_id'] = $id;
                $data['brand_id'] = implode(',', $data['brand_id']);
                unset($data['id']);
                if (CatRelBrandsModel::create($data)) {
                    $this->success('添加成功', url('index'));
                } else {
                    $this->error('添加失败');
                }
            }
            
        }
        $data = CatRelBrandsModel::where(['cat_id'=>$id])->find();
        $data['brand_id'] = explode(',', $data['brand_id']);
        $brand = BrandModel::column('id,brand_name');
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'brand_id', '品牌', '<code>可多选</code>', $brand,'','multiple'],
            ])
            ->setFormData($data)
            ->fetch();
    }
    /**
     * [props 关联属性]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function props($id=null)
    {
        if ($id === null) $this->error('缺少参数');
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if ($data['id']) {
                $data['cat_id'] = $id;
                $data['prop_id'] = implode(',', $data['prop_id']);

                if (CatRelPropsModel::update($data)) {
                    $this->success('编辑成功', url('index'));
                } else {
                    $this->error('编辑失败');
                }
            }else{
                $data['cat_id'] = $id;
                $data['prop_id'] = implode(',', $data['prop_id']);
                unset($data['id']);
                if (CatRelPropsModel::create($data)) {
                    $this->success('添加成功', url('index'));
                } else {
                    $this->error('添加失败');
                }
            }
            
        }
        $data = CatRelPropsModel::where(['cat_id'=>$id])->find();
        $data['prop_id'] = explode(',', $data['prop_id']);
        $brand = PropsModel::column('id,prop_name');
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'prop_id', '属性', '<code>可多选</code>', $brand,'','multiple'],
            ])
            ->setFormData($data)
            ->fetch();
    }
    /**
     * 新增
     * @param int $pid 菜单父级id
     * @author zg
     * @return mixed
     */
    public function add($pid = 0)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // $data['modified_time'] = time();
            // 验证
            //获得上级level
            if ($data['pid']) {
                $level = CatModel::where(['id'=>$pid])->value('level');
                $data['level'] = $level+1;
            }
            $result = $this->validate($data, 'Cat');

            if(true !== $result) $this->error($result);
            
            if ($Category = CatModel::create($data)) {
                // 记录行为
                action_log('cat_add', 'Cat', $Category['pid'], UID, $data['title']);
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }
        
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'pid', $pid],
                ['select', 'pid', '父类', '<code>必选</code>', CatModel::getTreeList(),$pid],
                ['text', 'title', '分类标题'],
                ['text', 'order_sort', '排序', '', 100],
                ['image', 'cat_logo', '分类图片'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1]
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
            $data['update_time'] = time();
            // 验证
            $result = $this->validate($data, 'Cat');
            if(true !== $result) $this->error($result);

            if (CatModel::update($data)) {
                // 记录行为
                action_log('cat_edit', 'Cat', $id, UID, $data['cat_name']);
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'pid', '父类', '<code>必选</code>', CatModel::getTreeList(0)],
                ['text', 'title', '分类标题'],
                ['text', 'order_sort', '排序', '', 100],
                ['image', 'cat_logo', '分类图片'],
                ['radio', 'disabled', '立即启用', '', ['否', '是'], 1]
            ])
            ->setFormData(CatModel::get($id))
            ->fetch();
    }

    /**
     * 删除菜单
     * @param null $ids 菜单id
     * @return mixed
     */
    public function delete($ids = null)
    {
        // 检查是否有子菜单
        if (CatModel::where('pid', $ids)->find()) {
            $this->error('请先删除或移动该菜单下的子菜单');
        }
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
        $menu_title = CatModel::where('id', 'in', $ids)->column('title');
        return parent::setStatus($type, ['cat_'.$type, 'cat', 0, UID, implode('、', $menu_title)]);
    }

    /**
     * 快速编辑
     * @param array $record 行为日志
     * @author zg
     * @return mixed
     */
    public function quickEdit($record = [])
    {
        $id      = input('post.pk', '');
        $field   = input('post.name', '');
        $value   = input('post.value', '');
        $menu    = CatModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $menu . ')，新值：(' . $value . ')';
        return parent::quickEdit(['cat_edit', 'cat', $id, UID, $details]);
    }
    function replace($data) {
    // 取出右侧按钮的Str字符串
        $rightButtonStr = $data['right_button'];
        // 根据自己的自定义规则任意处理正则隐藏不需要的按钮或修改内容
        if ($data['pid'] != 0) {
            $rightButtonStr = preg_replace('/<a\stitle="新增子菜单".*?<\/a>/', '', $rightButtonStr);
        }
        // 将新的按钮组覆盖原行数据(此时Bulider已处理完按钮的编译，覆盖即覆盖到已编译完成的结果)
        $data['right_button'] = $rightButtonStr;
        // 返回本列原有的内容
        return $data['right_button1'];
    }

}