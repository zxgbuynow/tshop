<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Extension as ExtensionModel;

/**
 * 首页后台控制器
 */
class Setting extends Admin
{

    /**
     * 菜单列表
     * @return mixed
     */
    public function index($group = 'tab1')
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);


        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($group=='tab1') {
                plugin_config('sms.appkey',$data['appkey']);
                plugin_config('sms.secret',$data['secret']);
            }
            if ($group=='tab2') {
                plugin_config('wechat.CORP_ID',$data['CORP_ID']);
                plugin_config('wechat.APP_ID',$data['APP_ID']);
                plugin_config('wechat.APP_SECRET',$data['APP_SECRET']);
                plugin_config('wechat.wechat',$data['wechat']);
            }
            if ($group=='tab3') {
                plugin_config('wechat.serv_url',$data['serv_url']);
                plugin_config('wechat.will_contact_custom_notice',$data['will_contact_custom_notice']);
                plugin_config('wechat.pass_second_contact_custom_notice',$data['pass_second_contact_custom_notice']);
                plugin_config('wechat.no_contact_custom_notice',$data['no_contact_custom_notice']);
            }
            
            $this->success('操作成功', cookie('__forward__'));
        }
        // 获取数据
        $info = [
            // 'msg_username'=>config('msg_username'),
            // 'msg_pwd'=>config('msg_pwd'),
            // 'wechat_key'=>config('wechat_key'),
            // 'APP_SECRET'=>plugin_config('APP_SECRET')
            'serv_url'=>isset(plugin_config('wechat')['serv_url'])?plugin_config('wechat')['serv_url']:'',
            'will_contact_custom_notice'=>isset(plugin_config('wechat')['will_contact_custom_notice'])?plugin_config('wechat')['will_contact_custom_notice']:'',
            'pass_second_contact_custom_notice'=>isset(plugin_config('wechat')['pass_second_contact_custom_notice'])?plugin_config('wechat')['pass_second_contact_custom_notice']:'',
            'no_contact_custom_notice'=>isset(plugin_config('wechat')['no_contact_custom_notice'])?plugin_config('wechat')['no_contact_custom_notice']:''
        ];
        $list_tab = [
            'tab1' => ['title' => '短信配置', 'url' => url('index', ['group' => 'tab1'])],
            'tab2' => ['title' => '微信配置', 'url' => url('index', ['group' => 'tab2'])],
            'tab3' => ['title' => '呼叫配置', 'url' => url('index', ['group' => 'tab3'])],
        ];

        if ($group=='tab1') {
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('短信配置') // 设置页面标题
                ->setTabNav($list_tab,  $group)
                ->addFormItems([ // 批量添加表单项
                    ['text', 'appkey', 'appkey', 'appkey',plugin_config('sms')['appkey']],
                    ['text', 'secret', 'secret', 'secret', plugin_config('sms')['secret']],
                    
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
        }
        if ($group=='tab2') {
            $map['wechat_name'] = array('neq',''); 
            $users = db('admin_user')->where($map)->column('wechat_name,nickname');
            $info['wechat'] = plugin_config('wechat')['wechat'];
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('微信配置') // 设置页面标题
                ->setTabNav($list_tab,  $group)
                ->addFormItems([ // 批量添加表单项
                    ['text', 'CORP_ID', 'CORP_ID', '', plugin_config('wechat')['CORP_ID']],
                    ['text', 'APP_ID', 'APP_ID', '', plugin_config('wechat')['APP_ID']],
                    ['text', 'APP_SECRET', 'APP_SECRET', '', plugin_config('wechat')['APP_SECRET']],
                    ['select', 'wechat', '企业微信','',$users,'','multiple'],
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
        }

        if ($group=='tab3') {
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('呼叫配置') // 设置页面标题
                ->setTabNav($list_tab,  $group)
                ->addFormItems([ // 批量添加表单项
                    ['text', 'serv_url', 'serv_url'],
                    ['text', 'will_contact_custom_notice', '新任务未联系客户提醒'],
                    ['text', 'pass_second_contact_custom_notice', '新任务未联系客户提醒'],
                    ['text', 'no_contact_custom_notice', '新任务未联系客户提醒'],
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
        }

        if ($group=='tab4') {
            $map['wechat_name'] = array('neq',''); 
            $users = db('admin_user')->where($map)->column('wechat_name,nickname');
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('报表微信推送配置') // 设置页面标题
                ->setTabNav($list_tab,  $group)
                ->addFormItems([ // 批量添加表单项
                    ['select', 'timeLength_statistics', '员工时长统计','',$users,'','multiple'],
                    ['select', 'classareport_statistics', 'A类客户1周平均成本统计','',$users,'','multiple'],
                    ['select', 'classareport_m_statistics', 'A类客户1月平均成本统计','',$users,'','multiple'],
                    ['select', 'classnreport_statistics', '单条客户平均成本','',$users,'','multiple'],
                    ['select', 'classfreport_statistics', '当月签约客户平均成本','',$users,'','multiple'],
                    ['select', 'classf15report_statistics', '15天签约数量统计','',$users,'','multiple'],
                    ['select', 'previousfeereport_statistics', '往年同期成本分析','',$users,'','multiple'],
                    ['select', 'roleCall_statistics', '部门新数据通话汇总','',$users,'','multiple'],
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
        }
    
        
    }

    /**
     * 分机配置
     * @return [type] [description]
     */
    public function extension()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = ExtensionModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

        $btn_access = [
            'title' => '添加分机',
            'icon'  => 'fa fa-fw fa-navicon ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('add')
        ];

        $catList = db('call_custom_cat')->where(['status'=>1])->column('id,title');
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['name', '分机代码'],
                ['status', '状态', 'switch'],
            ])
            ->addTopButton('custom', $btn_access,true)
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * [add 添加]
     */
    public function add()
    {
         // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            if ($props = ExtensionModel::create($data)) {
                $this->success('新增成功', null,'_parent_reload');
            } else {
                $this->error('新增失败',null,'_parent_reload');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'title', '分机代码'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1]
            ])
            ->fetch();
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
        print_r(input('post'));exit;
        return parent::quickEdit(['call_auth_edit', 'call', 0, UID, $id]);
    }
}