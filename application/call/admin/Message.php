<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Message as MessageModel;
use app\call\model\MessageLg as MessageLgModel;
use app\call\model\MsgLg as MsgLgModel;
use app\user\model\Role as RoleModel;
use app\user\model\User as UserModel;
/**
 * 品牌后台控制器
 */
class Message extends Admin
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

        $map['oper_id'] = UID;
        $m['user_id'] = UID;
        // 数据列表
        $data_list = MessageModel::where($map)->whereOr($m)->order('id desc')->paginate()->each(function($item, $key) use ($map){
            $m['user_id'] = $item['user_id'];
            $m['is_read'] = 0;
            $m['message_id'] = $item['id'];
            $item->is_read = db('call_message_log')->where($m)->count();
        });
        // 分页数据
        $page = $data_list->render();

        $btn_access = [
            'title' => '详情',
            'icon'  => 'fa fa-fw fa-navicon ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('detail',['id'=>'__id__'])
        ];

        $btn_ls = [
            'title' => '消息列表',
            'icon'  => 'fa fa-fw fa-envelope-o ',
            'href' => url('ls',['id'=>'__id__'])
        ];

        $btn_msg = [
            'title' => '回复',
            'icon'  => 'fa fa-fw fa-comment',
            'class' => 'btn btn-xs btn-default ajax-get',
            'href' => url('relpy',['id'=>'__id__'])
        ];

        //btn 隐藏 新消息

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '标题'],
                ['content', '内容'],
                ['is_read', '新消息','yesno'],
                // ['status', '发布', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('add')])
            ->addRightButton('edit')
            ->addRightButton('delete', ['data-tips' => '删除后无法恢复。'])// 批量添加右侧按钮
            ->replaceRightButton(['oper_id' => ['neq',UID]], '', 'delete,edit')
            ->addRightButton('custom1', $btn_ls)
            ->replaceRightButton(['oper_id' => ['neq',UID]], '', 'custom1')
            // ->addRightButton('custom', $btn_msg,true)
            // ->replaceRightButton(['oper_id' => ['eq',UID]], '', 'custom')
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * [ls description]
     * @param  string $id [description]
     * @return [type]     [description]
     */
    public function ls($id='')
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        if ($id === null) $this->error('缺少参数');

        // 获取查询条件
        $map = $this->getMap();

        $map['message_id'] = $id;

        $map['user_id'] = UID;

        // 数据列表
        $data_list = MessageLgModel::where($map)->order('id desc')->paginate()->each(function($item, $key) use ($map){
                    $item->is_read = $item['is_read']?'已读':'未读';
                });

        // 分页数据
        $page = $data_list->render();

        $btn_access = [
            'title' => '详情',
            'icon'  => 'fa fa-fw fa-eye',
            'class' => 'btn  btn-xs btn-default ajax-get',
            'href' => url('detail',['id'=>'__id__'])
        ];
        
        $btn_msg = [
            'title' => '回复',
            'icon'  => 'fa fa-fw fa-comment',
            'class' => 'btn btn-xs btn-default ajax-get',
            'href' => url('relpy',['id'=>'__id__'])
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                // ['title', '标题'],
                ['content', '内容'],
                ['is_read', '是否已读'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButton('custom1', $btn_access,true)

            ->addRightButton('custom', $btn_msg,true)
            ->replaceRightButton(['oper_id' => ['eq',UID]], '', 'custom')
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }
    /**
     * [relpy 回复]
     * @param  string $id [description]
     * @return [type]     [description]
     */
    public function relpy($id ='')
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $content['content'] = $data['title'];
            if (!$content['content']) {
                $this->error('内容不对',null,'_close_pop');
            }
            
            //生成日志
            $s['content'] = $data['title'];
            $s['message_id'] = $id;
            $s['send_user'] = UID;
            $s['user_id'] = db('call_message_log')->where(['id'=>$id])->value('send_user');
            db('call_message_log')->insert($s);

            $this->success('发送成功',null,'_close_pop');
            
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'title', '内容'],
            ])
            ->setBtnTitle('submit', '发送')
            ->setFormData()
            ->fetch();
    }
    /**
     * [log 消息日志]
     * @return [type] [description]
     */
    public function log()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        $map['user_id'] = UID;
        // 数据列表
        $data_list = MessageLgModel::where($map)->order('id desc')->paginate()->each(function($item, $key) use ($map){
                    $item->content = MessageModel::where(['id'=>$item['message_id']])->value('content');
                    $item->title = MessageModel::where(['id'=>$item['message_id']])->value('title');
                    $item->is_read = $item['is_read']?'已读':'未读';
                });

        // 分页数据
        $page = $data_list->render();

        $btn_access = [
            'title' => '详情',
            'icon'  => 'fa fa-fw fa-navicon ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('detail',['id'=>'__id__'])
        ];
      
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '标题'],
                ['content', '内容'],
                ['is_read', '是否已读'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButton('custom', $btn_access,true)
            ->setRowList($data_list)// 设置表格数据
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
            $data['oper_id'] = UID;
            if ($props = MessageModel::create($data)) {
            // if (1==1) {
                //日志
                $insert_id = $props->id;
                $save['message_id'] = $insert_id;
                // $save['message_id'] = 999;


                if ($data['touser_type']==0) {
                    if (!$data['user_id']) {
                        $this->error('选择用户');
                    }
                    $save['user_id'] = $data['user_id'];
                    $save['send_user'] = UID;
                    $save['content'] = $data['content'];
                    MessageLgModel::create($save);
                }
                if ($data['touser_type']==1) {
                    if (!$data['role']) {
                        $this->error('选择组');
                    }
                    $map1['role']  = $data['role'];
                    $users = db('admin_user')->where($map1)->column('id');
                    foreach ($users as $key => $value) {
                        $save['user_id'] = $value;
                        $save['send_user'] = UID;
                        $save['content'] = $data['content'];
                    }
                    MessageLgModel::saveAll($save);
                }
                if ($data['touser_type']==2) {
                    $map2['id'] = array('gt',1);
                    $users = db('admin_user')->where($map2)->column('id');
                    foreach ($users as $key => $value) {
                        $save['user_id'] = $value;
                        $save['send_user'] = UID;
                        $save['content'] = $data['content'];
                    }
                    MessageLgModel::saveAll($save);
                }
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        $user =  UserModel::where(['status'=>1])->column('id,username');
        $roles =  RoleModel::where(['status'=>1])->column('id,name');
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '标题'],
                ['textarea', 'content', '内容'],
                ['radio', 'touser_type', '对象', '', ['个人', '组','全员'], 2],
                ['select', 'user_id', '选择员工', '', $user],
                ['select', 'role', '选择组', '', $roles],
            ])
            ->setTrigger('touser_type', 0, 'user_id')
            ->setTrigger('touser_type', 1, 'role')
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
            if (MessageModel::update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['textarea', 'content', '内容'],
                // ['radio', 'touser_type', '对象', '', ['个人', '组','全员'], 2],
                // ['select', 'user_id', '选择员工', '', $user],
                // ['select', 'role', '选择组', '', $roles],
            ])
            // ->setTrigger('touser_type', 0, 'user_id')
            // ->setTrigger('touser_type', 1, 'role')
            ->setFormData(MessageModel::get($id))
            ->fetch();
    }

    /**
     * [detail 详情]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function detail($id = null)
    {
        if ($id === null) $this->error('缺少参数');
        $info = MessageLgModel::get($id);
        $info['content'] = MessageModel::where(['id'=>$info['message_id']])->value('content');
        $info['title'] = MessageModel::where(['id'=>$info['message_id']])->value('title');

        //更新已读
        
        db('call_message_log')->where(['message_id'=>$info['message_id'],'user_id'=>UID])->update(['is_read'=>1]);
        // 显示添加页面
        return ZBuilder::make('form')

            ->addFormItems([
                ['hidden', 'id'],
                ['static', 'title', '标题'],
                ['static', 'content', '内容'],
            ])
            ->hideBtn('submit')
            ->setFormData($info)
            ->fetch();
    }

    /**
     * [log 短信]
     * @return [type] [description]
     */
    public function msg()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        $map['user_id'] = UID;
        // 数据列表
        $data_list = MsgLgModel::where($map)->order('id desc')->paginate()->each(function($item, $key) use ($map){
                    $item->custom_id = db('call_custom')->where(['id'=>$item['custom_id']])->value('name');
                });;

        // 分页数据
        $page = $data_list->render();

      
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['content', '短信内容'],
                ['custom_id', '接收人'],
                // ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
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
        $menu_title = CouponModel::where('id', 'in', $ids)->column('title');
        return parent::setStatus($type, ['coupon_'.$type, 'class', 0, UID, implode('、', $menu_title)]);
    }

}