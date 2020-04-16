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

        if (isset($map['timeLengthst'])) {
            if (isset($map['timeLengthed'])) {
                $map['timeLength'][0]='between';
                $map['timeLength'][1][0]=$map['timeLengthst'][1];
                $map['timeLength'][1][1]=$map['timeLengthed'][1];
                unset($map['timeLengthed']);
                unset($map['timeLengthst']);
            }else{
                $map['timeLength'][0]='egt';
                $map['timeLength'][1][0]=$map['timeLengthst'][1];
                unset($map['timeLengthst']);
            }
        }else{
            if (isset($map['timeLengthed'])) {
                $map['timeLength'][0]='elt';
                $map['timeLength'][1][0]=$map['timeLengthed'][1];
                unset($map['timeLengthed']);
            }
        }

        //搜索客户
        if (isset($map['custom'])) {
            $m['name'] = $map['custom'];
            $customs = db('call_custom')->where($m)->column('id');
            $map['custom_id'] = array('in',$customs);            
            unset($map['custom']);
        }

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
            $item->timeLength = date('i:s',$item['timeLength']);
            $item->username = db('admin_user')->where(['id'=>$item['user_id']])->value('nickname');
            $item->customname = db('call_custom')->where(['id'=>$item['custom_id']])->value('name');
        });

        // 分页数据
        $page = $data_list->render();
    
        $btn_down = [
            // 'class' => 'btn btn-info',
            'title' => '播放录音',
            'icon'  => 'fa fa-fw fa-pinterest-p',
            'href'  => url('downcord',['id'=>'__id__'])
        ];
        $btnexport = [
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('export',http_build_query($this->request->param()))
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            // ->setSearch(['calledNum' => '被叫号码','callerNum'=>'主叫号码'])// 设置搜索框
            ->setSearchArea([
                ['daterange', 'startTime', '通话时间', '', '', ['format' => 'YYYY-MM-DD', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                ['text:6', 'extension', '分机号', 'like'],
                ['text:6', 'custom', '客户', 'like'],
                ['text:6', 'callerNum', '主叫号码', 'like'],
                ['text:6', 'calledNum', '被叫号码', 'like'],

                ['text:6', 'timeLengthst', '通话时长开始'],
                ['text:6', 'timeLengthed', '通话时长结束'],

            ])
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['username', '员工'],
                ['customname', '客户'],
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
            // ->addRightButton('custom',$btn_down)
            ->setRowList($data_list)// 设置表格数据
            // ->addRightButton('custom',$btn_down)
            ->addRightButton('custom',$btn_down,['title'=>'播放录音','area' => ['320px', '120px']])
            ->addTopButton('custom', $btnexport)
            ->replaceRightButton(['recordURL' => ['eq','']], '<button class="btn btn-danger btn-xs" type="button" disabled>不可操作</button>') // 修改id为1的按钮
            // ->raw('user') // 使用原值
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
        
    }

    /**
     * [export 导出]
     * @return [type] [description]
     */
    public function export()
    {
        $map = $this->getMaps();
        
        // 数据列表
        $data_list = CalllogModel::where($map)->order('id desc')->paginate();

        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['id','auto', 'ID'],
            ['id', 'auto','ID'],
            ['user', 'auto','员工'],
            ['callType', 'auto','呼叫类型',['','已接来电','已拨电话','未接来电','未接去电']],
            // ['callerNum','auto', '主叫号码'],
            // ['calledNum','auto', '被叫号码'],
            ['startTime', 'auto','开始通话时间'],
            ['timeLength','auto', '通话时长'],
            ['code','auto', '通话唯一标识'],
            ['recordURL', 'auto','录音地址'],
            ['extension','auto', '分机号'],
            ['create_time', 'auto','创建时间','datetime'],
        ];
        
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['通话记录', $cellName, $data_list]);
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
        $params['transactionId'] = db('call_log')->where(['id'=>$id])->value('code');
        if (!$params['transactionId']) {
            $this->error('transactionId缺失');
        }
        $params['file'] = db('call_log')->where(['id'=>$id])->value('recordURL');
        if (!$params['file']) {
            $this->error('file缺失');
        }

        //直接打开连接
        //http://xxx.xxx.xx.xx/http_uncall_api.php?model=downloadFile&transactionId=xxxxxxxxxxxx 
        
        // $status = ring_up_new('downloadFile',$params);
        // //弹框
        // $ret = json_decode($status,true);

        // if ($ret['status']==0) {
        //     $this->error($ret['msg'], null, '_close_pop');
        // }
        // if ($ret['status']==1&&!isset($ret['msg'])) {
        //     $this->error($ret['msg'], null, '_close_pop');
        // }
        $downUrl = "http://101.132.248.56/http_uncall_api.php?model=downloadFile&transactionId=".$params['transactionId'];
        $data['downUrl'] = $downUrl;
//         $js = <<<EOF
//             <script type="text/javascript">
               
//                 $(function(){
//                     console.log(111)
//                     // window.open({$downUrl})
//                 });
//             </script>
// EOF;
        // 显示添加页面
        return ZBuilder::make('form')
            // ->setExtraJs($js)
            ->assign('downUrl',$downUrl)
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