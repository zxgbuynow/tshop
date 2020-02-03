<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Payment as PaymentModel;
use app\call\model\Alloclg as AlloclgModel;

/**
 * 首页后台控制器
 */
class Task extends Admin
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
        // 数据列表
        $data_list = AlloclgModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();
    
        $btn_access = [
            'title' => '呼叫',
            'icon'  => 'fa fa-fw fa-whatsapp',
            'href' => url('call',['id'=>'__id__'])
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            // ->setSearch(['domain' => '域名','custom'=>'客户'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                // ['user', '员工'],
                ['custom', '客户'],
                // ['call_count', '呼叫次数'],
                ['alloc_count', '分配次数'],
                ['create_time', '创建时间','datetime'],
                ['right_button', '操作', 'btn']
            ])
            // ->addTopButton('add', ['href' => url('add')])
            ->addRightButton('custom',$btn_access)
            ->setRowList($data_list)// 设置表格数据
            ->raw('custom') // 使用原值
            ->fetch(); // 渲染模板
        
    }

    /**
     * [call description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function call($id = null)
    {
      $this->success('呼叫成功', url('index'));
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