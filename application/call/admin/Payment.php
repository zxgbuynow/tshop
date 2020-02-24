<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Payment as PaymentModel;

/**
 * 首页后台控制器
 */
class Payment extends Admin
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
        $data_list = PaymentModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

  

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['brank_account' => '客户账号'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['trade', '合同'],
                ['price', '金额'],
                ['type', '回款类型',['','首付款','货款','尾款']],
                ['brank_account', '客户账号'],
                ['is_notice', '是否提醒',['否','是']],
                ['sign_time', '回款时间','datetime'],
                ['create_time', '创建时间','datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add')
            ->addRightButton('edit')
            ->setRowList($data_list)// 设置表格数据
            ->raw('trade') // 使用原值
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
            $data['sign_time'] =strtotime($data['sign_time']);
            $data['create_time'] = time();
            if ($props = PaymentModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        //       `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单回款id',
  // `trade_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  // `price` decimal(20,3) NOT NULL COMMENT '回款金额',
  // `type` tinyint(1) DEFAULT '1' COMMENT '1订金 2货款 3尾款',
  // `brank_account` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '名称',
  // `is_notice` tinyint(1) DEFAULT '0' COMMENT '1已提醒',
  // `notice_time` int(10) unsigned DEFAULT NULL,
  // `sign_time` int(10) unsigned DEFAULT NULL,
  // `create_time` int(10) unsigned DEFAULT NULL,
  // `status` tinyint(1) DEFAULT '1' COMMENT '0失效',

        $list_trade = db('call_trade')->where(['status'=>'progress'])->column('id,title');
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['select', 'trade_id', '合同','',$list_trade],
                ['text', 'price', '回款金额'],
                ['radio', 'type', '回款方式', '', [1=>'首付款', 2=>'货款', 3=>'尾款'], 1],
                ['text', 'brank_account', '名称'],
                ['datetime', 'sign_time', '回款时间'],
                
                ['radio', 'status', '状态', '', ['失效', '有效'], 1],
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
            $data['sign_time'] =strtotime($data['sign_time']);
            if (PaymentModel::update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        $list_trade = db('call_trade')->where(['status'=>'progress'])->column('id,title');

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'trade_id', '合同','',$list_trade],
                ['text', 'price', '回款金额'],
                ['radio', 'type', '回款方式', '', [1=>'首付款', 2=>'货款', 3=>'尾款'], 1],
                ['text', 'brank_account', '名称'],
                ['datetime', 'sign_time', '回款时间'],
                
                ['radio', 'status', '状态', '', ['失效', '有效'], 1],

            ])
            ->setFormData(PaymentModel::get($id))
            ->fetch();
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
        $menu_title = AuthModel::where('id', 'in', $ids)->column('custom');
        return parent::setStatus($type, ['call_auth_'.$type, 'call', 0, UID, implode('、', $menu_title)]);
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