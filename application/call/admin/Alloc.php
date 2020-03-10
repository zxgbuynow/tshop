<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Alloc as AllocModel;
use app\call\model\Alloclg as AlloclgModel;
use app\call\model\Custom as CustomModel;
use app\user\model\User as UserModel;
/**
 * 首页后台控制器
 */
class Alloc extends Admin
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
        $data_list = AllocModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();
        $btn_access = [
            'title' => '分配日志',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('alloclg', ['id' => '__id__'])
        ];
// `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分配id',
//   `op_id` int(10) unsigned DEFAULT '0' COMMENT '操作员id',
//   `call_count` int(10) unsigned DEFAULT '0' COMMENT '呼叫次数',
//   `alloc_count` int(10) unsigned DEFAULT '0' COMMENT '分配次数',
//   `create_time` int(10) unsigned DEFAULT NULL,
//   `status` tinyint(1) DEFAULT '1' COMMENT '0失效',
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            // ->setSearch(['domain' => '域名','custom'=>'客户'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['oper', '操作员'],
                // ['call_count', '呼叫次数'],
                // ['alloc_count', '分配次数'],
                ['create_time', '创建时间','datetime'],
                ['way', '分配方式',['','平均分配','选配']],
                // ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('add', ['href' => url('add')])

            ->addRightButton('custom',$btn_access)
            ->setRowList($data_list)// 设置表格数据
            ->raw('oper') // 使用原值
            ->fetch(); // 渲染模板
        
    }

    /**
     * [alloclg 分配日志]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function alloclg($id =null)
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        if ($id === null) $this->error('缺少参数');


        // 获取查询条件
        $map = $this->getMap();

        $map['alloc_id'] = $id;
        // 数据列表
        $data_list = AlloclgModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();
 
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->addTopButton('back', [
                'title' => '返回列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('index')
            ])
            // ->setSearch(['domain' => '域名','custom'=>'客户'])// 设置搜索框
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['user', '员工'],
                ['custom', '客户'],
                // ['call_count', '呼叫次数'],
                // ['alloc_count', '分配次数'],
                ['create_time', '创建时间','datetime'],
                // ['right_button', '操作', 'btn']
            ])
            // ->addTopButton('add', ['href' => url('add')])
            // ->addRightButton('delete')
            ->setRowList($data_list)// 设置表格数据
            ->raw('user,custom') // 使用原值
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
            $sdata['create_time'] =  time();
            //UID
            $sdata['op_id'] = UID;
            $sdata['way'] = $data['way']==0?1:2;
            $sdata['call_count'] = $data['call_count'];
            // if ($sdata['way']==1) {
            //         //平均分处理
            //     $userCts = count($data['user_ids']);
            //     $custCts = count($data['custom_ids']);
            //     $average = ceil($custCts/$userCts);
            //     $custCtarr = array_chunk($data['custom_ids'], $average);
            //     // $s = [];
            //     $r = [];
            //     foreach ($custCtarr as $key => $value) {
            //         // $s[$key]['user_id'] = $data['user_ids'][$key];
            //         // $s[$key]['custom_id'] = $value;
            //         $cc = count($value);
            //         for ($i=0; $i < $cc; $i++) { 
            //             $rs['custom_id'] = $value[$i];
            //             $rs['user_id'] = $data['user_ids'][$key];
            //             $rs['alloc_id'] = 1;
            //             $rs['call_count'] = $data['call_count'];
            //             $rs['create_time'] = time();
            //             array_push($r,$rs);
            //         }
            //     }
                
                
            // }
            // if ($sdata['way']==2) {
            //     $custCts = count($data['custom_id']);
            //     $r = [];
            //     for ($i=0; $i < $custCts; $i++) { 
            //         $r[$i]['custom_id'] = $data['custom_id'][$i];
            //         $r[$i]['user_id'] = $data['user_id'];
            //         $r[$i]['alloc_id'] = 1;
            //         $r[$i]['call_count'] = $data['call_count'];
            //         $r[$i]['create_time'] = time();
            //     } 
            // }

            // print_r($r);exit;
            if ($props = AllocModel::create($sdata)) {
                $insert_id = $props->id;
                //分配处理
                
                if ($sdata['way']==1) {
                    //平均分处理
                    $userCts = count($data['user_ids']);
                    $custCts = count($data['custom_ids']);
                    $average = ceil($custCts/$userCts);
                    $custCtarr = array_chunk($data['custom_ids'], $average);
                    // $s = [];
                    $r = [];
                    foreach ($custCtarr as $key => $value) {
                        // $s[$key]['user_id'] = $data['user_ids'][$key];
                        // $s[$key]['custom_id'] = $value;
                        $cc = count($value);
                        for ($i=0; $i < $cc; $i++) { 
                            $rs['custom_id'] = $value[$i];
                            $rs['user_id'] = $data['user_ids'][$key];
                            $rs['alloc_id'] = $insert_id;
                            // $rs['call_count'] = $data['call_count'];
                            $rs['create_time'] = time();
                            array_push($r,$rs);
                        }
                    }
                    
                    
                }
                if ($sdata['way']==2) {
                    $custCts = count($data['custom_id']);
                    $r = [];
                    for ($i=0; $i < $custCts; $i++) { 
                        $r[$i]['custom_id'] = $data['custom_id'][$i];
                        $r[$i]['user_id'] = $data['user_id'];
                        $r[$i]['alloc_id'] = $insert_id;
                        // $r[$i]['call_count'] = $data['call_count'];
                        $r[$i]['create_time'] = time();
                    } 
                }
                $Alloclg = new AlloclgModel;
                $Alloclg->saveAll($r);

                if ($sdata['way']==1) {
                    $mp['id'] = array('in',$data['custom_ids']);
                    CustomModel::where($mp)->update(['status'=>2]);
                }
                if ($sdata['way']==2) {
                     $mp['id'] = array('in',$data['custom_id']);
                    CustomModel::where($mp)->update(['status'=>2]);
                }
               
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        $custom =  CustomModel::where(['status'=>1])->column('id,name');
        $map['id'] = array('>',1);
        $user =  UserModel::where($map)->column('id,username');

        $tips = db('call_custom')->where(['status'=>1])->count();
        // $columns = [
        //     'id'=>'ID',
        //     'name'=>'客户名称',
        //     'tel'=>'客户电话',
        //     'mobile'=>'客户手机'
        // ];
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                // ['text', 'call_count', '呼叫次数'],
                ['radio', 'way', '分配方式' ,'', ['平均分配', '选配'], 0],
                // ['number', 'alloc_count', '设置分配数量'],
                // ['select', 'custom_ids', '选择客户数据', '<code>可多选</code>', $custom,'','multiple'],
                ['number', 'custom_ids', '输入客户数量','<code>当前任务总数'.$tips.'；务必不要大于该值</code>'],
                ['select', 'user_ids', '选择员工', '<code>可多选</code>', $user,'','multiple'],

                ['select', 'user_id', '选择员工', '', $user],
                ['select', 'custom_id', '选择客户数据', '<code>可多选</code>', $custom,'','multiple'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],

                // ['selectTable', 'test', '测试客户', '', $columns, [], url('Custom/index')],
            ])

            ->setTrigger('way', 1, 'custom_id,user_id')
            ->setTrigger('way', 0, 'custom_ids,user_ids')
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

            $data['start_time'] =strtotime($data['start_time']);
            $data['end_time'] =  strtotime($data['end_time']);
            if (AuthModel::update($data)) {
                $this->success('编辑成功', url('index'));
            } else {
                $this->error('编辑失败');
            }
        }
        
        $custom =  CustomModel::where(['status'=>1])->column('id,name');
        $map['id'] = array('>',1);
        $user =  UserModel::where($map)->column('id,username');

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'call_count', '呼叫次数'],
                ['radio', 'way', '分配方式' ,'', ['平均分配', '选配'], 0],
                // ['number', 'alloc_count', '设置分配数量'],
                ['select', 'custom_ids', '选择客户数据', '<code>可多选</code>', $custom,'','multiple'],
                ['select', 'user_ids', '选择员工', '<code>可多选</code>', $user,'','multiple'],

                ['select', 'user_id', '选择员工', '', $custom],
                ['select', 'custom_id', '选择客户数据', '<code>可多选</code>', $custom,'','multiple'],
                ['radio', 'status', '立即启用', '', ['否', '是'], 1],

            ])
            ->setTrigger('way', 1, 'custom_id,user_id')
            ->setTrigger('way', 0, 'custom_ids,user_ids')
            ->setFormData(AllocModel::get($id))
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