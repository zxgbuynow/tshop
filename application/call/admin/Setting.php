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
                config('msg_username',$data['msg_username']);
                config('msg_pwd',$data['msg_pwd']);
            }
            if ($group=='tab2') {
                config('wechat_key',$data['wechat_key']);
                config('wechat_Appsecret',$data['wechat_Appsecret']);
            }
            
            $this->success('操作成功', cookie('__forward__'));
        }

        // 获取数据
        $info = [
            'msg_username'=>config('msg_username'),
            'msg_pwd'=>config('msg_pwd'),
            'wechat_key'=>config('wechat_key'),
            'wechat_Appsecret'=>config('wechat_Appsecret')
        ];

        $list_tab = [
            'tab1' => ['title' => '短信配置', 'url' => url('index', ['group' => 'tab1'])],
            'tab2' => ['title' => '微信配置', 'url' => url('index', ['group' => 'tab2'])],
        ];

        if ($group=='tab1') {
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('短信配置') // 设置页面标题
                ->addFormItems([ // 批量添加表单项
                    ['text', 'msg_username', 'msg_username', '短信用户名'],
                    ['text', 'msg_pwd', 'msg_pwd', '短信用户密码'],
                    
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
        }
        if ($group=='tab2') {
            // 使用ZBuilder快速创建表单
            return ZBuilder::make('form')
                ->setPageTitle('微信配置') // 设置页面标题
                ->addFormItems([ // 批量添加表单项
                    ['text', 'wechat_key', 'wechat_key', 'Key微信'],
                    ['text', 'wechat_Appsecret', 'wechat_Appsecret', 'Appsecret微信'],
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
        }
    
        
    }

    
    
}