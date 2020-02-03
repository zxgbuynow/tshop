<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Ondate as OndateModel;
use app\call\model\Custom as CustomModel;
/**
 * 首页后台控制器
 */
class Ondate extends Admin
{

    /**
     * 菜单列表
     * @return mixed
     */
    public function index($group = 'yes')
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);


        $list_tab = [
            'yes' => ['title' => '待回访', 'url' => url('index', ['group' => 'yes'])],
            'no' => ['title' => '已回访', 'url' => url('index', ['group' => 'no'])],
        ];

        // 获取查询条件
        $map = $this->getMap();
        $map['status'] = 1;
        if ($group=='yes') {
            $map['status'] = 0;
        }
        // 数据列表
        $data_list = OndateModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

  //       `custom_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  // `ondate` int(10) unsigned DEFAULT NULL,
  // `sign_time` int(10) unsigned DEFAULT NULL,
  // `create_time` int(10) unsigned DEFAULT NULL,
  // `status` tinyint(1) DEFAULT '0' COMMENT '0待回1已回',

        return ZBuilder::make('table')
            ->setTabNav($list_tab,  $group)
            ->setSearch(['custom'=>'客户'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['custom', '客户'],
                ['ondate', '预约时间'],
                ['sign_time', '录入时间','datetime'],
                ['create_time', '创建时间','datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('add')])
            ->addRightButton('edit')
            ->setRowList($data_list)// 设置表格数据
            ->raw('custom') // 使用原值
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
            $data['sign_time'] =strtotime($data['sign_time']);
            $data['ondate'] =  strtotime($data['ondate']);
            $data['create_time'] = time();
            $result = $this->validate($data, 'Ondate');
            if(true !== $result) $this->error($result);

            if ($props = OndateModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }
        $userList = CustomModel::where(['status'=>1])->column('id,name');
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['select', 'custom_id', '客户','',$userList],
                ['datetime', 'sign_time', '录入时间'],
                ['datetime', 'ondate', '预约时间'],
                ['radio', 'status', '立即启用', '', ['待回访', '已回访'], 1],
            ])
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
            $data['sign_time'] =strtotime($data['sign_time']);
            $data['ondate'] =  strtotime($data['ondate']);

            $result = $this->validate($data, 'Ondate');
            if(true !== $result) $this->error($result);

            if (OndateModel::update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        $userList = CustomModel::where(['status'=>1])->column('id,name');

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['select', 'custom_id', '客户','',$userList],
                ['datetime', 'sign_time', '录入时间'],
                ['datetime', 'ondate', '预约时间'],
                ['radio', 'status', '立即启用', '', ['待回访', '已回访'], 1],
            ])
            ->setFormData(OndateModel::get($id))
            ->fetch();
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