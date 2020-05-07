<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Reportcat as ReportcatModel;
use app\call\model\Calllog as CalllogModel;
use app\call\model\Custom as CustomModel;
use app\user\model\User as UserModel;
use app\call\model\Trade as TradeModel;
use app\call\model\Order as OrderModel;
use app\call\model\Tradelog as TradelogModel;
use app\call\model\CustomEXLog as CustomEXLogModel;
use app\user\model\Role as RoleModel;//CustomEXLog
use think\Db;

/**
 * 首页后台控制器
 */
class Report extends Admin
{

    /**
     * 客户分类报表
     * @return mixed
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = ReportcatModel::where($map)->order('id desc')->paginate();

        // 分页数据
        $page = $data_list->render();

        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('catexport')
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['custom', '客户名称'],
                ['categorys', '客户分类'],
                ['create_time', '分类修改时间','datetime'],
                ['export_time', '导入时间','datetime'],
                ['employ', '操作人'],
            ])
            ->setRowList($data_list)// 设置表格数据
            ->raw('custom,employ,categorys') // 使用原值
            ->addTopButton('custom', $btnexport)
            ->fetch(); // 渲染模板
        
    }
    /**
     * [catexport 导出]
     * @return [type] [description]
     */
    public function catexport()
    {
        
        //查询数据
        $map = [];
        $data = ReportcatModel::where($map)->order('id desc')->select();
        foreach ($data as $key => $value) {
            $data[$key]['custom'] = ReportcatModel::getCustomAttr(null,$value);
            $data[$key]['categorys'] = ReportcatModel::getCategorysAttr(null,$value);
            $data[$key]['employ'] = ReportcatModel::getEmployAttr(null,$value);
            $data[$key]['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
            $data[$key]['export_time'] = date('Y-m-d H:i:s',$value['export_time']);
            
        }
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['id','auto', 'ID'],
            ['custom','auto', '客户名称'],
            ['categorys','auto', '客户分类'],
            ['create_time','auto', '分类修改时间', 'datetime'],
            ['export_time','auto', '导入时间', 'datetime'],
            ['employ','auto', '操作人'],

        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['客户分类报表', $cellName, $data]);
    }

    /**
     * [timeLenth 呼出时间排名]
     * @return [type] [description]
     */
    public function timeLenth()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        if (isset($map['create_time'])) {
            //部门
            if (isset($map['role_id'])) {
                $user_ids = db('admin_user')->where(['role'=>$map['role_id']])->column('id');
                unset($map['role_id']);
                if ($user_ids) {
                    $map['user_id'] = array('in',array_column($user_ids, 'id'));
                }
                
            }
            $data_list = CalllogModel::where($map)->field('*,SUM(timeLength) as times,count(*) as call_count')->order('times DESC')->group('user_id')->paginate()->each(function($item, $key) use ($map){
                    unset($m);
                    $m['create_time'] = $map['create_time'];
                    $m['user_id'] = $item['user_id'];
                    $m['timeLength'] = array('gt',0);
                    $item->timerange = $map['create_time'][1][0].'~'.$map['create_time'][1][1];
                    // $item->time_minu = number_format($item['times']/60,2);
                    $item->time_minu = times_exchange_His($item['times']);
                    $item->get_count = CalllogModel::where($m)->count();
                    $item->call_rate = (number_format($item->get_count/$item->call_count,2)*100).'%';
                });
        }else{
            unset($map);
            $map['id'] = '';
            $data_list = CalllogModel::where($map)->field('*,SUM(timeLength) as times')->order('times DESC')->group('user_id')->paginate();
        }
       
        // 分页数据
        $page = $data_list->render();


        $btn_access = [
            'title' => '配置',
            'icon'  => 'fa fa-fw fa-cog ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('trangeSetting',['tag'=>'timeLength'])
        ];
        if (isset($map['create_time'])) {
            $btnexport = [
                // 'class' => 'btn btn-info',
                'title' => '导出',
                'icon'  => 'fa fa-fw fa-file-excel-o',
                'href'  => url('timeLenthexport',['timerange'=>json_encode($map)])
            ];
        }else{
            $btnexport = [
                // 'class' => 'btn btn-info',
                'title' => '导出',
                'icon'  => 'fa fa-fw fa-file-excel-o',
                'href'  => url('timeLenthexport')
            ];
        }
        $roles = RoleModel::where(['status'=>1])->column('id,name'); 

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->addFilter('role_id', $roles)
            // ->addTimeFilter('create_time') // 添加时间段筛选
            ->setSearchArea([
                ['daterange', 'create_time', '时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
            ])
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['__INDEX__', '序列'],
                ['user', '员工'],
                ['role', '部门'],
                ['times', '呼出通话时长(秒)'],
                ['time_minu', '呼出通话时长'],
                ['get_count', '接通数'],
                ['call_count', '呼叫数'],
                ['call_rate', '接通率'],
                ['__INDEX__', '时长排名'],
                ['timerange', '时间段'],
            ])
            ->addTopButton('custom', $btn_access,true)
            ->addTopButton('custom', $btnexport)
            ->setRowList($data_list)// 设置表格数据
            ->raw('user') // 使用原值
            ->fetch(); // 渲染模板
    }

    /**
     * [timeLenthexport 导出]
     * @return [type] [description]
     */
    public function timeLenthexport($timerange = [])
    {
        
        //查询数据
        if (!$timerange) $this->error('缺少参数');
        $map = object_to_array(json_decode(str_replace('+',' ',urldecode($timerange))));

        // if (isset($map['create_time'])) {
        //     $data = CalllogModel::where($map)->field('*,SUM(timeLength) as times')->order('times DESC')->group('user_id')->paginate()->each(function($item, $key) use ($map){
        //             $item->timerange = $map['create_time'][1][0].'~'.$map['create_time'][1][1];
        //             $item->__INDEX__ = $key+1;
        //         });
        // }
        
        if (isset($map['create_time'])) {
            //部门
            if (isset($map['role_id'])) {
                $user_ids = db('admin_user')->where(['role'=>$map['role_id']])->column('id');
                unset($map['role_id']);
                if ($user_ids) {
                    $map['user_id'] = array('in',array_column($user_ids, 'id'));
                }
                
            }
            $data_list = CalllogModel::where($map)->field('*,SUM(timeLength) as times,count(*) as call_count')->order('times DESC')->group('user_id')->paginate()->each(function($item, $key) use ($map){
                    unset($m);
                    $m['create_time'] = $map['create_time'];
                    $m['user_id'] = $item['user_id'];
                    $m['timeLength'] = array('gt',0);
                    $item->timerange = $map['create_time'][1][0].'~'.$map['create_time'][1][1];
                    $item->time_minu = times_exchange_His($item['times']);
                    $item->get_count = CalllogModel::where($m)->count();
                    $item->call_rate = (number_format($item->get_count/$item->call_count,2)*100).'%';
                    $item->__INDEX__ = $key+1;
                });
        }else{
            unset($map);
            $map['id'] = '';
            $data_list = CalllogModel::where($map)->field('*,SUM(timeLength) as times')->order('times DESC')->group('user_id')->paginate();
        }
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['user','auto', '员工'],
            ['role', 'auto', '部门'],
            ['times','auto',  '呼出通话时长(秒)'],
            ['time_minu', 'auto', '呼出通话时长'],
            ['get_count', 'auto', '接通数'],
            ['call_count','auto',  '呼叫数'],
            ['call_rate', 'auto', '接通率'],
            ['__INDEX__', 'auto', '时长排名'],
            ['timerange', 'auto', '时间段'],

        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['呼出时长报表', $cellName, $data_list]);
    }

    /**
     * [setting description]
     * @param  [type] $tag [description]
     * @return [type]      [description]
     */
    public function trangeSetting($tag)
    {

        if ($this->request->isPost()) {
            $data = $this->request->post();
            plugin_config('wechat.timeLength',$data['timeLength']);
            
            $this->success('配置成功', null,'_close_pop');
        }
        $info = [
            'timeLength'=>isset(plugin_config('wechat')['timeLength'])?plugin_config('wechat')['timeLength']:''
        ];
        return ZBuilder::make('form')
                ->setPageTitle('配置') // 设置页面标题
                ->addFormItems([ // 批量添加表单项
                    ['text', 'timeLength', '时间点','<code>多个时间点：12|17|21</code>'],
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
    }

    /**
     * [classAReport description]
     * @return [type] [description]
     */
    public function classAReport()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        
        if (!$map) {
            $map['id'] = '';//过滤所有数据
        }
        $map['category'] = 1;
        $data_list = CustomModel::where($map)->field('*,avg(fee) as fees')->order('fees DESC')->group('project_id,source')->paginate();
        
        
        // 分页数据
        $page = $data_list->render();

        $btn_access = [
            'title' => '配置',
            'icon'  => 'fa fa-fw fa-cog ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('classasetting',['tag'=>'classAReport'])
        ];

        if (isset($map['update_time'])) {
            $btnexport = [
                // 'class' => 'btn btn-info',
                'title' => '导出',
                'icon'  => 'fa fa-fw fa-file-excel-o',
                'href'  => url('classAReportexport',['timerange'=>json_encode($map)])
            ];
        }else{
            $btnexport = [
                // 'class' => 'btn btn-info',
                'title' => '导出',
                'icon'  => 'fa fa-fw fa-file-excel-o',
                'href'  => url('classAReportexport')
            ];
        }
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            // ->addTimeFilter('update_time') // 添加时间段筛选
            ->setSearchArea([
                ['daterange', 'update_time', '时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
            ])
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['project', '项目'],
                ['source', '客户来源'],
                ['fees', '成本'],
                ['categorys', '客户分类'],
                ['employ', '操作人'],
            ])
            ->addTopButton('custom', $btn_access,true)
            ->addTopButton('custom', $btnexport)
            ->setRowList($data_list)// 设置表格数据
            ->raw('project,categorys,employ') // 使用原值
            ->fetch(); // 渲染模板
    }

    /**
     * [classAReportexport 导出]
     * @return [type] [description]
     */
    public function classAReportexport($timerange = [])
    {
        
        //查询数据
        if (!$timerange) $this->error('缺少参数');
        $map = object_to_array(json_decode(str_replace('+',' ',urldecode($timerange))));

        if (isset($map['update_time'])) {
            $map['category'] = 1;
            $data = CustomModel::where($map)->field('*,avg(fee) as fees')->order('fees DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    $item->timerange = $map['update_time'][1][0].'~'.$map['update_time'][1][1];
                });
        }
       
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['project','auto', '项目'],
            ['source','auto', '客户来源'],
            ['fees','auto', '成本'],
            ['categorys','auto', '客户分类'],
            ['employ','auto', '操作人'],
            ['timerange','auto', '查询时间段'],
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['A类周平均成本', $cellName, $data]);
    }

    /**
     * [setting description]
     * @param  [type] $tag [description]
     * @return [type]      [description]
     */
    public function classasetting($tag)
    {

        if ($this->request->isPost()) {
            $data = $this->request->post();
            plugin_config('wechat.classAReport',$data['classAReport']);
            
            $this->success('配置成功', null,'_close_pop');
        }
        $info = [
            'classAReport'=>isset(plugin_config('wechat')['classAReport'])?plugin_config('wechat')['classAReport']:''
        ];
        return ZBuilder::make('form')
                ->setPageTitle('配置') // 设置页面标题
                ->addFormItems([ // 批量添加表单项
                    ['text', 'classAReport', '安全值','<code>如：100</code>'],
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
    }

    /**
     * [classNReport 单条客户平均成本]
     * @return [type] [description]
     */
    public function classNReport()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        
        if (!$map) {
            $map['id'] = '';//过滤所有数据
        }
        $data_list = CustomModel::where($map)->field('*,avg(fee) as fees')->order('fees DESC')->group('project_id,source')->paginate();
       
        
        // 分页数据
        $page = $data_list->render();
        $btn_access = [
            'title' => '配置',
            'icon'  => 'fa fa-fw fa-cog ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('classnsetting',['tag'=>'classNReport'])
        ];

        if (isset($map['update_time'])) {
            $btnexport = [
                // 'class' => 'btn btn-info',
                'title' => '导出',
                'icon'  => 'fa fa-fw fa-file-excel-o',
                'href'  => url('classNReportexport',['timerange'=>json_encode($map)])
            ];
        }else{
            $btnexport = [
                // 'class' => 'btn btn-info',
                'title' => '导出',
                'icon'  => 'fa fa-fw fa-file-excel-o',
                'href'  => url('classNReportexport')
            ];
        }
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            // ->addTimeFilter('update_time') // 添加时间段筛选
            ->setSearchArea([
                ['daterange', 'update_time', '时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
            ])
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['project', '项目'],
                ['source', '客户来源'],
                ['fees', '成本'],
                // ['categorys', '客户分类'],
                // ['employ', '操作人'],
            ])
            // ->addTopButton('custom', $btn_access,true)
            ->setRowList($data_list)// 设置表格数据
            ->addTopButton('custom', $btn_access,true)
            ->addTopButton('custom', $btnexport)
            ->raw('project,categorys,employ') // 使用原值
            ->fetch(); // 渲染模板
    }

    /**
     * [classNReportexport 导出]
     * @return [type] [description]
     */
    public function classNReportexport($timerange = [])
    {
        
        //查询数据
        if (!$timerange) $this->error('缺少参数');
        $map = object_to_array(json_decode(str_replace('+',' ',urldecode($timerange))));

        if (isset($map['update_time'])) {
            $data = CustomModel::where($map)->field('*,avg(fee) as fees')->order('fees DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    $item->timerange = $map['update_time'][1][0].'~'.$map['update_time'][1][1];
                });
        }
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['project','auto', '项目'],
            ['source','auto', '客户来源'],
            ['fees','auto', '成本'],
            ['timerange','auto', '查询时间段'],
            // ['employ','auto', '操作人'],
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['单条客户平均成本', $cellName, $data]);
    }

    /**
     * [setting description]
     * @param  [type] $tag [description]
     * @return [type]      [description]
     */
    public function classnsetting($tag)
    {

        if ($this->request->isPost()) {
            $data = $this->request->post();
            plugin_config('wechat.classNReport',$data['classNReport']);
            
            $this->success('配置成功', null,'_close_pop');
        }
        $info = [
            'classNReport'=>isset(plugin_config('wechat')['classNReport'])?plugin_config('wechat')['classNReport']:''
        ];
        return ZBuilder::make('form')
                ->setPageTitle('配置') // 设置页面标题
                ->addFormItems([ // 批量添加表单项
                    ['text', 'classNReport', '安全值','<code>如：100</code>'],
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
    }
    
    /**
     * [classFReport 当月签约客户平均成本]
     * @return [type] [description]
     */
    public function classFReport()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        
        if (!$map) {
            $map['id'] = '';//过滤所有数据
        }
        $map['category'] = 6;
        if (isset($map['sign_time'])) {
            $m['create_time'] = $map['sign_time'];
            $ids = ReportcatModel::where($m)->column('custom_id');
            $map['id'] = array('in',array_unique($ids));
            unset($map['sign_time']);
        }
        if (!isset($map['note_time'])) {
            $map['id'] = '';//无时间查询过滤
            $data_list = CustomModel::where($map)->field('*,avg(fee) as avgffee,count(*) as counts')->order('avgffee DESC')->group('project_id,source')->paginate();
        }else{
            $data_list = CustomModel::where($map)->field('*,avg(fee) as avgffee,count(*) as counts')->order('avgffee DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    $item->avgffee = number_format($item['avgffee'],1);
                    unset($m1);
                    unset($m2);
                    $m1['note_time'] = $map['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $item->total =  CustomModel::where($m1)->count();//当前查询总量

                    $item->avgfee =  number_format(CustomModel::where($m1)->avg('fee'),1);//当月单条平均成本
                    $m2 = $m1;
                    $m2['note_time'][0] = '< time';
                    $m2['note_time'][1] = $m1['note_time'][1][0];

                    $item->ftotal =  CustomModel::where($m2)->count();//往期数据总数量
                    $item->favgfee =  number_format(CustomModel::where($m2)->avg('fee'),1);//往期单条客户成本
                    $m2['category'] = 6;
                    $item->fcounts =  CustomModel::where($m2)->count();//往期签单数量
                    $item->favgffee =  number_format(CustomModel::where($m2)->avg('fee'),1) ;//往期签约平均成本
                });
        }
        
        
        // 分页数据
        $page = $data_list->render();

        $sources = db('call_custom')->column('source');
        foreach ($sources as $key => $value) {
            $list_source[$value] = $value;
        }

        $btn_access = [
            'title' => '配置',
            'icon'  => 'fa fa-fw fa-cog ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('classfsetting',['tag'=>'classFReport'])
        ];

        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('classFReportexport',http_build_query($this->request->param()))
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            // ->addTimeFilter('note_time','','留言开始时间,留言结束时间') // 添加时间段筛选
            ->setSearchArea([
                ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                ['daterange', 'sign_time', '签约时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                ['select', 'source', '平台来源', '', '', $list_source],

            ])
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['project', '项目'],
                ['source', '客户来源'],
                ['total', '当月数据总数量'],
                ['counts', '当月签单数量'],
                ['avgfee', '当月单条平均成本'],
                ['avgffee', '当月签约平均成本'],
                ['favgfee', '往期单条客户成本'],
                ['ftotal', '往期数据总数量'],
                ['fcounts', '往期签单数量'],
                ['favgffee', '往期签约平均成本'],
                
            ])
            ->addTopButton('custom', $btn_access,true)
            ->addTopButton('custom', $btnexport)
            ->setRowList($data_list)// 设置表格数据
            ->raw('project') // 使用原值
            ->fetch(); // 渲染模板
    }

    /**
     * [classNReportexport 导出]
     * @return [type] [description]
     */
    public function classFReportexport()
    {
        $map = $this->getMaps();
        //查询数据
        if (!$map) $this->error('缺少参数');

        $map = deep_array_map($map);
        $map['category'] = 6;
        if (isset($map['sign_time'])) {
            $m['create_time'] = $map['sign_time'];
            $ids = ReportcatModel::where($m)->column('custom_id');
            $map['id'] = array('in',array_unique($ids));
            unset($map['sign_time']);
        }
        
        if (!isset($map['note_time'])) {
            $map['id'] = '';//无时间查询过滤
            $data_list = CustomModel::where($map)->field('*,avg(fee) as avgffee,count(*) as counts')->order('avgffee DESC')->group('project_id,source')->paginate();
        }else{
            $data = CustomModel::where($map)->field('*,avg(fee) as avgffee,count(*) as counts')->order('avgffee DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    $item->avgffee = number_format($item['avgffee'],1);
                    unset($m1);
                    unset($m2);
                    $m1['note_time'] = $map['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $item->total =  CustomModel::where($m1)->count();//当前查询总量

                    $item->avgfee =  number_format(CustomModel::where($m1)->avg('fee'),1);//当月单条平均成本
                    $m2 = $m1;
                    $m2['note_time'][0] = '< time';
                    $m2['note_time'][1] = $m1['note_time'][1][0];

                    $item->ftotal =  CustomModel::where($m2)->count();//往期数据总数量
                    $item->favgfee =  number_format(CustomModel::where($m2)->avg('fee'),1);//往期单条客户成本
                    $m2['category'] = 6;
                    $item->fcounts =  CustomModel::where($m2)->count();//往期签单数量
                    $item->favgffee =  number_format(CustomModel::where($m2)->avg('fee'),1) ;//往期签约平均成本

                    // $item->timerange = $map['note_time'][1][0].'~'.$map['note_time'][1][1];
                });
        }
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['project','auto', '项目'],
            ['source', 'auto', '客户来源'],
            ['total', 'auto', '当月数据总数量'],
            ['counts', 'auto', '当月签单数量'],
            ['avgfee', 'auto', '当月单条平均成本'],
            ['avgffee', 'auto', '当月签约平均成本'],
            ['favgfee', 'auto', '往期单条客户成本'],
            ['ftotal', 'auto', '往期数据总数量'],
            ['fcounts', 'auto', '往期签单数量'],
            ['favgffee','auto',  '往期签约平均成本'],
            // ['timerange','auto', '留言时间段'],
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['当月签约客户平均成本', $cellName, $data]);
    }

    /**
     * [classfsetting description]
     * @param  [type] $tag [description]
     * @return [type]      [description]
     */
    public function classfsetting($tag)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            plugin_config('wechat.classFReport',$data['classFReport']);
            
            $this->success('配置成功', null,'_close_pop');
        }
        $info = [
            'classFReport'=>isset(plugin_config('wechat')['classFReport'])?plugin_config('wechat')['classFReport']:''
        ];
        return ZBuilder::make('form')
                ->setPageTitle('配置') // 设置页面标题
                ->addFormItems([ // 批量添加表单项
                    ['text', 'classFReport', '签约成本安全值','<code>如：200</code>'],
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
    }

    /**
     * [classf15Report 15天签约数量统计]
     * @return [type] [description]
     */
    public function classf15Report()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        
        if (!$map) {
            $map['id'] = '';//过滤所有数据
        }
        $map['category'] = 6;

        if (isset($map['note_time'])) {
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    unset($m1);
                    $m1['note_time'] = $map['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $item->total =  CustomModel::where($m1)->count();//当前查询总量

            });
        }else{
            $map['id'] = '';//过滤数据
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }
        
        
        // 分页数据
        $page = $data_list->render();

        $sources = db('call_custom')->column('source');
        foreach ($sources as $key => $value) {
            $list_source[$value] = $value;
        }

        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('classf15Reportexport',http_build_query($this->request->param()))
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearchArea([
                ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                ['select', 'source', '平台来源', '', '', $list_source],

            ])
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['project', '项目'],
                ['source', '客户来源'],
                ['total', '留言总数量'],
                ['counts', '签单数量'],
            ])
            ->setRowList($data_list)// 设置表格数据
            ->addTopButton('custom', $btnexport)
            ->raw('project') // 使用原值
            ->fetch(); // 渲染模板
    }

    /**
     * [classNReportexport 导出]
     * @return [type] [description]
     */
    public function classf15Reportexport()
    {
        $map = $this->getMaps();
        //查询数据
        if (!$map) $this->error('缺少参数');

        $map['category'] = 6;
        if (isset($map['note_time'])) {
            $data = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    unset($m1);
                    $m1['note_time'] = $map['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $item->total =  CustomModel::where($m1)->count();//当前查询总量
                    $item->timerange = $map['note_time'][1][0].'~'.$map['note_time'][1][1];


            });
        }else{
            $map['id'] = '';//过滤数据
            $data = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['project','auto', '项目'],
            ['source', 'auto', '客户来源'],
            ['total', 'auto', '留言总数量'],
            ['counts', 'auto', '签单数量'],
            ['timerange','auto', '查询时间段'],
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['15天签约数量统计', $cellName, $data]);
    }
    /**
     * [classFweekReport 每周所有平台签约数量]
     * @return [type] [description]
     */
    public function classFweekReport()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        
        if (!$map) {
            $map['id'] = '';//过滤所有数据
        }
        $map['category'] = 6;

        if (isset($map['note_time'])) {
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    unset($m1);
                    $m1['note_time'] = $map['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $item->total =  CustomModel::where($m1)->count();//当前查询总量
                    $item->rate = (number_format($item['counts']/$item->total,1)*100).'%';

            });
        }else{
            $map['id'] = '';//过滤数据
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }
        
        
        // 分页数据
        $page = $data_list->render();

        $sources = db('call_custom')->column('source');
        foreach ($sources as $key => $value) {
            $list_source[$value] = $value;
        }
        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('classFweekReportexport',http_build_query($this->request->param()))
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearchArea([
                ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                ['select', 'source', '平台来源', '', '', $list_source],

            ])
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['project', '项目'],
                ['source', '客户来源'],
                ['total', '留言总数量'],
                ['counts', '签单数量'],
                ['rate', '签单数量占比'],
            ])
            ->setRowList($data_list)// 设置表格数据
            ->addTopButton('custom', $btnexport)
            ->raw('project') // 使用原值
            ->fetch(); // 渲染模板
    }

    /**
     * [classFweekReportexport 导出]
     * @return [type] [description]
     */
    public function classFweekReportexport()
    {
        $map = $this->getMaps();
        //查询数据
        if (!$map) $this->error('缺少参数');

        $map['category'] = 6;
        if (isset($map['note_time'])) {
            $data = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    unset($m1);
                    $m1['note_time'] = $map['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $item->total =  CustomModel::where($m1)->count();//当前查询总量
                    $item->rate = (number_format($item['counts']/$item->total,1)*100).'%';
                    $item->timerange = $map['note_time'][1][0].'~'.$map['note_time'][1][1];

            });
        }else{
            $map['id'] = '';//过滤数据
            $data = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }

        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('classFweekReportexport',http_build_query($this->request->param()))
        ];
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['project','auto', '项目'],
            ['source','auto', '客户来源'],
            ['total', 'auto','留言总数量'],
            ['counts', 'auto','签单数量'],
            ['rate', 'auto','签单数量占比'],
            ['timerange','auto', '查询时间段'],
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['周所有平台签约数量', $cellName, $data]);
    }

    /**
     * [classFWeekTimelgReport 周所有平台通话时长签约]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function classFWeekTimelgReport()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        
        if (!$map) {
            $map['id'] = '';//过滤所有数据
        }
        $map['category'] = 6;

        if (isset($map['note_time'])) {
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    unset($m1);
                    unset($m2);
                    $m1['note_time'] = $map['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $item->total =  CustomModel::where($m1)->count();//当前查询总量
                    $mbs = CustomModel::where($m1)->column('mobile');
                    $m2['create_time'] = $map['note_time'];
                    $m2['calledNum'] = array('in',$mbs);
                    // print_r($mbs);exit;
                    $item->timeLg = times_exchange_His(CalllogModel::where($m2)->sum('timeLength'));
                    $item->pec = ceil($item->timeLg/$item['counts']);

            });
        }else{
            $map['id'] = '';//过滤数据
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }
        
        
        // 分页数据
        $page = $data_list->render();

        $sources = db('call_custom')->column('source');
        foreach ($sources as $key => $value) {
            $list_source[$value] = $value;
        }
        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('classFWeekTimelgReportexport',http_build_query($this->request->param()))
        ];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearchArea([
                ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                ['select', 'source', '平台来源', '', '', $list_source],

            ])
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['project', '项目'],
                ['source', '客户来源'],
                ['total', '留言总数量'],
                ['timeLg', '平台通话时长'],
                ['counts', '签单数量'],
                ['pec', '每单的分钟数'],
            ])
            ->setRowList($data_list)// 设置表格数据
            ->addTopButton('custom', $btnexport)
            ->raw('project') // 使用原值
            ->fetch(); // 渲染模板
    }

    /**
     * [classFweekReportexport 导出]
     * @return [type] [description]
     */
    public function classFWeekTimelgReportexport()
    {
        $map = $this->getMaps();
        //查询数据
        if (!$map) $this->error('缺少参数');

        $map['category'] = 6;

        if (isset($map['note_time'])) {
            $data = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    unset($m1);
                    unset($m2);
                    $m1['note_time'] = $map['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $item->total =  CustomModel::where($m1)->count();//当前查询总量
                    $mbs = CustomModel::where($m1)->column('mobile');
                    $m2['create_time'] = $map['note_time'];
                    $m2['calledNum'] = array('in',$mbs);
                    // print_r($mbs);exit;
                    $times = CalllogModel::where($m2)->sum('timeLength');
                    $item->timeLg = times_exchange_His($times);//date('i:s',120.83)
                    $item->pec = times_exchange_His($times/$item['counts']);
                    $item->timerange = $map['note_time'][1][0].'~'.$map['note_time'][1][1];

            });
        }else{
            $map['id'] = '';//过滤数据
            $data = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }

        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['project','auto', '项目'],
            ['source', 'auto','客户来源'],
            ['total', 'auto','留言总数量'],
            ['timeLg', 'auto','平台通话时长'],
            ['counts','auto', '签单数量'],
            ['pec', 'auto','每单的分钟数'],
            ['timerange','auto', '查询时间段'],
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['周所有平台通话时长签约', $cellName, $data]);
    }

    /**
     * [classFRateReport 平台分时签约率]
     * @return [type] [description]
     */
    public function classFRateReport()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        if (!$map) {
            $map['id'] = '';//过滤所有数据
        }
        $map['category'] = 6;
        

        if (isset($map['area'])) {
            if (strpos($map['area'][1],'省')) {
                $map['area'][1] = explode('省', $map['area'][1])[1];
            }
            $mm['area_name'] = array('like','%'.$map['area'][1].'%');
            $citycode = db('packet_common_area')->where($mm)->value('area_code');
            $map['city'] = $citycode;
            unset($map['area']);
        }
        
        if (isset($map['note_time'])) {
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source,city')->paginate()->each(function($item, $key) use ($map){
                    unset($m1);
                    unset($m2);
                    $m1['note_time'] = $map['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $item->total =  CustomModel::where($m1)->count();//当前查询总量
                    $parent = db('packet_common_area')->where(['area_code'=>$item['province']])->value('area_name');
                    $item->citys = $parent.' '.db('packet_common_area')->where(['area_code'=>$item['city']])->value('area_name');
                    $item->rate = (number_format($item['counts']/$item->total,1)*100).'%';

            });
        }else{
            $map['id'] = '';//过滤数据
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }
        
        
        // 分页数据
        $page = $data_list->render();

        $sources = db('call_custom')->column('source');
        foreach ($sources as $key => $value) {
            $list_source[$value] = $value;
        }

        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('classFRateReportexport',http_build_query($this->request->param()))
        ];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearchArea([
                ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                ['select', 'source', '平台来源', '', '', $list_source],
                ['text', 'area', '留言地区'],

            ])
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['project', '项目'],
                ['source', '客户来源'],
                ['citys', '留言地区'],
                ['total', '留言数量'],
                ['counts', '签单数量'],
                ['rate', '签约率'],
            ])
            ->setRowList($data_list)// 设置表格数据
            ->addTopButton('custom', $btnexport)
            ->raw('project') // 使用原值
            ->fetch(); // 渲染模板
    }
     /**
     * [classFweekReportexport 导出]
     * @return [type] [description]
     */
    public function classFRateReportexport()
    {
        $map = $this->getMaps();
        //查询数据
        if (!$map) $this->error('缺少参数');

        $map['category'] = 6;
        

        if (isset($map['area'])) {
            if (strpos($map['area'][1],'省')) {
                $map['area'][1] = explode('省', $map['area'][1])[1];
            }
            $mm['area_name'] = array('like','%'.$map['area'][1].'%');
            $citycode = db('packet_common_area')->where($mm)->value('area_code');
            $map['city'] = $citycode;
            unset($map['area']);
        }
        
        if (isset($map['note_time'])) {
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source,city')->paginate()->each(function($item, $key) use ($map){
                    unset($m1);
                    unset($m2);
                    $m1['note_time'] = $map['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $item->total =  CustomModel::where($m1)->count();//当前查询总量
                    $parent = db('packet_common_area')->where(['area_code'=>$item['province']])->value('area_name');
                    $item->citys = $parent.' '.db('packet_common_area')->where(['area_code'=>$item['city']])->value('area_name');
                    $item->rate = (number_format($item['counts']/$item->total,1)*100).'%';
                    $item->timerange = $map['note_time'][1][0].'~'.$map['note_time'][1][1];

            });
        }else{
            $map['id'] = '';//过滤数据
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }

        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['project','auto', '项目'],
            ['source','auto', '客户来源'],
            ['citys', 'auto','留言地区'],
            ['total','auto', '留言数量'],
            ['counts', 'auto','签单数量'],
            ['rate','auto', '签约率'],
            ['timerange','auto', '查询时间段'],
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['平台分时签约率', $cellName, $data_list]);
    }

    /**
     * [platformConRateReport 平台数据接通率]
     * @return [type] [description]
     */
    public function platformConRateReport()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        
        if (!$map) {
            $map['id'] = '';//过滤所有数据
        }

        if (isset($map['note_time'])) {
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    unset($m1);
                    unset($m2);
                    $m1['note_time'] = $map['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $item->total =  CustomModel::where($m1)->count();//当前查询总量
                    $mbs = CustomModel::where($m1)->column('mobile');
                    $m2['create_time'] = $map['note_time'];
                    $m2['calledNum'] = array('in',$mbs);

                    $item->calltimes = CalllogModel::where($m2)->count();
                    $item->contacts = CalllogModel::where($m2)->group('calledNum')->count();

                    $item->conrate = (number_format($item->contacts/$item->total,1)*100).'%';

            });
        }else{
            $map['id'] = '';//过滤数据
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }
        
        
        // 分页数据
        $page = $data_list->render();

        $sources = db('call_custom')->column('source');
        foreach ($sources as $key => $value) {
            $list_source[$value] = $value;
        }
        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('platformConRateReportexport',http_build_query($this->request->param()))
        ];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearchArea([
                ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                ['select', 'source', '平台来源', '', '', $list_source],

            ])
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['project', '项目'],
                ['source', '客户来源'],
                ['total', '留言总数量'],
                ['calltimes', '呼叫数量（打1次算1次）'],
                ['contacts', '接通数(1号码只算1次)'],
                ['conrate', '接通率'],
            ])
            ->setRowList($data_list)// 设置表格数据
            ->addTopButton('custom', $btnexport)
            ->raw('project') // 使用原值
            ->fetch(); // 渲染模板
    }
    /**
     * [classFweekReportexport 导出]
     * @return [type] [description]
     */
    public function platformConRateReportexport()
    {
        $map = $this->getMaps();
        //查询数据
        if (!$map) $this->error('缺少参数');

        if (isset($map['note_time'])) {
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    unset($m1);
                    unset($m2);
                    $m1['note_time'] = $map['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $item->total =  CustomModel::where($m1)->count();//当前查询总量
                    $mbs = CustomModel::where($m1)->column('mobile');
                    $m2['create_time'] = $map['note_time'];
                    $m2['calledNum'] = array('in',$mbs);

                    $item->calltimes = CalllogModel::where($m2)->count();
                    $item->contacts = CalllogModel::where($m2)->group('calledNum')->count();

                    $item->conrate = (number_format($item->contacts/$item->total,1)*100).'%';
                    $item->timerange = $map['note_time'][1][0].'~'.$map['note_time'][1][1];

            });
        }else{
            $map['id'] = '';//过滤数据
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }

        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['project','auto', '项目'],
            ['source','auto', '客户来源'],
            ['total', 'auto','留言总数量'],
            ['calltimes','auto', '呼叫数量（打1次算1次）'],
            ['contacts', 'auto','接通数(1号码只算1次)'],
            ['conrate', 'auto','接通率'],
            ['timerange','auto', '查询时间段'],
        ];
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['平台数据接通率', $cellName, $data_list]);
    }

    /**
     * [feeStatisReport 员工产出成本统计]
     * @return [type] [description]
     */
    public function feeStatisReport()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        
        if (!$map) {
            $map['id'] = '';//过滤所有数据
        }

        $colArr = [];
        $lableArr = db('call_custom_cat')->column('id,title');
        foreach ($lableArr as $key => $value) {
            $tmp = ['category'.$key,$value];
            array_push($colArr, $tmp);
        }

        if (isset($map['note_time'])) {
            $udata = [];
            $udata['note_time'] = $map['note_time'];
            $udata['lableArr'] = $lableArr;
            $data_list = CustomModel::where($map)->field('*,count(*) as counts,sum(fee) as totalfee')->order('counts DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($udata){
                    unset($m1);
                    $m1['note_time'] = $udata['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $m1['category'] = 6;
                    $item->succs =  CustomModel::where($m1)->count();//成交数
                    //成交率
                    $item->succrate =  (number_format($item->succs/$item['counts'],1)*100).'%';

                    $m2 = $m1;
                    unset($m2['category']);
                    foreach ($udata['lableArr'] as $key => $value) {
                        $m2['category'] = $key;
                        $item['category'.$key] = db('call_custom')->where($m2)->count();
                    }
            });
        }else{
            $map['id'] = '';//过滤数据
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }
        
        
        // 分页数据
        $page = $data_list->render();

        $btn_access = [
            'title' => '查看组',
            'icon'  => 'fa fa-fw fa-file-excel-o ',
            // 'class' => 'btn btn-default',
            'href' => url('feeStatisRowsReport')
        ];
        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('feeStatisReportexport',http_build_query($this->request->param()))
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearchArea([
                ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],

            ])
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['project', '项目'],
                ['source', '客户来源'],
                // ['rows', '销售组','url'],
                ['counts', '留言总数量'],
                ['totalfee', '留言成本'],
                ['succs', '成交数'],
                ['succrate', '成交率'],
                // ['right_button', '操作', 'btn']
            ])
            ->addColumns($colArr)
            // ->addRightButton('custom',$btn_access)
            ->addTopButton('custom',$btn_access)
            ->addTopButton('custom', $btnexport)
            ->setRowList($data_list)// 设置表格数据
            ->raw('project') // 使用原值
            ->fetch(); // 渲染模板
    }

    /**
     * [classFweekReportexport 导出]
     * @return [type] [description]
     */
    public function feeStatisReportexport()
    {
        $map = $this->getMaps();
        //查询数据
        if (!$map) $this->error('缺少参数');

        $lableArr = db('call_custom_cat')->column('id,title');

        if (isset($map['note_time'])) {
            $udata = [];
            $udata['note_time'] = $map['note_time'];
            $udata['lableArr'] = $lableArr;
            $data_list = CustomModel::where($map)->field('*,count(*) as counts,sum(fee) as totalfee')->order('counts DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($udata){
                    unset($m1);
                    $m1['note_time'] = $udata['note_time']; 
                    $m1['project_id'] = $item['project_id'];
                    $m1['source'] = $item['source'];
                    $m1['category'] = 6;
                    $item->succs =  CustomModel::where($m1)->count();//成交数
                    //成交率
                    $item->succrate =  (number_format($item->succs/$item['counts'],1)*100).'%';

                    $m2 = $m1;
                    unset($m2['category']);
                    foreach ($udata['lableArr'] as $key => $value) {
                        $m2['category'] = $key;
                        $item['category'.$key] = db('call_custom')->where($m2)->count();
                    }
                    $item->timerange = $udata['note_time'][1][0].'~'.$udata['note_time'][1][1];
            });
        }else{
            $map['id'] = '';//过滤数据
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }

        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['project','auto', '项目'],
            ['source', 'auto','客户来源'],
            ['counts', 'auto','留言总数量'],
            ['totalfee', 'auto','留言成本'],
            ['succs', 'auto','成交数'],
            ['succrate', 'auto','成交率'],
            ['timerange','auto', '查询时间段'],
        ];
        
        foreach ($lableArr as $key => $value) {
            $tmp = ['category'.$key,'auto',$value];
            array_push($cellName, $tmp);
        }
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['员工产出成本统计', $cellName, $data_list]);
    }

    /**
     * [feeStatisRowsReport 组统计]
     * @return [type] [description]
     */
    public function feeStatisRowsReport()
    {
        // 获取查询条件
        $map = $this->getMap();
        
        if (isset($map['note_time'])) {
            $udata = [];
            //custom_id
            // $custs = db('call_custom')->where($map)->column('id');
            // if ($custs) {
            //     //user_id
            //     $um['custom_id'] = array('in',$custs);
            //     $users = db('call_report_custom_cat')->where($um)->column('employ_id');
            //     $map['id'] = array('in',$users);
            // }else{
            //     $map['id'] = '';//过滤数据
            // }
            $udata['note_time'] = $map['note_time'];
            unset($map['note_time']);
            $map['role'] =array('gt',1);
            // print_r($map);exit;
            $data_list = UserModel::where($map)->field('*,GROUP_CONCAT(id) as ids')->group('role')->paginate()->each(function($item, $key) use ($udata){
                //销售组 留言总数量 留言成本 成交数 成交率
                $item->rolenm = db('admin_role')->where(['id'=>$item['role']])->value('name');
                unset($m1);
                unset($m2);
                //ids 
                $m1['employ_id'] = array('in',$item['ids']);
                $customs = db('call_report_custom_cat')->where($m1)->column('custom_id');
                $m2['note_time'] = $udata['note_time'];
                $m2['id'] = array('in',$customs);

                $item->counts = db('call_custom')->where($m2)->count(); 
                $item->totalfee = db('call_custom')->where($m2)->sum('fee'); 

                $m2['category'] = 6;
                $item->succs = db('call_custom')->where($m2)->count(); 
                if ($item->succs==0||$item->counts==0) {
                    $item->succrate = '0%';
                }else{
                    $item->succrate = (number_format($item->succs/$item->counts,1)*100).'%';
                }
                
            });
        }else{
            $map['id'] = '';//过滤数据
            $data_list = UserModel::where($map)->group('role')->paginate();
        }
        // 分页数据
        $page = $data_list->render();

        $btn_access = [
            'title' => '员工明细',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'class' => 'btn btn-default',
            'href' => url('feeStatisEmpolyReport',['id'=>'__id__'])
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearchArea([
                ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],

            ])
            ->hideCheckbox()
            ->addTopButton('back', [
                'title' => '返回列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('feeStatisReport')
            ])
            ->addColumns([ // 批量添加数据列
                // ['project', '项目'],
                // ['source', '客户来源'],
                ['rolenm', '销售组'],
                ['counts', '留言总数量'],
                ['totalfee', '留言成本'],
                ['succs', '成交数'],
                ['succrate', '成交率'],
                ['right_button', '操作', 'btn']
            ])
            ->setRowList($data_list)// 设置表格数据
            ->addRightButton('custom', $btn_access) // 添加授权按钮
            // ->raw('project') // 使用原值
            ->fetch(); // 渲染模板
    }


    /**
     * [feeStatisEmpolyReport description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function feeStatisEmpolyReport($id = null)
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        if ($id === null) $this->error('缺少参数');
        //获取组
        $role  = db('admin_user')->where(['id'=>$id])->value('role');
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        if (!$map) {
            $map['id'] = '';//过滤所有数据
        }
        if (isset($map['note_time'])) {
            $map['role'] = $role;
            $udata['note_time'] = $map['note_time'];
            unset($map['note_time']);
            $data_list = UserModel::where($map)->field('*')->paginate()->each(function($item, $key) use ($udata){
                //销售组 留言总数量 留言成本 成交数 成交率
                unset($m2);
                unset($m1);
                $m1['employ_id'] = array('in',$item['id']);
                $customs = db('call_report_custom_cat')->where($m1)->column('custom_id');
                $m2['note_time'] = $udata['note_time'];
                $m2['id'] = array('in',$customs);

                $item->counts = db('call_custom')->where($m2)->count(); 
                $item->totalfee = db('call_custom')->where($m2)->sum('fee'); 

                $m2['category'] = 6;
                $item->succs = db('call_custom')->where($m2)->count(); 
                if ($item->succs==0||$item->counts==0) {
                    $item->succrate = '0%';
                }else{
                    $item->succrate = (number_format($item->succs/$item->counts,1)*100).'%';
                }
                
            });

            // $data_list = CustomModel::where($map)->field('*,count(*) as counts,sum(fee) as totalfee')->order('counts DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
            //         unset($m1);
            //         $m1['note_time'] = $map['note_time']; 
            //         $m1['project_id'] = $item['project_id'];
            //         $m1['source'] = $item['source'];
            //         $m1['category'] = 6;
            //         $item->succs =  CustomModel::where($m1)->count();//成交数
            //         //成交率
            //         $item->succrate =  (number_format($item->succs/$item['counts'],1)*100).'%';
            //         // $item->rows = '<a href="www.baidu.com">全部</a>';
            // });
        }else{
            $map['id'] = 9999;//过滤数据
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }
        
        
        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearchArea([
                ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],

            ])
            ->hideCheckbox()
            ->addTopButton('back', [
                'title' => '返回列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('feeStatisRowsReport')
            ])
            ->addColumns([ // 批量添加数据列
                // ['project', '项目'],
                // ['source', '客户来源'],
                ['nickname', '员工'],
                ['totalfee', '留言成本'],
                ['counts', '留言总数量'],
                ['succs', '成交数'],
                ['succrate', '成交率'],
            ])
            ->setRowList($data_list)// 设置表格数据
            ->raw('project') // 使用原值
            ->fetch(); // 渲染模板
    }

    /**
     * [previousFeeReport description]
     * @return [type] [description]
     */
    public function previousFeeReport()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        if (isset($map['note_time'])) {
            $data_list = CustomModel::where($map)->field('*,count(*) as counts,sum(fee) as total')->order('counts DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    $item->avg = number_format($item['total']/$item['counts'],1);
                    unset($m1);
                    //往年节日
                    $m2['GregorianDateTime'] = $map['note_time'];
                    $start = date('Y-m-d H:i:s', strtotime($m2['GregorianDateTime'][1][0])) ;
                    $m2['GregorianDateTime'][1][0] = date('Y-m-d H:i:s',strtotime("$start-1year"));
                    $end = date('Y-m-d H:i:s', strtotime($m2['GregorianDateTime'][1][1])) ;
                    $m2['GregorianDateTime'][1][1] = date('Y-m-d H:i:s',strtotime("$end-1year"));

                    $ffestivals = db('call_calendar')->field('GROUP_CONCAT(GJie) as GJies ,GROUP_CONCAT(LJie) as LJies ')->where($m2)->group('LYear')->select();
                    $ffestival = '';
                    foreach ($ffestivals as $key => $value) {
                        $ffestival .= str_replace(',',' ', $value['GJies'].$value['LJies']) ;
                    }
                    $item->ffestival = $ffestival;
                    //本年节日
                    $m1['GregorianDateTime'] = $map['note_time'];
                    $festivals = db('call_calendar')->field('GROUP_CONCAT(GJie) as GJies ,GROUP_CONCAT(LJie) as LJies ')->where($m1)->group('LYear')->select();
                    $festival = '';
                    foreach ($festivals as $key => $value) {
                        $festival .= str_replace(',',' ', $value['GJies'].$value['LJies']) ;
                    }

                    $item->festival = $festival;
            });
        }else{
            $map['id'] = '';//过滤数据
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }
        
        
        // 分页数据
        $page = $data_list->render();

        $sources = db('call_custom')->column('source');
        foreach ($sources as $key => $value) {
            $list_source[$value] = $value;
        }

        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('previousFeeReportexport',http_build_query($this->request->param()))
        ];

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearchArea([
                ['daterange', 'note_time', '时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                ['select', 'source', '平台来源', '', '', $list_source],

            ])
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['project', '项目'],
                ['source', '客户来源'],
                ['counts', '留言总数量'],
                ['avg', '单条平均成本'],
                ['total', '总费用'],
                ['ffestival', '往年所含节日'],
                ['festival', '本年所含节日'],
            ])
            ->setRowList($data_list)// 设置表格数据
            ->addTopButton('custom', $btnexport)
            ->raw('project') // 使用原值
            ->fetch(); // 渲染模板
    }

    /**
     * [classFweekReportexport 导出]
     * @return [type] [description]
     */
    public function previousFeeReportexport()
    {
        $map = $this->getMaps();
        //查询数据
        if (!$map) $this->error('缺少参数');

        if (isset($map['note_time'])) {
            $data_list = CustomModel::where($map)->field('*,count(*) as counts,sum(fee) as total')->order('counts DESC')->group('project_id,source')->paginate()->each(function($item, $key) use ($map){
                    $item->avg = number_format($item['total']/$item['counts'],1);
                    unset($m1);
                    //往年节日
                    $m2['GregorianDateTime'] = $map['note_time'];
                    $start = date('Y-m-d H:i:s', strtotime($m2['GregorianDateTime'][1][0])) ;
                    $m2['GregorianDateTime'][1][0] = date('Y-m-d H:i:s',strtotime("$start-1year"));
                    $end = date('Y-m-d H:i:s', strtotime($m2['GregorianDateTime'][1][1])) ;
                    $m2['GregorianDateTime'][1][1] = date('Y-m-d H:i:s',strtotime("$end-1year"));

                    $ffestivals = db('call_calendar')->field('GROUP_CONCAT(GJie) as GJies ,GROUP_CONCAT(LJie) as LJies ')->where($m2)->group('LYear')->select();
                    $ffestival = '';
                    foreach ($ffestivals as $key => $value) {
                        $ffestival .= str_replace(',','', $value['GJies'].$value['LJies']) ;
                    }
                    $item->ffestival = $ffestival;
                    //本年节日
                    $m1['GregorianDateTime'] = $map['note_time'];
                    $festivals = db('call_calendar')->field('GROUP_CONCAT(GJie) as GJies ,GROUP_CONCAT(LJie) as LJies ')->where($m1)->group('LYear')->select();
                    $festival = '';
                    foreach ($festivals as $key => $value) {
                        $festival .= str_replace(',','', $value['GJies'].$value['LJies']) ;
                    }

                    $item->festival = $festival;
                    $item->timerange = $map['note_time'][1][0].'~'.$map['note_time'][1][1];
            });
        }else{
            $map['id'] = '';//过滤数据
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }

        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['project','auto', '项目'],
            ['source', 'auto','客户来源'],
            ['counts','auto', '留言总数量'],
            ['avg', 'auto','单条平均成本'],
            ['total', 'auto','总费用'],
            ['ffestival', 'auto','往年所含节日'],
            ['festival', 'auto','本年所含节日'],
            ['timerange','auto', '查询时间段'],
        ];
        
        
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['往年同期成本', $cellName, $data_list]);
    }

    /**
     * [tradeDetReport description]
     * @return [type] [description]
     */
    public function tradeDetReport()
    {

        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        if (isset($map['sign_time'])) {
            $m1['sign_time'] = $map['sign_time'];
            $map['custom_id'] = array('in',db('call_custom')->where($m1)->column('id')) ;
            unset($map['sign_time']);
        }
        if (isset($map['custom'])) {
            $m2['name'] = array('like','%'.$map['custom'][1].'%') ;
            $map['custom_id'] = array('in',db('call_custom')->where($m2)->column('id'));
            unset($map['custom']);
        }
        
        // 数据列表
        $data_list = TradeModel::where($map)->order('id desc')->paginate()->each(function($item, $key) use ($map){
                    unset($m);
                    $item->type = db('call_trade_cat')->where(['id'=>$item['type']])->value('title');
                    $item->menger = get_nickname($item['menger']);
                    $item->role = db('admin_role')->where(['id'=>$item['role']])->value('name');
                    $item->note_time = db('call_custom')->where(['id'=>$item['custom_id']])->value('note_time');
                    $item->payment = db('call_payment')->where(['trade_id'=>$item['id'],'type'=>1])->value('price');
                    
                    $m['trade_id'] = $item['id'];
                    $m['type'] = array('gt',1);
                    $item->reality = db('call_payment')->where($m)->order('id desc')->value('sign_time')?date('Y-m-d',db('call_payment')->where($m)->order('id desc')->value('sign_time')):'无' ;
                    $item->exceed = strtotime($item->reality)-$item['should_time']>0?'-'.floor((strtotime($item->reality)-$item['should_time'])/86400):floor((strtotime($item['should_time']-$item->reality))/86400);
                    $item->receipts = db('call_payment')->where($m)->order('id desc')->sum('price');
                    $item->receivable = $item['total'] - $item->payment - $item->receipts;
                    $item->custom = db('call_custom')->where(['id'=>$item['custom_id']])->value('name').'【'.db('call_custom')->where(['id'=>$item['custom_id']])->value('mobile').'】';
                });

        // 分页数据
        $page = $data_list->render();
        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('tradeDetReportexport',http_build_query($this->request->param()))
        ];
        $tradeLog = ['icon' => 'fa  fa-fw fa-envelope-o', 'title' => '变更合同日志', 'href' => url('tradeLog',['id'=>'__id__'])];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setSearchArea([
                ['daterange', 'should_time', '应收日期', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                ['daterange', 'sign_time', '签约日期', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                ['text', 'contactMobile', '手机号'],
                ['text', 'serialNO', '合同编号'],
                ['text', 'custom', '客户系统名'],

            ])
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['serialNO', '合同编号'],
                ['title', '合同名'],
                ['type', '类型'],
                ['sign_time', '签约日期','date'],
                ['signcity', '地区'],
                ['note_time', '留言时间'],
                ['custom', '客户系统名'],
                ['contactMobile', '手机号'],
                ['total', '合同金额'],
                ['payment', '已付金额'],
                ['receivable', '应收余款'],
                ['should_time', '应收日期','date'],
                ['receipts', '实收余款'],
                ['reality', '实际日期'],
                ['exceed', '逾期天数'],
                ['role', '部门'],
                ['menger', '负责人'],
                ['right_button', '操作', 'btn']
            ])
            ->setRowList($data_list)// 设置表格数据
            ->addRightButton('custom', $tradeLog,true)
            ->addTopButton('custom', $btnexport)
            ->raw('signcity') // 使用原值
            ->fetch(); // 渲染模板
    }


    
    /**
     * [classFweekReportexport 导出]
     * @return [type] [description]
     */
    public function tradeDetReportexport()
    {
        $map = $this->getMaps();
        //查询数据

        if (isset($map['sign_time'])) {
            $m1['sign_time'] = $map['sign_time'];
            $map['custom_id'] = array('in',db('call_custom')->where($m1)->column('id')) ;
            unset($map['sign_time']);
        }
        if (isset($map['custom'])) {
            $m2['name'] = array('like','%'.$map['custom'][1].'%') ;
            $map['custom_id'] = array('in',db('call_custom')->where($m2)->column('id'));
            unset($map['custom']);
        }
        
        // 数据列表
        $data_list = TradeModel::where($map)->order('id desc')->paginate()->each(function($item, $key) use ($map){
                    unset($m);
                    $item->type = db('call_trade_cat')->where(['id'=>$item['type']])->value('title');
                    $item->menger = get_nickname($item['menger']);
                    $item->role = db('admin_role')->where(['id'=>$item['role']])->value('name');
                    $item->note_time = db('call_custom')->where(['id'=>$item['custom_id']])->value('note_time');
                    $item->payment = db('call_payment')->where(['trade_id'=>$item['id'],'type'=>1])->value('price');
                    
                    $m['trade_id'] = $item['id'];
                    $m['type'] = array('gt',1);
                    $item->reality = db('call_payment')->where($m)->order('id desc')->value('sign_time')?date('Y-m-d',db('call_payment')->where($m)->order('id desc')->value('sign_time')):'无' ;
                    $item->exceed = strtotime($item->reality)-$item['should_time']>0?'-'.floor((strtotime($item->reality)-$item['should_time'])/86400):floor((strtotime($item['should_time']-$item->reality))/86400);
                    $item->receipts = db('call_payment')->where($m)->order('id desc')->sum('price');
                    $item->receivable = $item['total'] - $item->payment - $item->receipts;
                    $item->custom = db('call_custom')->where(['id'=>$item['custom_id']])->value('name').'【'.db('call_custom')->where(['id'=>$item['custom_id']])->value('mobile').'】';
                    // $item->timerange = $map['note_time'][1][0].'~'.$map['note_time'][1][1];
                });

        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['id','auto', 'ID'],
            ['serialNO', 'auto','合同编号'],
            ['title', 'auto','合同名'],
            ['type', 'auto','类型'],
            ['sign_time', 'auto','签约日期','date'],
            ['signcity', 'auto','地区'],
            ['note_time', 'auto','留言时间'],
            ['custom', 'auto','客户系统名'],
            ['contactMobile', 'auto','手机号'],
            ['total', 'auto','合同金额'],
            ['payment', 'auto','已付金额'],
            ['receivable', 'auto','应收余款'],
            ['should_time', 'auto','应收日期','date'],
            ['receipts','auto', '实收余款'],
            ['reality', 'auto','实际日期'],
            ['exceed', 'auto','逾期天数'],
            ['role', 'auto','部门'],
            ['menger', 'auto','负责人'],
            // ['timerange','auto', '查询时间段'],
        ];
        
        
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['合同回款', $cellName, $data_list]);
    }
    /**
     * [tradeLog 日志]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function tradeLog($id = null)
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = TradelogModel::where($map)->order('id desc')->paginate()->each(function($item, $key) use ($map){
            $item->trade = db('call_trade')->where(['id'=>$item['trade_id']])->value('title');
        });

        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('变更合同日志') // 设置页面标题
            ->hideCheckbox()
            ->addColumns([ // 批量添加列
                ['id', 'ID'],
                ['trade', '合同'],
                ['node', '日志'],
            ])
            ->setRowList($data_list) // 设置表格数据
            ->setPages($page) // 设置分页数据
            ->fetch(); // 渲染页面
    }

    /**
     * [importReport 净得率]
     * @return [type] [description]
     */
    function importReport()
    {
        
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        $data_list = CustomEXLogModel::where($map)->order('id desc')->paginate();
        
        // 分页数据
        $page = $data_list->render();
        $btnexport = [
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('importReportexport',http_build_query($this->request->param()))
        ];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setSearchArea([
                ['daterange', 'create_time', '导入日期', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],

            ])
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['title', '分批导入表名','link',url('call/custom/import'), '_blank'],
                ['rate', '净得率'],
                ['create_time', '导入日期','date'],
            ])
            ->setRowList($data_list)// 设置表格数据
            ->addTopButton('custom', $btnexport)
            ->fetch(); // 渲染模板
    }

    /**
     * [importReportexport 净得率导出]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function importReportexport()
    {
        $map = $this->getMaps();
        
        // 数据列表
        $data_list = CustomEXLogModel::where($map)->order('id desc')->paginate();

        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['id','auto', 'ID'],
            ['title', 'auto','分批导入表名'],
            ['rate', 'auto','净得率'],
            ['create_time', 'auto','导入日期','date'],
        ];
        
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['净得率报表', $cellName, $data_list]);
    }

    /**
     * [roleCallReport 部门通话报表]
     * @return [type] [description]
     */
    public function roleCallReport()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        //alloc_time timeslength alloc_sum gtback 个人
        //standard_num day_nocontanct
        //时间 部门
        // 获取查询条件
        $map = $this->getMap();

        if (!isset(plugin_config('wechat')['roleCallSetting'])) {
            $map['id']  = '';
            $data_list = CalllogModel::where($map)->paginate();
        }else{
            $roleCallSetting = intval(plugin_config('wechat')['roleCallSetting']);//强行转换
            // 数据列表
            if (isset($map['create_time'])) {
                $data = $map;
                //处理部门
                if (isset($map['role_id'])) {

                    $user_ids = db('admin_user')->where(['role'=>$map['role_id']])->column('id');
                    // $user_ids = CalllogModel::where($map)->column('user_id');
                    //部门计总
                    $standard_num = 0;
                    foreach ($user_ids as $key => $value) {
                        $m['user_id'] = $value;
                        $m['create_time'] = $map['create_time'];
                        $timeLengths = CalllogModel::where($m)->field('SUM(timeLength) as timeLengths')->find();
                        if ($timeLengths['timeLengths']>$roleCallSetting*60) {
                            $standard_num++;
                        }
                    }
                    $data['standard_num'][$map['role_id'][1]] = intval($standard_num) ;

                    //7day
                    $m3['a.create_time'] = array('gt',time()-86400*7);
                    $data['day_nocontanct'][$map['role_id'][1]] = db('call_alloc_log')->alias('a')->field('a.custom_id,a.user_id')->join(' call_log c',' c.alloc_log_id = a.id','LEFT')->where($m3)->group('a.id')->count();
                    // $map['user_id'] = array('in',array_column($user_ids, 'user_id'));
                    // unset($map['role_id']);
                }else{

                    $role_ids = CalllogModel::where($map)->column('role_id');

                    foreach ($role_ids as $key => $value) {
                        $user_ids = db('admin_user')->where(['role'=>$value])->column('id');
                        //部门计总
                        $standard_num = 0;
                        foreach ($user_ids as $key => $value) {
                            $m['user_id'] = $value;
                            $m['create_time'] = $map['create_time'];
                            $timeLengths = CalllogModel::where($m)->field('SUM(timeLength) as timeLengths')->find();
                            if ($timeLengths['timeLengths']>$roleCallSetting*60) {
                                $standard_num++;
                            }
                        }
                        $data['standard_num'][$value] = $standard_num;

                        $m3['a.create_time'] = array('gt',time()-86400*7);
                        $data['day_nocontanct'][$value] = db('call_alloc_log')->alias('a')->field('a.custom_id,a.user_id')->join(' call_log c',' c.alloc_log_id = a.id','LEFT')->where($m3)->group('a.id')->count();

                    }

                }
                $data_list = CalllogModel::where($map)->field('*,SUM(timeLength) as timeLengths,count(*) as call_count')->order('timeLengths DESC')->group('user_id')->paginate()->each(function($item, $key) use ($data){
                        unset($m1);
                        unset($m2);
                        //role alloc_time  alloc_sum gtback standard_num day_nocontanct
                        $item->user = db('admin_user')->where(['id'=>$item['user_id']])->value('nickname');
                        $item->alloc_time = date('Y-m-d H:i:s',db('call_alloc')->where(['id'=>$item['alloc_log_id']])->value('create_time')) ;

                        $m1['create_time'] = $data['create_time'];
                        $m1['user_id'] = $item['user_id'];
                        $item->alloc_sum = db('call_alloc_log')->where($m1)->count();

                        $m2['create_time'] = $data['create_time'];
                        $m2['user_id'] = $item['user_id'];
                        $m2['status'] = 2;
                        $item->gtback = db('call_alloc_log')->where($m2)->count();

                        //role_id 
                        $item->standard_num = isset($data[$item['role_id']]) ?$data[$item['role_id']]['standard_num']:0;
                        $item->day_nocontanct = isset($data[$item['role_id']]['day_nocontanct'])?$data[$item['role_id']]['day_nocontanct']:0 ;

                        $item->role = db('admin_role')->where(['id'=>$item['role_id']])->value('name');

                        $item->timeLengths = times_exchange_His($item['timeLengths']);

                    });
            }else{
                $map['id']  = '';
                $data_list = CalllogModel::where($map)->paginate();
            }
        }
        
        
        
        // 分页数据
        $page = $data_list->render();
        $btnexport = [
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('roleCallReportexport',http_build_query($this->request->param()))
        ];

        $btn_access = [
            'title' => '配置',
            'icon'  => 'fa fa-fw fa-cog ',
            'class' => 'btn btn-default ajax-get',
            'href' => url('roleCallSetting',['tag'=>'timeLength'])
        ];
        $roles = RoleModel::where(['status'=>1])->column('id,name'); 

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->setSearchArea([
                ['daterange', 'create_time', '时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                ['select', 'role_id', '部门', '', '', $roles],
            ])
            ->addColumns([ // 批量添加数据列
                ['id', 'ID'],
                ['user', '员工'],
                ['role', '部门'],
                ['alloc_time', '分配时间'],
                ['timeLengths', '通话时长'],
                ['alloc_sum', '分配数量'],
                ['gtback', '回收数量'],
                ['standard_num', '部门达标数量(部门)'],
                ['day_nocontanct', '7天未联系客户(部门)'],
                ['create_time', '呼叫时间','datetime'],
            ])
            ->setRowList($data_list)// 设置表格数据
            ->addTopButton('custom', $btnexport)
            ->addTopButton('custom', $btn_access,true)
            // ->addTopButton('custom', $btn_access)
            ->fetch(); // 渲染模板
    }

    /**
     * [setting description]
     * @param  [type] $tag [description]
     * @return [type]      [description]
     */
    public function roleCallSetting($tag)
    {

        if ($this->request->isPost()) {
            $data = $this->request->post();
            plugin_config('wechat.roleCallSetting',$data['roleCallSetting']);
            
            $this->success('配置成功', null,'_close_pop');
        }
        $info = [
            'roleCallSetting'=>isset(plugin_config('wechat')['roleCallSetting'])?plugin_config('wechat')['roleCallSetting']:''
        ];
        return ZBuilder::make('form')
                ->setPageTitle('配置') // 设置页面标题
                ->addFormItems([ // 批量添加表单项
                    ['text', 'roleCallSetting', '达标时长(分)','<code>注意单位是分钟</code>'],
                ])
                ->setFormData($info) // 设置表单数据
                ->fetch();
    }
    /**
     * [roleCallReportexport description]
     * @return [type] [description]
     */
    public function roleCallReportexport()
    {
        $map = $this->getMaps();
        
        // 数据列表
        if (!isset(plugin_config('wechat')['roleCallSetting'])) {
            $map['id']  = '';
            $data_list = CalllogModel::where($map)->paginate();
        }else{
            $roleCallSetting = intval(plugin_config('wechat')['roleCallSetting']);//强行转换
            // 数据列表
            if (isset($map['create_time'])) {
                $data = $map;
                //处理部门
                if (isset($map['role_id'])) {

                    $user_ids = db('admin_user')->where(['role'=>$map['role_id']])->column('id');
                    // $user_ids = CalllogModel::where($map)->column('user_id');
                    //部门计总
                    $standard_num = 0;
                    foreach ($user_ids as $key => $value) {
                        $m['user_id'] = $value;
                        $m['create_time'] = $map['create_time'];
                        $timeLengths = CalllogModel::where($m)->field('SUM(timeLength) as timeLengths')->find();
                        if ($timeLengths['timeLengths']>$roleCallSetting*60) {
                            $standard_num++;
                        }
                    }
                    $data['standard_num'][$map['role_id'][1]] = intval($standard_num) ;

                    //7day
                    $m3['a.create_time'] = array('gt',time()-86400*7);
                    $data['day_nocontanct'][$map['role_id'][1]] = db('call_alloc_log')->alias('a')->field('a.custom_id,a.user_id')->join(' call_log c',' c.alloc_log_id = a.id','LEFT')->where($m3)->group('a.id')->count();
                    // $map['user_id'] = array('in',array_column($user_ids, 'user_id'));
                    // unset($map['role_id']);
                }else{

                    $role_ids = CalllogModel::where($map)->column('role_id');

                    foreach ($role_ids as $key => $value) {
                        $user_ids = db('admin_user')->where(['role'=>$map['role_id'][1]])->column('user_id');
                        //部门计总
                        $standard_num = 0;
                        foreach ($user_ids as $key => $value) {
                            $m['user_id'] = $value;
                            $m['create_time'] = $map['create_time'];
                            $timeLengths = CalllogModel::where($m)->field('SUM(timeLength) as timeLengths')->find();
                            if ($timeLengths['timeLengths']>$roleCallSetting*60) {
                                $standard_num++;
                            }
                        }
                        $data['standard_num'][$value] = $standard_num;

                        $m3['a.create_time'] = array('gt',time()-86400*7);
                        $data['day_nocontanct'][$value] = db('call_alloc_log')->alias('a')->field('a.custom_id,a.user_id')->join(' call_log c',' c.alloc_log_id = a.id','LEFT')->where($m3)->group('a.id')->count();

                    }

                }
                $data_list = CalllogModel::where($map)->field('*,SUM(timeLength) as timeLengths,count(*) as call_count')->order('timeLengths DESC')->group('user_id')->paginate()->each(function($item, $key) use ($data){
                        unset($m1);
                        unset($m2);
                        //role alloc_time  alloc_sum gtback standard_num day_nocontanct
                        $item->user = db('admin_user')->where(['id'=>$item['user_id']])->value('nickname');
                        $item->alloc_time = date('Y-m-d H:i:s',db('call_alloc')->where(['id'=>$item['alloc_log_id']])->value('create_time')) ;

                        $m1['create_time'] = $data['create_time'];
                        $m1['user_id'] = $item['user_id'];
                        $item->alloc_sum = db('call_alloc_log')->where($m1)->count();

                        $m2['create_time'] = $data['create_time'];
                        $m2['user_id'] = $item['user_id'];
                        $m2['status'] = 2;
                        $item->gtback = db('call_alloc_log')->where($m2)->count();

                        //role_id 
                        $item->standard_num = isset($data[$item['role_id']]) ?$data[$item['role_id']]['standard_num']:0;
                        $item->day_nocontanct = isset($data[$item['role_id']]['day_nocontanct'])?$data[$item['role_id']]['day_nocontanct']:0 ;

                        $item->role = db('admin_role')->where(['id'=>$data['role_id'][1]])->value('name');

                        $item->timeLengths = times_exchange_His($item['timeLengths']);

                    });
            }else{
                $map['id']  = '';
                $data_list = CalllogModel::where($map)->paginate();
            }
        }

        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['id','auto', 'ID'],
            ['user', 'auto','员工'],
            ['role', 'auto','部门'],
            ['alloc_time','auto', '分配时间'],
            ['timeLengths', 'auto','通话时长'],
            ['alloc_sum', 'auto','分配数量'],
            ['gtback', 'auto','回收数量'],
            ['standard_num', 'auto','部门达标数量(部门)'],
            ['day_nocontanct','auto', '7天未联系客户(部门)'],
            ['create_time','auto', '呼叫时间','datetime'],
        ];
        
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['净得率报表', $cellName, $data_list]);
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