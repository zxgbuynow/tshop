<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Extension as ExtensionModel;

/**
 * 首页后台控制器
 */
class Extension extends Admin
{

    
    /**
     * 分机配置
     * @return [type] [description]
     */
    public function index()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = ExtensionModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

        $btn_access = [
            'title' => '添加分机',
            'icon'  => 'fa fa-fw fa-navicon ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('add')
        ];

        $catList = db('call_custom_cat')->where(['status'=>1])->column('id,title');
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '分机代码'],
                ['status', '状态', 'switch'],
            ])
            ->addTopButton('custom', $btn_access,true)
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * [add 添加]
     */
    public function add()
    {
         // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if ($props = ExtensionModel::create($data)) {
                $this->success('新增成功', null,'_parent_reload');
            } else {
                $this->error('新增失败',null,'_parent_reload');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '分机代码'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1]
            ])
            ->fetch();
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