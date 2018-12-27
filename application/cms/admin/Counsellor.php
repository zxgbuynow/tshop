<?php


namespace app\cms\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\cms\model\Counsellor as CounsellorModel;
use app\cms\model\Counsellorot as CounsellorotModel;
use app\cms\model\Agency as AgencyModel;
use app\cms\model\Point as PointModel;
use app\cms\model\Category as CategoryModel;
use app\cms\model\CateAccess as CateAccessModel;
use app\cms\model\Trade as TradeModel;
use app\cms\model\Calendar as CalendarModel;
use util\Tree;
use think\Db;
use think\Hook;

/**
 * 咨询师默认控制器
 * @package app\member\admin
 */
class Counsellor extends Admin
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
        if (isset($map['status'])&&$map['status'][1]=='%待审核%') {
            $map['status']=0;
        }
        if (isset($map['status'])&&$map['status'][1]=='%上线%') {
            $map['status']=1;
        }
        $map['type'] = 1;
        // 数据列表
        $data_list = CounsellorModel::where($map)->order('id desc')->paginate();
        // $data_list = Db::name('member')->alias('a')->field('a.*')->join(' calendar c',' c.id = a.cid','LEFT')->where($map)->order('a.id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

        $list_type = AgencyModel::where('status', 1)->column('id,title');

        $btnAdd = ['icon' => 'fa fa-plus', 'title' => '积分列表', 'href' => url('point', ['id' => '__id__'])];
        $incomeBtn = ['icon' => 'fa fa-fw fa-cny', 'title' => '收列表', 'href' => url('income', ['id' => '__id__'])];
        $articleBtn = ['icon' => 'fa fa-fw fa-file-text-o', 'title' => '文章列表', 'href' => url('cms/page/index', ['id' => '__id__'])];
        $orderBtn = ['icon' => 'fa fa-fw fa-skype', 'title' => '订单列表', 'href' => url('cms/trade/index', ['id' => '__id__'])];
        $caseBtn = ['icon' => 'fa fa-fw fa-folder-open', 'title' => '案例列表', 'href' => url('cms/caselist/index', ['id' => '__id__'])];
        $btncalendar = [
            // 'class' => 'btn btn-info',
            'title' => '预约列表',
            'icon'  => 'fa fa-fw fa-calendar',
            'href'  => url('calendar', ['id' => '__id__'])
        ];
        $btneval = [
            // 'class' => 'btn btn-info',
            'title' => '评价',
            'icon'  => 'fa fa-fw fa-star',
            'href'  =>  url('cms/counsellorevaluation/index', ['id' => '__id__'])
        ];
        
        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('export')
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('咨询师管理') // 设置页面标题
            ->setTableName('member') // 设置数据表名
            ->setSearch(['mobile' => '手机号','nickname'=>'姓名','status'=>'审核状态']) // 设置搜索参数
            // ->addFilter('status', $statuslist)
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['mobile', '手机号'],
                ['nickname', '姓名'],
                ['ondatenums', '咨询次数'],
                ['ondatenum', '设置咨询次数', 'text.edit'],
                ['sources', '星级评分', 'text.edit'],
                ['sex', '性别', 'select',['0' =>'女','1' => '男']],
                ['qq', 'QQ'],
                ['weixin', '微信'],
                ['alipay', '支付宝'],
                ['shopid', '机构', 'select', $list_type],
                ['create_time', '创建时间', 'datetime'],
                ['verifystatus', '审核状态', '', '', ['待审核', '上线']],
                ['status', '状态', 'switch'],
                ['recommond', '推荐', 'switch'],
                ['sort', '排序', 'text.edit'],
                ['right_button', '操作', 'btn']
            ])
            // ->addColumn('sex', '性别', 'status', '', ['女', '男'])
            ->raw('verifystatus')
            ->raw('ondatenums')
            ->addTopButtons('enable,disable,delete') // 批量添加顶部按钮
            ->addRightButtons('delete,edit') // 批量添加右侧按钮
            ->addRightButton('custom', $btnAdd)
            ->addRightButton('custom', $articleBtn)
            ->addRightButton('custom', $orderBtn)
            ->addRightButton('custom', $btncalendar)
            ->addRightButton('custom', $caseBtn)
            ->addRightButton('custom', $btneval)
            ->addTopButton('custom', $btnexport)
            // ->addRightButton('custom', $incomeBtn)
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }
    /**
     * [tradexport 导出]
     * @return [type] [description]
     */
    public function export()
    {
        
        //查询数据
        $map['type'] = 1;
        $data = CounsellorModel::where($map)->order('id desc')->select();
        $status = ['0'=>'禁用', '1'=>'启用'];
        $recommond =  ['0'=>'不推荐', '1'=>'推荐'];
        foreach ($data as $key => $value) {
            $data[$key]['shopid'] = db('shop_agency')->where(['id'=>$value['shopid']])->value('title');
            $data[$key]['ondatenums'] = CounsellorModel::getOndatenumsAttr(null,$value);
            $data[$key]['recommond'] = $recommond[$value['recommond']];
            $data[$key]['verifystatus'] = $status[$value['status']];
            
        }
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['id','auto', 'ID'],
            ['mobile','auto', '手机号'],
            ['nickname','auto', '姓名'],
            ['ondatenums','auto', '咨询次数'],
            ['sources','auto', '星级评分'],
            ['sex','auto', '性别'],
            ['qq','auto', 'QQ'],
            ['weixin','auto', '微信'],
            ['alipay','auto', '支付宝'],
            ['shopid','auto', '机构'],
            ['create_time','auto', '创建时间', 'datetime'],
            ['verifystatus','auto', '审核状态'],
            ['recommond','auto', '推荐'],
            ['sort','auto', '排序'],
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['咨询师表', $cellName, $data]);
    }

    /**
     * [calendar description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function calendar($id=null)
    {
        if ($id === null) $this->error('缺少参数');

        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        $map['memberid'] = $id;
        // 数据列表
        $data_list = CalendarModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();


        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('咨询师咨询管理') // 设置页面标题
            ->setTableName('calendar') // 设置数据表名
            ->setSearch(['title' => '标题']) // 设置搜索参数
            ->hideCheckbox()
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['title', '标题'],
                ['tids', '订单编号'],
                ['counsollor', '咨询师'],
                ['username', '用户姓名'],
                ['chart', '咨询方式'],
                ['place', '咨询地点'],
                ['status', '状态', '', '', ['待咨询', '已咨询','已填写案历','已评价']],
                ['start_time', '开始时间', 'datetime'],
                ['end_time', '结束时间', 'datetime'],
            ])
            ->raw('counsollor')
            ->raw('username')
            ->raw('chart')
            ->raw('tids')
            ->raw('place')
            ->addTopButton('back', [
                'title' => '返回咨询师列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('counsellor/index')
            ])
            // ->addTopButtons('delete') // 批量添加顶部按钮
            // ->addRightButtons('delete') // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面

    }

    public function articlelt($id = null)
    {
        if ($id === null) $this->error('缺少参数');
        $this->redirect('cms/page/index'.'?cid='.$id);exit;

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

            $save['username'] = $data['username'];
            $save['nickname'] = $data['nickname'];
            $save['truename'] = $data['truename'];
            $save['slogan'] = $data['slogan'];
            $save['password'] = $data['password'];

            $save['qq'] = $data['qq'];
            $save['weixin'] = $data['weixin'];
            $save['alipay'] = $data['alipay'];
            $save['mobile'] = $data['mobile'];
            $save['status'] = $data['status'];
            $save['recommond'] = $data['recommond'];
            $save['shopids'] = implode(',', $data['shopids']) ;
            if ($crid = CounsellorModel::create($save)) {
                $user = CounsellorModel::get($save['id']);
                
                //添加
                $save1['status'] = $data['status'];
                $save1['memberid'] = $crid;
                $save1['per'] = $data['per'];
                $save1['wordchart'] = $data['wordchart'];
                $save1['speechchart'] = $data['speechchart'];
                $save1['videochart'] = $data['videochart'];
                $save1['facechart'] = $data['facechart'];
                $save1['intro'] = $data['intro'];
                $save1['employment'] = strtotime($data['employment']);
                
                $save1['remark'] = $data['remark'];

                $save1['intro'] = $data['intro'];

                $save1['identifi'] = $data['identifi'];
                $save1['diploma'] = $data['diploma'];

                $save1['tearch'] = $data['tearch'];
                $save1['leader'] = $data['leader'];
                $save1['cerfornt'] = $data['cerfornt'];
                $save1['cerback'] = $data['cerback'];
                $save1['tags'] = implode(',', $data['tags']);
                //业务类弄
                // $save1['tags'] = CateAccessModel::where('shopid', $data['shopid'])->column('cids')[0];
                CounsellorotModel::create($save1);
                
                
                // 记录行为
                action_log('user_add', 'admin_counsellor', $user['id'], UID, get_nickname($user['id']));
                $this->success('编辑成功', cookie('__forward__'));
            } else {
                $this->error('编辑失败');
            }
        }

        
        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('新增') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'aid'],
                ['hidden', 'bid'],
                ['hidden', 'shopid'],
                ['text', 'username', '用户名', '必填，可由英文字母、数字组成'],
                ['text', 'nickname', '昵称', '可以是中文'],
                ['image', 'avar', '头像'],
                ['text', 'truename', '真实姓名', '中文'],
                ['text', 'slogan', '座右铭'],
                ['text', 'identifi', '身份证'],
                ['image', 'diploma', '咨询师证书'],
                ['radio', 'tearch', '是否是讲师', '', ['否', '是']],
                ['radio', 'leader', '是否是团队Leader', '', ['否', '是']],

                ['password', 'password', '密码', '必填，6-20位'],
                ['text', 'mobile', '手机号'],
                ['text', 'qq', 'QQ'],
                ['text', 'weixin', '微信'],
                ['text', 'alipay', '支付宝'],
                ['image', 'cerfornt', '身份证正面'],
                ['image', 'cerback', '身份证反面'],

                ['radio', 'status', '状态', '', ['停牌', '上线']],
                ['radio', 'recommond', '推荐', '', ['不推荐', '推荐']],
                ['text', 'sort', '排序'],
                // ['date', 'employment', '从业时间'],
                ['number', 'per', '单次时长'],
                ['text', 'wordchart', '文字咨询'],
                ['text', 'wordchartlv', '文字咨询会员价'],
                ['text', 'speechchart', '语音咨询'],
                ['text', 'speechchartlv', '语音咨询会员价'],
                ['text', 'videochart', '视频咨询'],
                ['text', 'videochartlv', '视频咨询会员价'],
                ['text', 'facechart', '面对面咨询'],
                ['text', 'facechartlv', '面对面咨询会员价'],
                ['textarea', 'intro', '简介'],
            ])
            ->addDatetime('employment', '从业时间', '', '', 'YYYY-MM-DD')
            ->addSelect('tags', '咨询类型', '', $list_type)
            ->addSelect('shopids', '挂靠分机构', '', $agency_type)
            ->addUeditor('remark', '祥细说明')
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

            $save['id'] = $data['aid'];
            $save['username'] = $data['username'];
            $save['nickname'] = $data['nickname'];
            $save['password'] = $data['password'];

            $save['qq'] = $data['qq'];
            $save['avar'] = $data['avar'];
            $save['weixin'] = $data['weixin'];
            $save['alipay'] = $data['alipay'];
            $save['truename'] = $data['truename'];
            $save['slogan'] = $data['slogan'];
            $save['cerfornt'] = $data['cerfornt'];
            $save['cerback'] = $data['cerback'];
            $save['identifi'] = $data['identifi'];

            $save['mobile'] = $data['mobile'];
            $save['status'] = $data['status'];
            $save['recommond'] = $data['recommond'];
            $save['shopids'] = implode(',', $data['shopids']) ;
            if (CounsellorModel::update($save)) {
                $user = CounsellorModel::get($save['id']);
                if ($data['bid']) {
                    //更新属表
                    $save1['id'] = $data['bid'];
                    $save1['per'] = $data['per'];
                    $save1['wordchart'] = $data['wordchart'];
                    $save1['speechchart'] = $data['speechchart'];
                    $save1['videochart'] = $data['videochart'];
                    $save1['facechart'] = $data['facechart'];

                    $save1['wordchartlv'] = $data['wordchartlv'];
                    $save1['speechchartlv'] = $data['speechchartlv'];
                    $save1['videochartlv'] = $data['videochartlv'];
                    $save1['facechartlv'] = $data['facechartlv'];

                    $save1['intro'] = $data['intro'];
                    $save1['employment'] = strtotime($data['employment']);
                    $save1['remark'] = $data['remark'];

                    $save1['diploma'] = $data['diploma'];

                    $save1['tearch'] = $data['tearch'];
                    $save1['leader'] = $data['leader'];
                    
                    //业务类弄
                    // $save1['tags'] = CateAccessModel::where('shopid', $data['shopid'])->column('cids')[0];
                    $save1['tags'] = implode(',', $data['tags']);
                    CounsellorotModel::update($save1);

                }else{
                    //添加
                    $save1['status'] = $data['status'];
                    $save1['memberid'] = $data['aid'];
                    $save1['per'] = $data['per'];
                    $save1['wordchart'] = $data['wordchart'];
                    $save1['speechchart'] = $data['speechchart'];
                    $save1['videochart'] = $data['videochart'];
                    $save1['facechart'] = $data['facechart'];
                    $save1['wordchartlv'] = $data['wordchartlv'];
                    $save1['speechchartlv'] = $data['speechchartlv'];
                    $save1['videochartlv'] = $data['videochartlv'];
                    $save1['facechartlv'] = $data['facechartlv'];
                    $save1['intro'] = $data['intro'];
                    $save1['employment'] = strtotime($data['employment']);
                    
                    $save1['remark'] = $data['remark'];

                    
                    $save1['diploma'] = $data['diploma'];

                    $save1['tearch'] = $data['tearch'];
                    $save1['leader'] = $data['leader'];
                    //业务类弄
                    // @$save1['tags'] = CateAccessModel::where('shopid', $data['shopid'])->column('cids')[0];
                    $save1['tags'] = implode(',', $data['tags']);
                    CounsellorotModel::create($save1);
                }
                
                
                // 记录行为
                action_log('user_edit', 'admin_counsellor', $user['id'], UID, get_nickname($user['id']));
                $this->success('编辑成功', cookie('__forward__'));
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        // $info = CounsellorModel::where('id', $id)->find();
        $info = CounsellorModel::getCounsellorList($id);
        if (!$info['status']) {
            $info['status'] = 0;
        }
        $list_type = [];
        if ($info['shopid']) {
            $cids = CateAccessModel::where('shopid', $info['shopid'])->value('cids');
            $emap['status'] = 1;
            $emap['id'] = array('in',$cids);
            $list_type = CategoryModel::where($emap)->column('id,title');
        }
        //分中心
        $agency_type = AgencyModel::where(['status'=>1])->column('id,title');

        // 使用ZBuilder快速创建表单 
        return ZBuilder::make('form')
            ->setPageTitle('编辑') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'aid'],
                ['hidden', 'bid'],
                ['hidden', 'shopid'],
                ['text', 'username', '用户名', '必填，可由英文字母、数字组成'],
                ['text', 'nickname', '昵称', '可以是中文'],
                ['image', 'avar', '头像'],
                ['text', 'truename', '真实姓名', '中文'],
                ['text', 'slogan', '座右铭'],
                ['text', 'identifi', '身份证'],
                ['image', 'diploma', '咨询师证书'],
                ['radio', 'tearch', '是否是讲师', '', ['否', '是'],1],
                ['radio', 'leader', '是否是团队Leader', '', ['否', '是'],1],

                ['password', 'password', '密码', '必填，6-20位'],
                ['text', 'mobile', '手机号'],
                ['text', 'qq', 'QQ'],
                ['text', 'weixin', '微信'],
                ['text', 'alipay', '支付宝'],
                ['image', 'cerfornt', '身份证正面'],
                ['image', 'cerback', '身份证反面'],

                ['radio', 'status', '状态', '', ['停牌', '上线']],
                ['radio', 'recommond', '推荐', '', ['不推荐', '推荐']],
                // ['date', 'employment', '从业时间'],
                ['number', 'per', '单次时长'],
                ['text', 'wordchart', '文字咨询'],
                ['text', 'wordchartlv', '文字咨询会员价'],
                ['text', 'speechchart', '语音咨询'],
                ['text', 'speechchartlv', '语音咨询会员价'],
                ['text', 'videochart', '视频咨询'],
                ['text', 'videochartlv', '视频咨询会员价'],
                ['text', 'facechart', '面对面咨询'],
                ['text', 'facechartlv', '面对面咨询会员价'],
                ['textarea', 'intro', '简介'],
            ])
            ->addDatetime('employment', '从业时间', '', '', 'YYYY-MM-DD')
            ->addSelect('tags', '咨询类型', '', $list_type,'','multiple')
            ->addSelect('shopids', '挂靠分机构', '', $agency_type,'','multiple')
            ->addUeditor('remark', '祥细说明')
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

   public function point($id = null)
   {
       if ($id === null) $this->error('缺少参数');

        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        $map['memberid'] = $id;
        // 数据列表
        $data_list = PointModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();


        $list_type = CounsellorModel::where('status', 1)->column('id,username');

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('会员管理') // 设置页面标题
            ->setTableName('member_point') // 设置数据表名
            ->setSearch(['mobile' => '手机号']) // 设置搜索参数
            ->hideCheckbox()
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['behavior_type', '行为类型',['获得','消费']],
                ['behavior', '行为描述'],
                ['memberid', '会员', 'select', $list_type],
                ['point', '积分值'],
                ['create_time', '创建时间', 'datetime'],
                // ['right_button', '操作', 'btn']
            ])
            ->addTopButton('back', [
                'title' => '返回咨询师列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('counsellor/index')
            ])
            // ->addTopButtons('delete') // 批量添加顶部按钮
            // ->addRightButtons('delete') // 批量添加右侧按钮
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
   }
   /**
    * [income 收入列表]
    * @param  [type] $id [description]
    * @return [type]     [description]
    */
   public function income($id = null)
   {
       if ($id === null) $this->error('缺少参数');

        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        $map['memberid'] = $id;
        // 数据列表
        $data_list = PointModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();


        $list_type = CounsellorModel::where('status', 1)->column('id,username');

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('收入管理') // 设置页面标题
            ->setTableName('member_point') // 设置数据表名
            ->setSearch(['mobile' => '手机号']) // 设置搜索参数
            ->hideCheckbox()
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['behavior_type', '行为类型',['获得','消费']],
                ['behavior', '行为描述'],
                ['memberid', '会员', 'select', $list_type],
                ['point', '积分值'],
                ['create_time', '创建时间', 'datetime'],
            ])
            ->addTopButton('back', [
                'title' => '返回咨询师列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('counsellor/index')
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
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
        $menu_title = CounsellorModel::where('id', 'in', $ids)->column('mobile');
        return parent::setStatus($type, ['member_'.$type, 'member', 0, UID, implode('、', $menu_title)]);
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
