<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\user\model\Role as RoleModel;

/**
 * 首页后台控制器
 */
class Home extends Admin
{

    /**
     * 菜单列表
     * @return mixed
     */
    public function index()
    {
        
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('form')->fetch('index2'); // 渲染模板
        
    }

    
}