<?php


namespace app\cms\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\cms\model\Counsellor as CounsellorModel;
use app\cms\model\Trade as TradeModel;
use app\cms\model\Agency as AgencyModel;
use util\Tree;
use think\Db;
use think\Hook;

/**
 * 课程活动订单默认控制器
 * @package app\member\admin
 */
class Clactrade extends Admin
{
    /**
     * 咨询师首页
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        $map['paytype'] = array('in','2,3');
        // 数据列表
        $data_list = TradeModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

        //机构列表
        $agency_list = AgencyModel::where('status', 1)->column('id,title');
        //用户列表
        $counsellor_list =  CounsellorModel::where('status', 1)->column('id,nickname');

        $btncancle = [
            // 'class' => 'btn btn-info',
            'title' => '取消',
            'icon'  => 'fa fa-fw fa-times-circle',
            'href'  => url('cancle', ['id' => '__id__'])
        ];

        $btnfrzee = [
            // 'class' => 'btn btn-info',
            'title' => '冰结',
            'icon'  => 'fa fa-fw fa-snowflake-o',
            'href'  => url('frzee', ['id' => '__id__'])
        ];

        $btnlook = [
            // 'class' => 'btn btn-info',
            'title' => '查看',
            'icon'  => 'fa fa-fw fa-search',
            'href'  => url('look', ['id' => '__id__'])
        ];
        $btncalendar = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('clacexport')
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('课程活动订单管理') // 设置页面标题
            ->setTableName('trade') // 设置数据表名
            ->setSearch(['id' => '订单编号']) // 设置搜索参数
            ->hideCheckbox()
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['title', '交易标题'],
                ['payment', '支付金额'],
                ['shopid', '机构', 'select', $agency_list],
                ['memberid', '用户', 'select', $counsellor_list],
                ['mid', '咨询师', 'select', $counsellor_list],
                ['created_time', '创建时间', 'datetime'],
                ['status', '状态', 'text', '', ['待支付', '已支付', '取消', '冻结']],
                ['right_button', '操作', 'btn']
                
            ])
            ->addTopButton('custom', $btncalendar)
            // ->addTopButtons('delete') // 批量添加顶部按钮
            // ->addRightButtons('cancle,frzee') // 批量添加右侧按钮
            ->addRightButton('custom', $btncancle) // 添加右侧按钮
            ->addRightButton('custom', $btnfrzee) // 添加右侧按钮
            // ->addRightButton('custom', $btnlook) // 添加右侧按钮
            ->replaceRightButton(['status' => ['>', 1]], '', ['custom'])
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     * [tradexport 导出]
     * @return [type] [description]
     */
    public function clacexport()
    {
        
        //查询数据
        $map['paytype'] = array('in','2,3');
        $data = TradeModel::where($map)->select();
        $pay_type = ['alipay'=>'支付宝', 'wxpayApp'=>'微信支付',''=>'其他'];
        $status =  ['0'=>'待支付', '1'=>'已支付', '2'=>'取消', '3'=>'冻结'];
        foreach ($data as $key => $value) {
            $data[$key]['shopid'] = db('shop_agency')->where(['id'=>$value['shopid']])->value('title');
            $data[$key]['memberid'] = db('member')->where(['id'=>$value['memberid']])->value('nickname');
            $data[$key]['mid'] = db('member')->where(['id'=>$value['mid']])->value('nickname');
            $data[$key]['pay_type'] = $pay_type[$value['pay_type']];
            $data[$key]['status'] = $status[$value['status']];
            
        }
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['id','auto', 'ID'],
            ['title','auto', '交易标题'],
            ['payment','auto', '支付金额'],
            ['pay_type','auto', '支付方式'],
            ['shopid', 'auto','机构'],
            ['memberid','auto', '用户'],
            ['mid','auto', '咨询师'],
            ['created_time','auto', '创建时间', 'datetime'],
            ['status','auto', '状态']
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['课程活动订单表', $cellName, $data]);
    }

    public function look($id = null)
    {
        
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
            // 验证
            $result = $this->validate($data, 'User');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);

            if ($user = UserModel::create($data)) {
                Hook::listen('user_add', $user);
                // 记录行为
                action_shop_log('user_add', 'shop_user', $user['id'], UID);
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('新增') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['text', 'username', '用户名', '必填，可由英文字母、数字组成'],
                ['text', 'nickname', '昵称', '可以是中文'],
                ['select', 'role', '角色', '', RoleModel::getTree(null, false)],
                ['text', 'email', '邮箱', ''],
                ['password', 'password', '密码', '必填，6-20位'],
                ['text', 'mobile', '手机号'],
                ['image', 'avatar', '头像'],
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

            // 禁止修改分中心超级管理员的角色和状态
            if ($data['id'] == 1 && $data['role'] != 1) {
                $this->error('禁止修改分中心超级管理员角色');
            }

            // 禁止修改分中心超级管理员的状态
            if ($data['id'] == 1 && $data['status'] != 1) {
                $this->error('禁止修改分中心超级管理员状态');
            }

            // 验证
            $result = $this->validate($data, 'User.update');
            // 验证失败 输出错误信息
            if(true !== $result) $this->error($result);

            // 如果没有填写密码，则不更新密码
            if ($data['password'] == '') {
                unset($data['password']);
            }

            if (UserModel::update($data)) {
                $user = UserModel::get($data['id']);
                Hook::listen('user_edit', $user);
                // 记录行为
                action_shop_log('user_edit', 'shop_user', $user['id'], UID, get_shop_nickname($user['id']));
                $this->success('编辑成功', cookie('__forward__'));
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = UserModel::where('id', $id)->field('password', true)->find();

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑') // 设置页面标题
            ->addFormItems([ // 批量添加表单项
                ['hidden', 'id'],
                ['static', 'username', '用户名', '不可更改'],
                ['text', 'nickname', '昵称', '可以是中文'],
                ['select', 'role', '角色', '', RoleModel::getTree(null, false)],
                ['text', 'email', '邮箱', ''],
                ['password', 'password', '密码', '必填，6-20位'],
                ['text', 'mobile', '手机号'],
                ['image', 'avatar', '头像'],
                ['radio', 'status', '状态', '', ['禁用', '启用']]
            ])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    /**
     * [cancle description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function cancle($id = null)
    {
        if (!$id) {
            $this->error('操作失败');
        }
        if (db('trade')->where(['id'=>$id])->update(['status'=>2])) {
            $this->success('取消成功', url('index'));
        }else{
            $this->error('操作失败');
        }
    }

    /**
     * [frzee description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function frzee($id = null)
    {
        if (!$id) {
            $this->error('操作失败');
        }
        if (db('trade')->where(['id'=>$id])->update(['status'=>3])) {
            $this->success('冻结成功', url('index'));
        }else{
            $this->error('操作失败');
        }
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
        $menu_title = MemberModel::where('id', 'in', $ids)->column('mobile');
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
        $config  = UserModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $config . ')，新值：(' . $value . ')';
        return parent::quickEdit(['user_edit', 'shop_user', $id, UID, $details]);
    }
}
