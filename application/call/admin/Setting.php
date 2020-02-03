<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;

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
            }
            
            $this->success('操作成功', cookie('__forward__'));
        }

        // 获取数据
        $info = [
            // 'msg_username'=>config('msg_username'),
            // 'msg_pwd'=>config('msg_pwd'),
            // 'wechat_key'=>config('wechat_key'),
            // 'APP_SECRET'=>plugin_config('APP_SECRET')
        ];
        $list_tab = [
            'tab1' => ['title' => '短信配置', 'url' => url('index', ['group' => 'tab1'])],
            'tab2' => ['title' => '微信配置', 'url' => url('index', ['group' => 'tab2'])],
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
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('微信配置') // 设置页面标题
                ->setTabNav($list_tab,  $group)
                ->addFormItems([ // 批量添加表单项
                    ['text', 'CORP_ID', 'CORP_ID', '', plugin_config('wechat')['CORP_ID']],
                    ['text', 'APP_ID', 'APP_ID', '', plugin_config('wechat')['APP_ID']],
                    ['text', 'APP_SECRET', 'APP_SECRET', '', plugin_config('wechat')['APP_SECRET']],
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
        }
    
        
    }

    
    
}