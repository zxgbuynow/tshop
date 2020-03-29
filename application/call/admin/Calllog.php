<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Calllog as CalllogModel;

/**
 * 首页后台控制器
 */
class Calllog extends Admin
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

        $map['user_id'] = UID;
        if (UID==1) {
            unset($map['user_id']);
        }
        //判断是否为主管
        if ($userin =  db('admin_user')->where(['id'=>UID,'is_maner'=>1 ])->find()) {
            $userids = db('admin_user')->where(['role'=>$userin['role'] ])->column('id');
            $map['user_id'] = array('in',$userids);
        }
        $map['status'] = 1;
        // 数据列表
        $data_list = CalllogModel::where($map)->order('id desc')->paginate()->each(function($item, $key) use ($map){
            $item->calledNum = UID==1?$item['calledNum']:replaceTel($item['calledNum']);
        });

        // 分页数据
        $page = $data_list->render();
    
        $btn_down = [
            // 'class' => 'btn btn-info',
            'title' => '下载录音',
            'icon'  => 'fa fa-fw fa-pinterest-p',
            'href'  => url('downcord',['id'=>'__id__'])
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['calledNum' => '被叫号码','callerNum'=>'主叫号码'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['user', '员工'],
                // ['followId', '跟进ID'],
                ['callType', '呼叫类型',['','已接来电','已拨电话','未接来电','未接去电']],
                ['callerNum', '主叫号码'],
                ['calledNum', '被叫号码'],
                ['startTime', '开始通话时间'],
                ['timeLength', '通话时长'],
                ['code', '通话唯一标识'],
                ['recordURL', '录音地址'],
                ['extension', '分机号'],
                // ['ownerAcc', '所有者帐号'],
                // ['communicationNO', '通信号码'],
                ['create_time', '创建时间','datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->hideCheckbox()
            ->setRowList($data_list)// 设置表格数据
            ->addRightButton('custom',$btn_down)
            ->replaceRightButton(['recordURL' => ['eq','']], '<button class="btn btn-danger btn-xs" type="button" disabled>不可操作</button>') // 修改id为1的按钮
            ->raw('user') // 使用原值
            ->fetch(); // 渲染模板
        
    }

    
    /**
     * [downCord 下载]
     * @param  string $id [description]
     * @return [type]     [description]
     */
    public function downcord($id ='')
    {
        if ($id === null) $this->error('缺少参数');

        //transactionId
        $params['transactionId'] = db('call_log')->where(['id'=>$id])->value('transactionId');
        if (!$params['transactionId']) {
            $this->error('transactionId缺失');
        }
        $status = ring_up_new('downloadFile',$params);
        //弹框
        $ret = json_decode($status,true);
        if ($ret['status']==0) {
            $this->error($ret['msg'], null, '_close_pop');
        }
        if ($ret['status']==true&&!isset($ret['data'])) {
            $this->error($ret['msg'], null, '_close_pop');
        }
        
        // 显示添加页面
        return ZBuilder::make('form')
            ->fetch('hangup');
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