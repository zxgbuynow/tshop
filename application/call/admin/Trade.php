<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Trade as TradeModel;
use app\call\model\Order as OrderModel;
use app\call\model\Item as ItemModel;
use app\call\model\Projectls as ProjectlsModel;

/**
 * 首页后台控制器
 */
class Trade extends Admin
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
        $data_list = TradeModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

  //       `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '话术id',
  // `project_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  // `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '名称',
  // `total` decimal(20,3) NOT NULL COMMENT '总金额',
  // `payment` decimal(20,3) NOT NULL COMMENT '支付金额',
  // `brokerage` decimal(20,3) NOT NULL COMMENT '提成',
  // `surplus` decimal(20,3) NOT NULL COMMENT '余额',  
  // `note` longtext COLLATE utf8_unicode_ci COMMENT '备注',
  // `sign_area_city` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '城市',
  // `sign_area_area` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '区',
  // `sign_area_province` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '省',
  // `sign_time` int(10) unsigned DEFAULT NULL,
  // `create_time` int(10) unsigned DEFAULT NULL,
  // `status` varchar(20) DEFAULT 'progress' COMMENT 'progress 进行finsh完成 end人工结束',

        $btnAdd = ['icon' => 'fa fa-plus', 'title' => '子订单列表', 'href' => url('order', ['id' => '__id__'])];

        $itemAdd = ['icon' => 'fa fa-plus', 'title' => '添加商品', 'href' => url('item')];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '订单标题'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['project', '关联项目'],
                ['title', '订单标题'],
                ['total', '总金额'],
                ['payment', '支付金额'],
                ['brokerage', '提成'],
                ['surplus', '余额'],
                ['signcity', '签单城市'],
                ['sign_time', '签订时间','datetime'],
                ['create_time', '创建时间','datetime'],
                ['statustx', '状态'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('add')])
            ->addTopButton('custom', $itemAdd)
            ->addRightButton('edit')
            ->addRightButton('custom', $btnAdd)
            ->setRowList($data_list)// 设置表格数据
            ->raw('project,statustx,signcity') // 使用原值
            ->fetch(); // 渲染模板
        
    }

    /**
     * [item 商品]
     * @return [type] [description]
     */
    public function item()
    {
      if ($id === null) $this->error('缺少参数');

        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        $map['trade_id'] = $id;
        // 数据列表
        $data_list = ItemModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();
  // `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品id',
  // `project_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  // `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '名称',
  // `unit` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '单位',
  // `price` decimal(20,3) NOT NULL COMMENT '商品价格',
  // `note` longtext COLLATE utf8_unicode_ci COMMENT '备注',
  // `create_time` int(10) unsigned DEFAULT NULL,
  // `status` tinyint(1) DEFAULT '1' COMMENT '0失效',

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('商品管理') // 设置页面标题
            ->setTableName('item') // 设置数据表名
            ->setSearch(['mobile' => '手机号']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['project', '项目'],
                ['title', '商品名'],
                ['price', '单价'],
                ['unit', '单位'],
                ['status', '状态', 'switch'],
                ['create_time', '创建时间', 'datetime'],
                ['right_button', '操作', 'btn']
            ])
            // ->addTopButtons('enable,disable,delete') // 批量添加顶部按钮
            // ->addRightButtons('delete') // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->raw('project') // 使用原值
            ->fetch(); // 渲染页面
    }
    /**
     * 子订单管理
     * @return mixed
     */
    public function order($id = null)
    {
       if ($id === null) $this->error('缺少参数');

        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        $map['trade_id'] = $id;
        // 数据列表
        $data_list = OrderModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

  //       `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单明细id',
  // `trade_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  // `item_id` int(10) unsigned DEFAULT '0' COMMENT '商品id',
  // `price` decimal(20,3) NOT NULL COMMENT '商品价格',
  // `num` decimal(20,3) NOT NULL COMMENT '数量',
  // `create_time` int(10) unsigned DEFAULT NULL,
  // `status` tinyint(1) DEFAULT '1' COMMENT '0失效',

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('子订单管理') // 设置页面标题
            ->setTableName('order') // 设置数据表名
            ->setSearch(['mobile' => '手机号']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['trade', '订单'],
                ['item', '商品'],
                ['price', '金额'],
                ['num', '数量'],
                ['create_time', '创建时间', 'datetime'],
                // ['right_button', '操作', 'btn']
            ])
            // ->addTopButtons('enable,disable,delete') // 批量添加顶部按钮
            // ->addRightButtons('delete') // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->raw('trade,item') // 使用原值
            ->fetch(); // 渲染页面
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
            $data['start_time'] =strtotime($data['start_time']);
            $data['end_time'] =  strtotime($data['end_time']);
            if ($props = AuthModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'custom', '客户名'],
                ['text', 'domain', '域名'],
                ['text', 'ip', '服务器ID'],
                ['radio', 'online', '授权方式', '', ['线下', '线上'], 1],
                ['datetime', 'start_time', '开始时间'],
                ['datetime', 'end_time', '结束时间'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],
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
            $data['start_time'] =strtotime($data['start_time']);
            $data['end_time'] =  strtotime($data['end_time']);
            if (AuthModel::update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'custom', '客户名'],
                ['text', 'domain', '域名'],
                ['text', 'ip', '服务器ID'],
                ['radio', 'online', '授权方式', '', ['线下', '线上'], 1],
                ['datetime', 'start_time', '开始时间'],
                ['datetime', 'end_time', '结束时间'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],

            ])
            ->setFormData(AuthModel::get($id))
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