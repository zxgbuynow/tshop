<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Notice as NoticeModel;
use app\call\model\NoticeLg as NoticeLgModel;

/**
 * 首页后台控制器
 */
class Notice extends Admin
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
        $data_list = NoticeModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();
    

  //       `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '标题',
  // `content` longtext COLLATE utf8_unicode_ci COMMENT '内容',
  // `way` tinyint(1) DEFAULT '0' COMMENT '0系统1企业微信',
  // `model` tinyint(1) DEFAULT '0' COMMENT '0事件触发1定时触发',
  // `create_time` int(10) unsigned DEFAULT NULL,
  // `status` tinyint(1) DEFAULT '1' COMMENT '0删除1正常',

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            // ->setSearch(['domain' => '域名','custom'=>'客户'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '标题'],
                ['way', '提醒方式',['系统','企业微信']],
                ['model', '提醒方法',['事件触发','定时触发']],
                ['is_admin', '推送对象',['其他人员', '管理员']],
                // ['content', '内容','popover'],
                ['create_time', '创建时间','datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('add')])
            ->addRightButton('edit')
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
        
    }

    /**
     * [add description]
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $data['create_time'] =  time();
            $result = $this->validate($data, 'Notice');
            if(true !== $result) $this->error($result);
            if ($props = NoticeModel::create($data)) {
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([

                ['text', 'title', '标题'],
                ['radio', 'way', '提醒方式', '', ['系统', '企业微信'], 1],
                ['radio', 'model', '提醒方法', '', ['事件触发', '定时触发'], 1],
                ['radio', 'is_admin', '是否管理员', '', ['否', '是'], 1],

                ['textarea', 'content', '内容','<code>项目[project] 客户[custom] 员工[employ]</code>'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],
            ])
            ->addTags('tags', '标签','<code>提醒埋点</code>')
            ->fetch();
    }

    /**
     * [detail description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function detail($id = null)
    {
        if ($id === null) $this->error('缺少参数');
        $info = NoticeLgModel::get($id);
        $info['create_time'] = date('Y-m-d H:i:s',$info['create_time']);

        db('call_notice_log')->where(['id'=>$id,'user_id'=>UID])->update(['is_read'=>1]);
        // 显示添加页面
        return ZBuilder::make('form')
            ->setPageTitle('详情')
            ->addStatic('title', '名称')
            ->addStatic('content', '内容')
            ->addStatic('create_time', '提醒时间')
            ->hideBtn('submit,back')
            ->setFormData($info)
            ->fetch();
    }
    /**
     * [edit description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $result = $this->validate($data, 'Notice');
            if(true !== $result) $this->error($result);
            if (NoticeModel::update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'title', '标题'],
                ['radio', 'way', '提醒方式', '', ['系统', '企业微信'], 1],
                ['radio', 'model', '提醒方法', '', ['事件触发', '定时触发'], 1],
                ['radio', 'is_admin', '是否管理员', '', ['否', '是'], 1],
                ['textarea', 'content', '内容','<code>项目[project] 客户[custom] 员工[employ]</code>'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],

            ])
            ->addTags('tags', '标签','<code>提醒埋点</code>')
            ->setFormData(NoticeModel::get($id))
            ->fetch();
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