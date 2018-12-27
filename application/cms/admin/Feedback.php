<?php


namespace app\cms\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\cms\model\Counsellor as CounsellorModel;
use app\cms\model\Category as CategoryModel;
use app\cms\model\CateAccess as CateAccessModel;
use app\cms\model\Agency as AgencyModel;
use app\shop\model\User as UserModel;
use app\cms\model\Feedback as FeedbackModel;
use util\Tree;
use think\Db;
use think\Hook;

/**
 * Notice默认控制器
 * @package app\member\admin
 */
class Feedback extends Admin
{
    /**
     * 咨询师首页
     * @TODO 所属机构
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        if (isset($map['status'])&&$map['status'][1]=='%已处理%') {
            $map['status'] = 1;
        }
        if (isset($map['status'])&&$map['status'][1]=='%未处理%') {
            $map['status'] = 0;
        }
        

        // 数据列表
        $data_list = FeedbackModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('投诉管理') // 设置页面标题
            ->setTableName('cms_feedback') // 设置数据表名
            ->setSearch(['content' => '内容','status'=>'状态']) // 设置搜索参数
            ->addTimeFilter('create_time')
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['username', '用户'],
                ['phone', '手机号'],
                ['content', '内容'],
                ['create_time', '创建时间', 'datetime'],
                ['statusxt', '状态', ['未处理', '已处理']],
                ['status', '设置状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->raw('username')
            ->raw('statusxt')
            // ->addTopButtons('delete') // 批量添加顶部按钮
            ->addRightButtons('delete') // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     * 新增
     * @author zg
     * @return mixed
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $data['create_time'] = time();
            if ($data = NoticeModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }
        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('新增') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'title', '标题'],
                ['textarea', 'content', '内容'],
                ['radio', 'status', '状态', '', ['禁用', '启用'], 1]
            ])
            ->fetch();
    }

    /**
     * 编辑
     * @param null $id 用户id
     * @author zg
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (NoticeModel::update($data)) {
                $this->success('编辑成功', cookie('__forward__'));
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = NoticeModel::where('id', $id)->find();

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['text', 'title', '标题', ''],
                ['textarea', 'content', '内容'],
                ['radio', 'status', '状态', '', ['禁用', '启用']]
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

  
    /**
     * 删除用户
     * @param array $ids 用户id
     * @author zg
     * @return mixed
     */
    public function delete($ids = [])
    {
        // Hook::listen('user_delete', $ids);
        return $this->setStatus('delete');
    }

    /**
     * 启用用户
     * @param array $ids 用户id
     * @author zg
     * @return mixed
     */
    public function enable($ids = [])
    {
        // Hook::listen('user_enable', $ids);
        return $this->setStatus('enable');
    }

    /**
     * 禁用用户
     * @param array $ids 用户id
     * @author zg
     * @return mixed
     */
    public function disable($ids = [])
    {
        // Hook::listen('user_disable', $ids);
        return $this->setStatus('disable');
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
        $menu_title = FeedbackModel::where('id', 'in', $ids)->column('mobile');
        return parent::setStatus($type, ['feedback_'.$type, 'feedback', 0, UID, implode('、', $menu_title)]);
    }

    
}
