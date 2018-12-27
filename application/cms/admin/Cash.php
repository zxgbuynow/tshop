<?php


namespace app\cms\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\cms\model\Cash as CashModel;
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
class Cash extends Admin
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
        $data_list = CashModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

        $btnlook = [
            'class' => 'btn btn-xs btn-default',
            'title' => '查看',
            'icon'  => 'fa fa-fw fa-eye',
            'href'  => url('look',['id' => '__id__'])
        ];
        
        $btnpass = [
            'class' => 'btn btn-xs btn-default ajax-get confirm',
            'title' => '通过',
            'icon'  => 'fa fa-fw fa-check',
            'href'  => url('pass',['id' => '__id__'])
        ];
        $btnpast = [
            'class' => 'btn btn-xs btn-default ajax-get confirm',
            'title' => '拒绝',
            'icon'  => 'fa fa-fw fa-remove',
            'href'  => url('past',['id' => '__id__'])
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['cardno', '银行卡'],
                ['cardnm', '银行卡联系人'],
                ['payment', '金额'],
                ['apply_time', '申请时间','datetime'],
                ['op_time', '操作时间','datetime'],
                ['oper', '操作人'],
                ['status', '状态','','',['待审','通过','已提现','未通过']],
                ['right_button', '操作', 'btn']
            ])
            ->raw('oper')
            // ->addTopButtons('add,enable,disable') // 批量添加顶部按钮
            ->addRightButton('custom', $btnlook)
            ->addRightButton('custom', $btnpass)
            ->addRightButton('custom', $btnpast)
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }

    
    public function look($id = null)
    {
        if ($id === null) $this->error('请选择要操作的数据');

        $u = db('cash')->where(['id'=>$id])->value('cid');

        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        $map['id'] = $u;
        // 数据列表
        $data_list = CounsellorModel::where($map)->paginate();
        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('咨询师') // 设置页面标题
            ->setTableName('member') // 设置数据表名
            ->hideCheckbox()
            ->addColumns([ // 批量添加列
                ['nickname', '咨询师'],
                ['income', '总收入'],
                ['totalget', '总提现金额'],
                ['totalcash', '现有金额'],
            ])
            ->raw('income')
            ->raw('totalget')
            ->raw('totalcash')
            ->addTopButton('back', [
                'title' => '返回列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('index')
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面


    }

    public function pass($id = null)
    {
        if ($id === null) $this->error('请选择要操作的数据');

        db('cash')->where(['id'=>$id])->update(['status'=>1,'opuid'=>UID,'op_time'=>time()]);
        $this->success('成功');
    }

    public function past($id = null)
    {
        if ($id === null) $this->error('请选择要操作的数据');
        db('cash')->where(['id'=>$id])->update(['status'=>3,'opuid'=>UID,'op_time'=>time()]);
        $this->success('成功');
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