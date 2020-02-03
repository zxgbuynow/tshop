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

        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

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

        $itemEdit = ['icon' => 'fa fa-pencil', 'title' => '编辑商品', 'href' => url('editItem', ['id' => '__id__'])];
        $itemAdd = ['icon' => 'fa fa-plus', 'title' => '添加商品', 'href' => url('addItem')];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('商品管理') // 设置页面标题
            ->setTableName('item') // 设置数据表名
            ->addTopButton('back', [
                'title' => '返回订单列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('index')
            ])
            ->setSearch(['title' => '商品名']) // 设置搜索参数
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
            ->addTopButton('custom',$itemAdd)
            ->addRightButton('custom',$itemEdit) // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->raw('project') // 使用原值
            ->fetch(); // 渲染页面
    }
    /**
     * [addItem 添加商品]
     */
    public function addItem()
    {

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['create_time'] = time();
            if ($props = ItemModel::create($data)) {
              $this->success('新增成功', url('item'));
            } else {
              $this->error('新增失败', url('item'));
            }
        }


        $list_project = db('call_project_list')->where(['status'=>1])->column('id,col1');
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '商品名称'],
                ['select', 'project_id', '项目','',$list_project],
                ['text', 'unit', '单位'],
                ['text', 'price', '价格'],
                ['textarea', 'note', '备注'],
                ['radio', 'status', '商品状态', '', ['否', '是'], 1],
            ])
            ->fetch();

    }

    /**
     * [editItem 编辑商品]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function editItem($id = null)
    {
      if ($id === null) $this->error('缺少参数');
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if (ItemModel::update($data)) {
                $this->success('编辑成功', url('item'));
            } else {
                $this->error('编辑失败', url('item'));
            }
        }
        
        $info = ItemModel::get($id);
        $list_project = db('call_project_list')->where(['status'=>1])->column('id,col1');

        //市
        //区
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'title', '商品名称'],
                ['select', 'project_id', '项目','',$list_project],
                ['text', 'unit', '单位'],
                ['text', 'price', '价格'],
                ['textarea', 'note', '备注'],
                ['radio', 'status', '商品状态', '', ['否', '是'], 1],
            ])
            ->setFormData($info)
            ->fetch();
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

        $btnAdd = ['icon' => 'fa fa-plus', 'title' => '添加商品明细', 'href' => url('orderAdd', ['id' => $id])];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('子订单管理') // 设置页面标题
            ->setTableName('order') // 设置数据表名
            ->addTopButton('back', [
                'title' => '返回订单列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('index')
            ])
            // ->setSearch(['mobile' => '手机号']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['trade', '订单'],
                ['item', '商品'],
                ['price', '金额'],
                ['num', '数量'],
                ['create_time', '创建时间', 'datetime'],
                // ['right_button', '操作', 'btn']
            ])
            ->addTopButton('custom',$btnAdd)
            // ->addTopButtons('enable,disable,delete') // 批量添加顶部按钮
            // ->addRightButtons('delete') // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->raw('trade,item') // 使用原值
            ->fetch(); // 渲染页面
    }
    /**
     * [orderAdd 添加明细]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function orderAdd($id = null)
    { 

      if ($id === null) $this->error('缺少参数');
      if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $price =  db('call_item')->where(['id'=>$data['item_id']])->value('price');
            $data['create_time'] = time();
            $data['trade_id'] = $id;
            $data['price'] = $price*$data['num'];
            if ($props = OrderModel::create($data)) {
              $this->success('新增成功', url('order',['id'=>$id]));
            } else {
              $this->error('新增失败', url('order',['id'=>$id]));
            }
        }


        $list_item = db('call_item')->where(['status'=>1])->column('id,title');
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['select', 'item_id', '商品','',$list_item],
                ['text', 'num', '商品个数'],
            ])
            ->fetch();
    }
    /**
     * 新增
     * @return mixed
     */
    public function add()
    {
  //     `trade_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  // `item_id` int(10) unsigned DEFAULT '0' COMMENT '商品id',
  // `price` decimal(20,3) NOT NULL COMMENT '商品价格',
  // `num` decimal(20,3) NOT NULL COMMENT '数量',
  // `create_time` int(10) unsigned DEFAULT NULL,
  // `status` tinyint(1) DEFAULT '1' COMMENT '0失效',
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['sign_time'] =strtotime($data['sign_time']);
            $data['create_time'] = time();
            if ($props = TradeModel::create($data)) {
              $this->success('新增成功', url('index'));
            } else {
              $this->error('新增失败');
            }
        }


        $list_province = db('packet_common_area')->where(['level'=>1])->column('area_code,area_name');
        $list_project = db('call_project_list')->where(['status'=>1])->column('id,col1');
        // $list_item = db('call_item')->where(['status'=>1])->column('id,title');
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '订单名称'],
                ['select', 'project_id', '项目','',$list_project],
                ['text', 'total', '总金额'],
                ['text', 'payment', '支付金额'],
                ['text', 'brokerage', '提成'],
                ['text', 'surplus', '余额'],
                // ['select', 'item_id', '商品','',$list_item],
                // ['text', 'num', '商品个数'],
                ['text', 'note', '备注'],
                ['datetime', 'sign_time', '签单时间'],
                ['radio', 'status', '订单状态', '', ['progress' => '进行', 'finsh' => '完成', 'end' => '人工结束']],
            ])
            ->addLinkage('sign_area_province', '选择省份', '', $list_province, '', url('get_city'), 'sign_area_city,sign_area_area')
            ->addLinkage('sign_area_city', '选择城市', '', '', '', url('get_area'), 'sign_area_area')
            ->addSelect('sign_area_area', '选择地区')
            ->fetch();
    }
    // 根据省份获取城市
    public function get_city($sign_area_province = '')
    {
        $arr['code'] = '1'; //判断状态
        $arr['msg'] = '请求成功'; //回传信息
        $city = db('packet_common_area')->where(['parent_code'=>$sign_area_province])->field('area_code,area_name')->select();
        $arr['list'] = [];
        foreach ($city as $key => $value) {
          $arr['list'][$key]['key'] = $value['area_code']; 
          $arr['list'][$key]['value'] = $value['area_name']; 
        }
        
        return json($arr);
    }
    // 根据省份获取区
    public function get_area($sign_area_city = '')
    {
        $arr['code'] = '1'; //判断状态
        $arr['msg'] = '请求成功'; //回传信息
        $area = db('packet_common_area')->where(['parent_code'=>$sign_area_city])->field('area_code,area_name')->select();
        $arr['list'] = [];
        foreach ($area as $key => $value) {
          $arr['list'][$key]['key'] = $value['area_code']; 
          $arr['list'][$key]['value'] = $value['area_name']; 
        }
        
        return json($arr);
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
            if (TradeModel::update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        
        $info = TradeModel::get($id);
        $list_province = db('packet_common_area')->where(['level'=>1])->column('area_code,area_name');
        $list_project = db('call_project_list')->where(['status'=>1])->column('id,col1');

        //市
        $list_city = db('packet_common_area')->where(['parent_code'=>$info['sign_area_province']])->column('area_code,area_name');
        //区
        $list_area = db('packet_common_area')->where(['parent_code'=>$info['sign_area_city']])->column('area_code,area_name');
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'title', '订单名称'],
                ['select', 'project_id', '项目','',$list_project],
                ['text', 'total', '总金额'],
                ['text', 'payment', '支付金额'],
                ['text', 'brokerage', '提成'],
                ['text', 'surplus', '余额'],
                ['text', 'note', '备注'],
                ['datetime', 'sign_time', '签单时间'],
                ['radio', 'status', '订单状态', '', ['progress' => '进行', 'finsh' => '完成', 'end' => '人工结束']],

            ])
            ->addLinkage('sign_area_province', '选择省份', '', $list_province, '', url('get_city'), 'sign_area_city,sign_area_area')
            ->addLinkage('sign_area_city', '选择城市', '', $list_city, '', url('get_area'), 'sign_area_area')
            ->addSelect('sign_area_area', '选择地区','',$list_area)
            ->setFormData($info)
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