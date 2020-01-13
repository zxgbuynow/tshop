<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Project as ProjectModel;
use app\call\model\Projectls as ProjectlsModel;
use app\call\model\Projectst as ProjectstModel;

/**
 * 首页后台控制器
 */
class Project extends Admin
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

        // 数据列表
        $data_list = ProjectstModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();
// text time textarea image wangeditor 

  //       `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '项目设置id',
  // `lable` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '字段名',
  // `col` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '字段对应名',
  // `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT '字段类别',
  // `sort` int(10) unsigned DEFAULT '0' COMMENT '排序',
  // `project_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  // `create_time` int(10) unsigned DEFAULT NULL,
  // `disabled` tinyint(1) DEFAULT '1' COMMENT '0失效',

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            // ->setSearch(['domain' => '域名','custom'=>'客户'])// 设置搜索框
            ->addOrder('sort')
            ->addFilter('project')
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['lable', '字段名'],
                ['type', '字段类别'],
                ['sort', '排序'],
                ['project', '项目'],
                ['create_time', '生成时间','datetime'],
                ['disabled', '有效', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('add')])
            ->addRightButton('edit')
            ->setRowList($data_list)// 设置表格数据
            ->raw('project') // 使用原值
            ->fetch(); // 渲染模板
        
    }

    /**
     * [pjslist 项目描述]
     * @return [type] [description]
     */
    public function pjslist()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);


        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = ProjectModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();
// `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '项目id',
//   `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '项目名称',
//   `describe` longtext COLLATE utf8_unicode_ci COMMENT '项目说明',
//   `create_time` int(10) unsigned DEFAULT NULL,
//   `status` tinyint(1) DEFAULT '1' COMMENT '0失效',

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '项目名称'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '项目名称'],
                ['describe', '项目说明'],
                ['create_time', '创建时间','datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('pjsadd')])
            ->addRightButton('pjsedit')
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }

    public function pjlist($id = null)
    {
        
        if ($id === null) $this->error('缺少参数');

        cookie('__forward__', $_SERVER['REQUEST_URI']);
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = ProjectModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();
// `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '项目行记录id',
//   `project_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
//   `col1` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col1',

        //Label处理
        $iswang = 0;
        $colArr = [
            ['id', 'ID'],
            ['project', '项目名称'],
        ];

        $lableArr = ProjectstModel::where(['project_id'=>$id,'disabled'=>1])->order('col ASC')->select();
        foreach ($lableArr as $key => $value) {
            if ($value['type']=='text') {
                $tmp = [$value['col'],$value['lable']];
                array_push($colArr, $tmp);
                continue;
            }
            // if ($value['type']=='wangeditor') {
            //     // $tmp = [$value['col'],$value['lable']];
            //     // array_push($colArr, $tmp);
            //     $iswang = 1;
            //     continue;
            // }

            $tmp = [$value['col'],$value['lable'],$value['type']];

            array_push($colArr, $tmp);
        }
        
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearch(['title' => '项目名称'])// 设置搜索框
            ->addColumns($colArr)
            ->addColumns([ // 批量添加数据列
                
                ['create_time', '创建时间','datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            
            ->addTopButton('add', ['href' => url('pjadd')])
            ->addRightButton('pjedit')
            ->setRowList($data_list)// 设置表格数据
            ->raw('project') // 使用原值
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
            $data['create_time'] =  time();
            if ($props = ProjectModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        $list_type = ['text'=>'文本框','time'=>'时间','textarea'=>'多行文本','image'=>'图片','wangeditor'=>'编辑器'];
        $list_pj = ProjectModel::where(['status'=>1])->select();
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['text', 'lable', '字段名'],
                ['text', 'sort', '排序'],
                ['select', 'type', '字段类别','',$list_type],
                ['select', 'project', '项目', '', $list_pj],
                ['radio', 'disabled', '立即启用', '', ['否', '是'], 1],
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
            $data['create_time'] =  time();
            if ($props = ProjectModel::update($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        $list_type = ['text'=>'文本框','time'=>'时间','textarea'=>'多行文本','image'=>'图片','wangeditor'=>'编辑器'];
        $list_pj = ProjectModel::where(['status'=>1])->select();
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'lable', '字段名'],
                ['text', 'sort', '排序'],
                ['select', 'type', '字段类别','',$list_type],
                ['select', 'project', '项目', '', $list_pj],
                ['radio', 'disabled', '立即启用', '', ['否', '是'], 1],
            ])
            ->setFormData(AuthModel::get($id))
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