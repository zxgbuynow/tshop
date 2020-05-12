<?php

namespace plugins\TableBm\controller;

use app\common\builder\ZBuilder;
use app\common\controller\Common;
use plugins\TableBm\model\TableBm;
use plugins\TableBm\validate\TableBm as TableBmValidate;
use think\Db;

/**
 * 插件后台管理控制器
 * @package plugins\TableBm\controller
 */
class Admin extends Common
{
    public function index()
    {
        // 查询条件
        $map = $this->getMap();
        $data_list = TableBm::where($map)->order('id desc')->paginate();
        // 分页数据
        $page = $data_list->render();
        $btn_access = [
            'title' => '字段设置',
            'icon'  => 'fa fa-fw fa-key',
            'href'  => plugin_url('TableBm/Admin/field_set',array('form_id'=>'__id__'))
        ];
        return ZBuilder::make('table')
            ->setPageTitle('表单列表')
            ->addColumn('id', 'ID')
            ->addColumns([
                ['title', '标题'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButton('edit', ['plugin_name' => 'TableBm'])
            ->addTopButton('add', ['plugin_name' => 'TableBm'])
            ->addRightButton('custom', $btn_access)
            ->setTableName('plugin_form')
            ->setRowList($data_list)
            ->setPages($page)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 验证数据
            $result = $this->validate($data, [
                'title|标题' => 'require',
            ]);
            if(true !== $result){
                // 验证失败 输出错误信息
                $this->error($result);
            }
            $data['create_time'] = time();
            // 插入数据
            if (TableBm::create($data)) {
                $this->success('新增成功', cookie('__forward__'));
            } else {
                $this->error('新增失败');
            }
        }

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('新增')
            ->addFormItem('text', 'title', '标题')
            ->fetch();
    }

    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            // 使用自定义的验证器验证数据
            $validate = new TableBmValidate();
            if (!$validate->check($data)) {
                // 验证失败 输出错误信息
                $this->error($validate->getError());
            }
            $data['update_time'] = time();
            // 更新数据
            if (TableBm::update($data)) {
                $this->success('编辑成功', cookie('__forward__'));
            } else {
                $this->error('编辑失败');
            }
        }
        $id = input('param.id');

        // 获取数据
        $info = TableBm::get($id);

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑')
            ->addFormItem('hidden', 'id')
            ->addFormItem('text', 'title', '标题')
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 插件自定义方法
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function testTable()
    {
        // 使用ZBuilder快速创建表单
        return ZBuilder::make('table')
            ->setPageTitle('插件自定义方法(列表)')
            ->setSearch(['said' => '名言', 'name' => '出处'])
            ->addColumn('id', 'ID')
            ->addColumn('said', '名言')
            ->addColumn('name', '出处')
            ->addColumn('status', '状态', 'switch')
            ->addColumn('right_button', '操作', 'btn')
            ->setTableName('plugin_hello')
            ->fetch();
    }

    /**
     * 插件自定义方法
     * 这里的参数是根据插件定义的按钮链接按顺序设置
     * @param string $id
     * @param string $table
     * @param string $name
     * @param string $age
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     * @throws \think\Exception
     */
    public function testForm($id = '', $table = '', $name = '', $age = '')
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            halt($data);
        }

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('插件自定义方法(表单)')
            ->addFormItem('text', 'name', '出处')
            ->addFormItem('text', 'said', '名言')
            ->fetch();
    }

    /**
     * 自定义页面
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function testPage()
    {
        // 1.使用默认的方法渲染模板，必须指定完整的模板文件名（包括模板后缀）
//        return $this->fetch(config('plugin_path'). 'TableBm/view/index.html');

        // 2.使用已封装好的快捷方法，该方法只用于加载插件模板
        // 如果不指定模板名称，则自动加载插件view目录下与当前方法名一致的模板
        return $this->pluginView();
//         return $this->pluginView('index'); // 指定模板名称
//         return $this->pluginView('', 'tpl'); // 指定模板后缀
    }

    # 字段设置
    public function field_set()
    {
        $w['form_id'] = input('form_id',0);
        $data_list = Db::name('admin_plugin_form_data')->where($w)->order('sort asc')->paginate();

        $returnbtn_access = [
            'title' => '返回',
            'icon'  => 'fa fa-fw fa-arrow-circle-left',
            'href'  => '/admin.php/admin/plugin/manage/name/TableBm'
        ];

        $addbtn_access = [
            'title' => '添加',
            'icon'  => 'fa fa-fw fa-plus',
            'href'  => plugin_url('TableBm/Admin/field_set_add',array('form_id'=>input('form_id',0)))
        ];

        $editbtn_access = [
            'title' => '编辑',
            'icon'  => 'fa fa-fw fa-edit',
            'href'  => plugin_url('TableBm/Admin/field_set_edit',array('id'=>'__id__'))
        ];
        $delbtn_access = [
            'title' => '删除',
            'icon'  => 'fa fa-fw fa-close',
            'href'  => plugin_url('TableBm/Admin/field_set_del',array('id'=>'__id__'))
        ];

        return ZBuilder::make('table')
            ->setPageTitle('字段设置(列表)')
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['sort', '排序'],
                ['title', '字段标题'],
                ['input_type', '字段类型'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('custom',$returnbtn_access) // 添加顶部按钮
            ->addTopButton('custom',$addbtn_access) // 添加顶部按钮
            ->addRightButton('custom',$editbtn_access) // 添加编辑按钮
            ->addRightButton('custom',$delbtn_access) //添加删除按钮
            ->setTableName('admin_plugin_form_data')
            ->setRowList($data_list) // 设置表格数据
            ->fetch();
//        return $this->pluginView();
    }

    public function field_set_add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            unset($data['__token__']);
            $data['form_id'] = input('form_id',0);
            $data['create_time'] = time();
            $data['update_time'] = time();
            if ($hook = Db::name('admin_plugin_form_data')->insertGetId($data)) {
                $this->success('新增成功', plugin_url('TableBm/Admin/field_set',array('form_id'=>input('form_id',0))));
            } else {
                $this->error('新增失败');
            }
        }

        $fieldtype = [
            'text' => '文本类型',
            'select' => '下拉框',
            'radio' => '单选',
            'checkbox' => '多选',
            'textarea' => '多行文本',
            'radio_text' => '多选文本'
        ];
        return ZBuilder::make('form')
            ->setPageTitle('新增')
            ->addText('sort', '排序', '','1')
            ->addText('title', '标题', '')
            ->addSelect('input_type', '字段类型','多选文本类型：<a target="_blank" href="http://cdnimg.k-d.fun/%E5%BE%AE%E4%BF%A1%E6%88%AA%E5%9B%BE_20190716162212.png">参考图片</a>',$fieldtype,'text')
            ->addText('placeholder', '提示文字', '多选文本类型填写参考：普通话|0,广东话|0,英语|0,其他|1')
            ->fetch();
    }

    public function field_set_edit($id = 0)
    {
        $id = input('id',0);
        if ($id === 0) $this->error('参数错误');
        $info = Db::name('admin_plugin_form_data')->where(array('id'=>$id))->find();
        if ($this->request->isPost()) {
            $data = $this->request->post();
            unset($data['__token__']);
            unset($data['form_id']);
            unset($data['id']);
            $data['update_time'] = time();
            if ($hook = Db::name('admin_plugin_form_data')->where(array('id'=>$id))->update($data)) {
                $this->success('编辑成功', plugin_url('TableBm/Admin/field_set',array('form_id'=>$info['form_id'])));
            } else {
                $this->error('编辑失败');
            }
        }

        $fieldtype = [
            'text' => '文本类型',
            'select' => '下拉框',
            'radio' => '单选',
            'checkbox' => '多选',
            'textarea' => '多行文本'
        ];

        return ZBuilder::make('form')
            ->setPageTitle('编辑')
            ->addHidden('id',$id)
            ->addText('sort', '排序', '',$info['sort'])
            ->addText('title', '标题', '',$info['title'])
            ->addSelect('input_type', '字段类型','多选文本类型：<a target="_blank" href="http://cdnimg.k-d.fun/%E5%BE%AE%E4%BF%A1%E6%88%AA%E5%9B%BE_20190716162212.png">参考图片</a>',$fieldtype,'text')
            ->addText('placeholder', '提示文字', '多选文本类型填写参考：普通话|0,广东话|0,英语|0,其他|1')
            ->fetch();
    }

    public function field_set_del($record = [])
    {
        $ids   = $this->request->isPost() ? input('post.id/a') : input('param.id');
        $map = [
            ['id', 'in', $ids]
        ];
        if (Db::name('admin_plugin_form_data')->where($map)->delete()) {
            $this->success('操作成功',plugin_url('TableBm/Admin/field_set',array('form_id'=>input('form_id',0))));
        }else{
            $this->error('操作失败');
        }
    }





    /**
     * 快速编辑（启用/禁用）
     * @param string $status 状态
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    public function quickEdit($status = '')
    {
        $id        = $this->request->post('pk');
        $status    = $this->request->param('value');
        $hook_name = HookModel::where('id', $id)->value('name');

        if (false === HookPluginModel::where('hook', $hook_name)->setField('status', $status == 'true' ? 1 : 0)) {
            $this->error('操作失败，请重试');
        }
        cache('hook_plugins', null);
        $details = $status == 'true' ? '启用钩子' : '禁用钩子';
        return parent::quickEdit(['hook_edit', 'admin_hook', $id, UID, $details]);
    }

    /**
     * 启用
     * @param array $record 行为日志内容
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function enable($record = [])
    {
        return $this->setStatus('enable');
    }

    /**
     * 禁用
     * @param array $record 行为日志内容
     * @author 蔡伟明 <314013107@qq.com>
     * @return mixed
     */
    /**
     * 禁用
     * @param array $record 行为日志内容
     * @author 蔡伟明 <314013107@qq.com>
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function disable($record = [])
    {
        return $this->setStatus('disable');
    }


}
