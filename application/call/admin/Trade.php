<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Trade as TradeModel;
use app\call\model\Order as OrderModel;
use app\call\model\Item as ItemModel;
use app\call\model\Projectls as ProjectlsModel;
use app\call\model\Tradecat as TradecatModel;
use app\call\model\Tradelog as TradelogModel;

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

        if (isset($map['custom_id'])) {
            $mm['name'] = $map['custom_id'];
            $custom_ids = db('call_custom')->where($mm)->value('id');
            $map['custom_id'] = array('in',$custom_ids);
        }

        if (isset($map['menger'])) {
            $mm['nickname'] = $map['menger'];
            $custom_ids = db('admin_user')->where($mm)->value('id');
            $map['menger'] = array('in',$custom_ids);
        }

        // if (isset($map['project_id'])) {
        //     $mmm['col1'] = $map['project_id'];
        //     $project_ids = db('call_project_list')->where($mm)->value('id');
        //     $map['project_id'] = array('in',$project_ids);
        // }

        // 数据列表
        $data_list = TradeModel::where($map)->order('id desc')->paginate()->each(function($item, $key) use ($map){
            $item->menger = db('admin_user')->where(['id'=>$item['menger']])->value('nickname');
            $item->sign_area_city = db('packet_common_area')->where(['area_code'=>$item['sign_area_city']])->value('area_name');
            $item->sign_area_province = db('packet_common_area')->where(['area_code'=>$item['sign_area_province']])->value('area_name');
        });

        // 分页数据
        $page = $data_list->render();

        $btn_access = [
            'title' => '配置',
            'icon'  => 'fa fa-fw fa-cog ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('setting',['id'=>'__id__'])
        ];

        $catelsbt = [
            'title' => '设置合同类型',
            'icon'  => 'fa fa-fw fa-navicon ',
            'href' => url('catls',['id'=>'__id__'])
        ];

        $btnexport = [
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('export',http_build_query($this->request->param()))
        ];

        $btnAdd = ['icon' => 'fa fa-plus', 'title' => '合同商品明细列表', 'href' => url('order', ['id' => '__id__'])];

        $itemAdd = ['icon' => 'fa fa-plus', 'title' => '添加商品', 'href' => url('item')];
        $tradeLog = ['icon' => 'fa  fa-fw fa-envelope-o', 'title' => '变更合同日志', 'href' => url('tradeLog',['id'=>'__id__'])];

        $project = db('call_project_list')->where(['status'=>1])->column('id,col1');
        $list_province = db('packet_common_area')->where(['level'=>1])->column('area_code,area_name');
        $list_city = db('packet_common_area')->where(['level'=>2])->column('area_code,area_name');
        //商品名？
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearchArea([
                ['text:6', 'title', '合同名称','like'],
                ['text:6', 'total', '合同金额','like'],
                ['text:6', 'brokerage', '可提成金额','like'],
                ['text:6', 'payment', '当日实际收款','like'],
                ['text:6', 'surplus', '余款','like'],
                ['text:6', 'menger', '所有者','like'],
                ['text:6', 'custom_id', '客户名称','like'],
                ['text:6', 'contactMobile', '电话','like'],

                ['select', 'project_id', '项目', '', '', $project],
                ['select', 'status', '合同状态','','',['progress'=>'进行','finsh'=>'完成','end'=>'人工结束']],
                ['daterange', 'sign_time', '签约时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],

            ])
            ->addFilter('sign_area_province', $list_province) // $list_province 是用于将省份id转为省份名称
            ->addFilter('sign_area_city', $list_city) // $list_city 是用于将城市id转为城市名称
            ->addFilterMap('sign_area_city', 'sign_area_province')
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['project', '关联项目'],
                ['title', '合同标题'],
                ['total', '合同金额'],
                ['payment', '当日实际收款'],
                ['brokerage', '可提成金额'],
                ['surplus', '余额'],
                ['signcity', '签订城市'],
                ['should_time', '应收日期','date'],
                ['sign_time', '签约时间','datetime'],
                ['create_time', '创建时间','datetime'],
                ['statustx', '状态'],
                ['menger', '负责人'],
                ['sign_area_province', '省'],
                ['sign_area_city', '市'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('add')])
            ->addTopButton('custom', $itemAdd)
            ->addRightButton('edit')
            ->addRightButton('custom', $btnAdd)
            ->addTopButton('custom', $catelsbt)
            ->addTopButton('custom', $btnexport)
            ->addRightButton('custom', $tradeLog,true)
            ->addTopButton('custom', $btn_access,true)
            ->setRowList($data_list)// 设置表格数据
            ->raw('project,statustx,signcity') // 使用原值
            ->fetch(); // 渲染模板
        
    }

    /**
     * [classNReportexport 导出]
     * @return [type] [description]
     */
    public function export()
    {
        $map = $this->getMaps();

        $data_list = TradeModel::where($map)->order('id desc')->paginate()->each(function($item, $key) use ($map){
            $item->menger = db('admin_user')->where(['id'=>$item['menger']])->value('nickname');
            $item->username = db('call_custom')->where(['id'=>$item['custom_id']])->value('name');
            $item->should_time = date('Y-m-d',$item['should_time']);
            $item->sign_time = date('Y-m-d H:i',$item['should_time']);
            $item->create_time = date('Y-m-d H:i',$item['create_time']);
        });
        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['id', 'auto', 'ID'],
            ['project', 'auto', '关联项目'],
            ['title','auto',  '合同标题'],
            ['total','auto',  '合同金额'],
            ['payment', 'auto', '当日实际收款'],
            ['brokerage','auto',  '可提成金额'],
            ['surplus','auto',  '余额'],
            ['signcity', 'auto', '签订城市'],
            ['should_time','auto',  '应收日期','date'],
            ['sign_time', 'auto', '签约时间','datetime'],
            ['create_time', 'auto', '创建时间','datetime'],
            ['statustx','auto',  '状态'],
            ['menger', 'auto', '所有者'],
            ['username','auto',  '客户名称'],
            ['contactMobile','auto',  '电话'],
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['合同数据', $cellName, $data_list]);
    }

    /**
     * [catls 分类列表]
     * @return [type] [description]
     */
    public function catls()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = TradecatModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();


        $catedit_bt = [
            'title' => '编辑合同分类',
            'icon'  => 'fa fa-fw fa-pencil ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('catedit',['id'=>'__id__'])
        ];

        $catdel_bt = [
            'title' => '删除合同分类',
            'icon'  => 'fa fa-fw fa-trash-o ',
            'class' => 'btn btn-default ajax-get confirm',
            'href' => url('catdelete',['id'=>'__id__']),
            'data-title' => '删除后无法恢复'
        ];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '标题'])// 设置搜索框
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '标题'],
                ['right_button', '操作', 'btn']
            ])
            // ->addTopButton('add', ['href' => url('add')])
            ->addRightButton('custom',$catedit_bt,true)
            ->addRightButton('custom',$catdel_bt)
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * [catedit 编辑]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function catedit($id=null)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            
            if ($props = TradecatModel::update($data)) {
                $this->success('编辑成功', null,'_parent_reload');
            } else {
                $this->error('编辑失败',null,'_close_pop');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'title', '分类标题'],
            ])
            ->setFormData(TradecatModel::get($id))
            ->fetch();
    }

    /**
     * [catdelete 删了]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function catdelete($id=null)
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data['id'] = $id;
            if ($props = TradecatModel::where($data)->delete()) {
                $this->success('删除成功', null);
            } else {
                $this->error('删除失败',null);
            }
        }
    }
    /**
     * [tradeLog 日志]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function tradeLog($id = null)
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = TradelogModel::where($map)->order('id desc')->paginate()->each(function($item, $key) use ($map){
            $item->trade = db('call_trade')->where(['id'=>$item['trade_id']])->value('title');
            $item->oper = db('admin_user')->where(['id'=>$item['oper']])->value('nickname');
        });

        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('变更合同日志') // 设置页面标题
            ->hideCheckbox()
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['trade', '合同'],
                ['node', '日志'],
                ['oper', '操作人'],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     * [setting description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function setting($id = null)
    {
      if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($props = TradecatModel::create($data)) {
              $this->success('配置成功', null,'_close_pop');
            } 
        }
       
        return ZBuilder::make('form')
                ->setPageTitle('配置') // 设置页面标题
                ->addFormItems([ // 批量添加表单项
                    ['text', 'title', '类型','<code>合同类型</code>'],
                ])
                ->fetch();
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
                'title' => '返回合同列表',
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

        $btnAdd = ['icon' => 'fa fa-plus', 'title' => '添加商品明细', 'href' => url('orderAdd', ['id' => $id])];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('合同明细') // 设置页面标题
            ->setTableName('order') // 设置数据表名
            ->addTopButton('back', [
                'title' => '返回合同列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('index')
            ])
            // ->setSearch(['mobile' => '手机号']) // 设置搜索参数
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['trade', '合同'],
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
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['sign_time'] =strtotime($data['sign_time']);
            $data['should_time'] =strtotime($data['should_time']);
            $data['create_time'] = time();
            $data['role'] = db('admin_user')->where(['id'=>$data['menger']])->value('role');
            if ($props = TradeModel::create($data)) {
              $this->success('新增成功', url('index'));
            } else {
              $this->error('新增失败');
            }
        }

        $list_province = db('packet_common_area')->where(['level'=>1])->column('area_code,area_name');
        $list_project = db('call_project_list')->where(['status'=>1])->column('id,col1');
        $user = db('admin_user')->where('id','gt','1')->column('id,nickname');
        $type  = TradecatModel::column('id,title');

        // $custom = db('call_custom')->column('id,mobile');
        $custom = db('call_custom')->select();

        $roleid = db('admin_user')->where(['id'=>UID])->value('role');
        $access_moblie = db('admin_role')->where(['id'=>$roleid])->value('access_moblie');

        $customs = [];
        foreach ($custom as $key => $value) {
            $customs[$value['id']]= $access_moblie?$value['name'].' '.replaceTel($value['mobile']):$value['name'].' '.$value['mobile'];
            // $customs[$key]['id'] = $value['id'];
        }
        // print_r($customs);exit;
        // $list_item = db('call_item')->where(['status'=>1])->column('id,title');
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '合同名称'],
                ['text', 'serialNO', '合同序列号'],
                ['text', 'contactMobile', '手机号'],
                ['select', 'custom_id', '客户','',$customs],
                ['select', 'project_id', '项目','',$list_project],
                ['select', 'type', '合同类型','',$type],
                ['select', 'menger', '负责人','',$user],
                ['text', 'total', '合同金额'],
                ['text', 'payment', '当日实际收款'],
                ['text', 'brokerage', '可提成金额'],
                ['text', 'surplus', '余额'],
                // ['select', 'item_id', '商品','',$list_item],
                // ['text', 'num', '商品个数'],
                // ['text', 'note', '备注'],
                ['datetime', 'sign_time', '签约时间'],
                ['date', 'should_time', '应收日期'],
                ['radio', 'status', '合同状态', '', ['progress' => '进行', 'finsh' => '完成', 'end' => '人工结束']],
            ])
            ->addLinkage('sign_area_province', '选择省份', '', $list_province, '', url('get_city'), 'sign_area_city,sign_area_area')
            ->addLinkage('sign_area_city', '选择城市', '', '', '', url('get_area'), 'sign_area_area')
            ->addSelect('sign_area_area', '选择地区')

            ->addLinkage('sign_area_province1', '选择省份(备用)', '', $list_province, '', url('get_city1'), 'sign_area_city1,sign_area_area1')
            ->addLinkage('sign_area_city1', '选择城市(备用)', '', '', '', url('get_area1'), 'sign_area_area1')
            ->addSelect('sign_area_area1', '选择地区(备用)')
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
    // 根据省份获取城市
    public function get_city1($sign_area_province1 = '')
    {
        $arr['code'] = '1'; //判断状态
        $arr['msg'] = '请求成功'; //回传信息
        $city = db('packet_common_area')->where(['parent_code'=>$sign_area_province1])->field('area_code,area_name')->select();
        $arr['list'] = [];
        foreach ($city as $key => $value) {
          $arr['list'][$key]['key'] = $value['area_code']; 
          $arr['list'][$key]['value'] = $value['area_name']; 
        }
        
        return json($arr);
    }
    // 根据省份获取区
    public function get_area1($sign_area_city1 = '')
    {
        $arr['code'] = '1'; //判断状态
        $arr['msg'] = '请求成功'; //回传信息
        $area = db('packet_common_area')->where(['parent_code'=>$sign_area_city1])->field('area_code,area_name')->select();
        $arr['list'] = [];
        foreach ($area as $key => $value) {
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
            $data['should_time'] =strtotime($data['should_time']);
            $data['role'] = db('admin_user')->where(['id'=>$data['menger']])->value('role');
            $s['node'] = $data['log'];
            if (TradeModel::update($data)) {
              if (isset($s['node'])) {
                $s['trade_id'] = $data['id'];
                db('call_trade_log')->insert($s);
              }
              $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        
        $info = TradeModel::get($id);
        $list_province = db('packet_common_area')->where(['level'=>1])->column('area_code,area_name');
        $list_project = db('call_project_list')->where(['status'=>1])->column('id,col1');
        $user = db('admin_user')->where('id','gt','1')->column('id,nickname');
        $type  = TradecatModel::column('id,title');
        // $custom = db('call_custom')->column('id,mobile');
        $custom = db('call_custom')->select();
        //市
        $list_city = db('packet_common_area')->where(['parent_code'=>$info['sign_area_province']])->column('area_code,area_name');
        //区
        $list_area = db('packet_common_area')->where(['parent_code'=>$info['sign_area_city']])->column('area_code,area_name');
        //市
        $list_city1 = db('packet_common_area')->where(['parent_code'=>$info['sign_area_province1']])->column('area_code,area_name');
        //区
        $list_area1 = db('packet_common_area')->where(['parent_code'=>$info['sign_area_city1']])->column('area_code,area_name');
        $roleid = db('admin_user')->where(['id'=>UID])->value('role');
        $access_moblie = db('admin_role')->where(['id'=>$roleid])->value('access_moblie');
        $customs = [];
        foreach ($custom as $key => $value) {
            $customs[$value['id']]= $access_moblie?$value['name'].' '.replaceTel($value['mobile']):$value['name'].' '.$value['mobile'];
            // $customs[$key]['id'] = $value['id'];
        }
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'title', '合同名称'],
                ['text', 'serialNO', '合同序列号'],
                ['text', 'contactMobile', '手机号'],
                ['select', 'custom_id', '客户','',$customs],
                ['select', 'project_id', '项目','',$list_project],
                ['select', 'type', '合同类型','',$type],
                ['select', 'menger', '负责人','',$user],
                ['text', 'total', '合同金额'],
                ['text', 'payment', '当日实际收款'],
                ['text', 'brokerage', '可提成金额'],
                ['text', 'surplus', '余额'],
                // ['select', 'item_id', '商品','',$list_item],
                // ['text', 'num', '商品个数'],
                
                ['datetime', 'sign_time', '签约时间'],
                ['date', 'should_time', '应收日期'],
                ['radio', 'status', '合同状态', '', ['progress' => '进行', 'finsh' => '完成', 'end' => '人工结束']],


            ])
            ->addLinkage('sign_area_province', '选择省份', '', $list_province, '', url('get_city'), 'sign_area_city,sign_area_area')
            ->addLinkage('sign_area_city', '选择城市', '', $list_city, '', url('get_area'), 'sign_area_area')
            ->addSelect('sign_area_area', '选择地区','',$list_area)

            ->addLinkage('sign_area_province1', '选择省份(备用)', '', $list_province, '', url('get_city1'), 'sign_area_city1,sign_area_area1')
            ->addLinkage('sign_area_city1', '选择城市(备用)', '', $list_city1, '', url('get_area1'), 'sign_area_area1')
            ->addSelect('sign_area_area1', '选择地区(备用)','',$list_area1)
            
            ->addTextarea('log','变更合同备注')
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