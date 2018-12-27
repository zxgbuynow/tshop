<?php


namespace app\cms\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\cms\model\Refund as RefundModel;
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
class Refund extends Admin
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
        $data_list = RefundModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

        $btn_refund = [
            'title' => '完成退款',
            'icon'  => 'fa fa-fw fa-key',
            'class' => 'btn btn-xs btn-default ajax-get confirm',
            'href'  => url('dorefund', ['id' => '__id__']),
            'data-title' => '确认完成退款吗？'
        ];

        $btn_refuse = [
            'title' => '拒绝',
            'icon'  => 'fa fa-fw fa-key',
            'class' => 'btn btn-xs btn-default ajax-get confirm',
            'href'  => url('dorefuse', ['id' => '__id__']),
            'data-title' => '确认拒绝吗？'
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '标题'],
                ['payment', '金额'],
                ['member', '用户名'],
                ['status', '状态','','',['待审核 ','通过','完成','未通过']],
                ['right_button', '操作', 'btn']
            ])
            ->raw('member')
            ->addRightButton('custom', $btn_refund)
            ->addRightButton('custom', $btn_refuse)
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); //  渲染模板
    }
    /**
     * [dorefund 完成]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function dorefund($id = null)
    {
        db('refund')->where(['id'=>$id])->update(['status'=>2]);
    }
    /**
     * [dorefund 完成]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function dorefuse($id = null)
    {
        db('refund')->where(['id'=>$id])->update(['status'=>3]);
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
        $menu_title = RefundModel::where('id', 'in', $ids)->column('title');
        return parent::setStatus($type, ['refundModel_'.$type, 'class', 0, UID, implode('、', $menu_title)]);
    }

    
}