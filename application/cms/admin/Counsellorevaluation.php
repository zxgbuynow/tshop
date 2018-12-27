<?php


namespace app\cms\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\cms\model\Counsellor as CounsellorModel;
use app\cms\model\Evaluate as EvaluateModel;
use app\cms\model\Counsellorot as CounsellorotModel;
use app\cms\model\Agency as AgencyModel;
use app\cms\model\Point as PointModel;
use app\cms\model\Category as CategoryModel;
use app\cms\model\CateAccess as CateAccessModel;
use app\cms\model\Trade as TradeModel;
use util\Tree;
use think\Db;
use think\Hook;

/**
 * 咨询师默认控制器
 * @package app\member\admin
 */
class CounsellorEvaluation extends Admin
{
    /**
     * 咨询师首页
     * @TODO 所属机构
     * @return mixed
     */
    public function index($id = null)
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        if ($id) {
            $map['b.mid'] = $id;
        }

        // 数据列表
        // $data_list = EvaluateModel::where($map)->order('id desc')->paginate();
        $data_list = Db::name('evaluate')->alias('a')->field('a.*,m.mobile as mobile,m.nickname as nickname,um.mobile as umobile,um.nickname as unickname,shop_agency.title as title')->join(' calendar c',' c.id = a.cid','LEFT')->join(' trade b',' b.id = c.tid','LEFT')->join(' member m',' m.id = b.mid','LEFT')->join(' member um',' um.id = a.memberid','LEFT')->join(' shop_agency shop_agency',' shop_agency.id = m.shopid','LEFT')->where($map)->order('a.id desc')->paginate();
        // 分页数据
        $page = $data_list->render();

        $btnlook = [
            // 'class' => 'btn btn-info',
            'title' => '查看',
            'icon'  => 'fa fa-fw fa-search',
            'href'  => url('look', ['id' => '__id__'])
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('咨询师评价管理') // 设置页面标题
            ->setTableName('evaluate') // 设置数据表名
            ->setSearch(['m.mobile' => '手机号','m.nickname'=>'咨询师','sorce'=>'评分']) // 设置搜索参数
            // ->addFilter('shop_agency.title') // 添加筛选
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['mobile', '手机号'],
                ['nickname', '咨询师'],
                // ['title', '分中心'],
                ['unickname', '用户名'],
                ['umobile', '用户手机'],
                ['sorce', '评分'],
                ['cotent', '评价内容'],
                ['create_time', '创建时间', 'datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons('delete') // 批量添加右侧按钮
            ->addRightButton('custom', $btnlook) // 添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     * [look 查看评价祥情]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function look($id=null)
    {

        if ($id === null) $this->error('缺少参数');

        $map['a.id'] = $id;
        $info = Db::name('evaluate')->alias('a')->field('a.*,m.mobile as mobile,m.nickname as nickname,shop_agency.title as title')->join(' calendar c',' c.id = a.cid','LEFT')->join(' trade b',' b.id = c.tid','LEFT')->join(' member m',' m.id = b.mid','LEFT')->join(' shop_agency shop_agency',' shop_agency.id = m.shopid','LEFT')->where($map)->order('a.id desc')->find();

        // 显示编辑页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'mobile', '手机号'],
                ['text', 'nickname', '咨询师'],
                ['text', 'title', '分中心'],
                ['text', 'sorce', '评分'],
                ['textarea', 'cotent', '评价内容'],
            ])
            ->setFormdata($info)
            ->hideBtn('submit')
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
        $menu_title = EvaluateModel::where('id', 'in', $ids)->column('memberid');
        return parent::setStatus($type, ['evaluate_'.$type, 'evaluate', 0, UID, implode('、', $menu_title)]);
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
        $id      == UID && $this->error('禁止操作当前账号');
        $field   = input('post.name', '');
        $value   = input('post.value', '');
        $config  = CounsellorModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $config . ')，新值：(' . $value . ')';
        return parent::quickEdit(['member_edit', 'admin_member', $id, UID, $details]);
    }
}
