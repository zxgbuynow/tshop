<?php
namespace app\call\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\call\model\Reportcat as ReportcatModel;
use app\call\model\Calllog as CalllogModel;
use app\call\model\Custom as CustomModel;
use app\user\model\User as UserModel;
use think\Db;

/**
 * 首页后台控制器
 */
class Statistics extends Admin
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
        if (!$map) {
            $map['id'] = '';//过滤所有数据
        }

        if (isset($map['note_time'])) {
            //总推广费用
            $totalfee = CustomModel::where($map)->sum('fee');
            //签单量
            $map1['category'] = 6;
            $map1['create_time'] = $map['note_time'];
            $signtots = db('call_report_custom_cat')->where($map1)->group('custom_id')->count();

            $custom_ids = db('call_report_custom_cat')->where($map1)->column('custom_id');
            foreach ($custom_ids as $key => $value) {
                if (!db('call_custom')->where(['category'=>6,'id'=>$value])->find()) {
                    unset($custom_ids[$key]);
                    $signtots--;
                }
            }

            //当期签单量
            $map2['note_time'] = $map['note_time'];
            $map2['id'] = array('in',$custom_ids);
            $signcurrs = CustomModel::where($map2)->count();
            //往期追回签单量
            $map3['note_time'][0] = '< time';
            $map3['note_time'][1] = $map['note_time'][1][0];
            $map3['id'] = array('in',$custom_ids);
            $signfogs = CustomModel::where($map3)->count();
            //当期1单成单成本
            $costcurr = number_format($totalfee/$signcurrs,1); 
            //当期+往期成单成本
            $cost = number_format($totalfee/$signtots,1);
            // //数据
            $data_list[0]['id'] = 1;
            $data_list[0]['cost'] = $cost;
            $data_list[0]['costcurr'] = $costcurr;
            $data_list[0]['signfogs'] = $signfogs;
            $data_list[0]['signcurrs'] = $signcurrs;
            $data_list[0]['signtots'] = $signtots;
            $data_list[0]['totalfee'] = $totalfee;
        }else{
            $data_list = [];
        }
        
        // 分页数据
        // $page = $data_list->render();

        if (isset($map['note_time'])) {
            $fee_btn = [
                'title' => '推广费用明细',
                'icon'  => 'fa fa-fw fa-file-excel-o ',
                'href' => url('feeDeail',['note_time'=>json_encode($map['note_time'])])
            ];

            $employ_btn = [
                'title' => '签单客户明细',
                'icon'  => 'fa fa-fw fa-user ',
                'href' => url('custDeail',['note_time'=>json_encode($map['note_time'])])
            ];
        }else{
            $fee_btn = [
                'title' => '推广费用明细',
                'icon'  => 'fa fa-fw fa-file-excel-o ',
                'href' => url('feeDeail')
            ];

            $employ_btn = [
                'title' => '签单客户明细',
                'icon'  => 'fa fa-fw fa-user ',
                'href' => url('custDeail')
            ];
        }
        
        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('export',http_build_query($this->request->param()))
        ];
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearchArea([
                ['daterange', 'note_time', '时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],

            ])
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['totalfee', '总推广费用'],
                ['signtots', '签单量'],
                ['signcurrs', '当期签单量'],
                ['signfogs', '往期追回签单量'],
                ['costcurr', '当期1单成单成本'],
                ['cost', '当期+往期成单成本'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButton('custom',$fee_btn)
            ->addRightButton('custom',$employ_btn)
            ->setRowList($data_list)// 设置表格数据
            ->addTopButton('custom', $btnexport)
            ->fetch(); // 渲染模板
        
    }

    /**
     * [classFweekReportexport 导出]
     * @return [type] [description]
     */
    public function export()
    {
        $map = $this->getMaps();
        //查询数据

       if (isset($map['note_time'])) {
            //总推广费用
            $totalfee = CustomModel::where($map)->sum('fee');
            //签单量
            $map1['category'] = 6;
            $map1['create_time'] = $map['note_time'];
            $signtots = db('call_report_custom_cat')->where($map1)->group('custom_id')->count();

            $custom_ids = db('call_report_custom_cat')->where($map1)->column('custom_id');
            foreach ($custom_ids as $key => $value) {
                if (!db('call_custom')->where(['category'=>6,'id'=>$value])->find()) {
                    unset($custom_ids[$key]);
                    $signtots--;
                }
            }

            //当期签单量
            $map2['note_time'] = $map['note_time'];
            $map2['id'] = array('in',$custom_ids);
            $signcurrs = CustomModel::where($map2)->count();
            //往期追回签单量
            $map3['note_time'][0] = '< time';
            $map3['note_time'][1] = $map['note_time'][1][0];
            $map3['id'] = array('in',$custom_ids);
            $signfogs = CustomModel::where($map3)->count();
            //当期1单成单成本
            $costcurr = number_format($totalfee/$signcurrs,1); 
            //当期+往期成单成本
            $cost = number_format($totalfee/$signtots,1);
            // //数据
            $data_list[0]['id'] = 1;
            $data_list[0]['cost'] = $cost;
            $data_list[0]['costcurr'] = $costcurr;
            $data_list[0]['signfogs'] = $signfogs;
            $data_list[0]['signcurrs'] = $signcurrs;
            $data_list[0]['signtots'] = $signtots;
            $data_list[0]['totalfee'] = $totalfee;
            $data_list[0]['timerange'] = $map['note_time'][1][0].'~'.$map['note_time'][1][1];
        }else{
            $data_list = [];
        }

        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['totalfee', 'auto','总推广费用'],
            ['signtots', 'auto','签单量'],
            ['signcurrs','auto', '当期签单量'],
            ['signfogs', 'auto','往期追回签单量'],
            ['costcurr', 'auto','当期1单成单成本'],
            ['cost', 'auto','当期+往期成单成本'],
            // ['timerange','auto', '查询时间段'],
        ];
        
        
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['开单成本统计', $cellName, $data_list]);
    }
    /**
     * [feeDeail 费用明细]
     * @return [type] [description]
     */
    public function feeDeail($note_time = null)
    {
        if ($note_time === null) $this->error('缺少参数');

        //平台 加 费用
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表        
        if ($note_time) {
            $map['note_time'] = json_decode(str_replace('+',' ',urldecode($note_time)));
            $data_list = CustomModel::where($map)->field('*,count(*) as counts,sum(fee) as total')->order('total DESC')->group('source')->paginate();
        }else{
            $map['id'] = '';//过滤数据
            $data_list = CustomModel::where($map)->field('*,count(*) as counts')->order('counts DESC')->group('project_id,source')->paginate();
        }
        
        
        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->addTopButton('back', [
                'title' => '返回列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('index')
            ])
            ->addColumns([ // 批量添加数据列
                ['source', '客户来源'],
                ['counts', '数据量'],
                ['total', '推广费用'],
            ])
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板

    }

    /**
     * [custDeail 客户明细]
     * @return [type] [description]
     */
    public function custDeail($note_time = null )
    {
        if ($note_time === null) $this->error('缺少参数');

        //平台 加 费用
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表        
        if ($note_time) {
            $map['note_time'] = json_decode(str_replace('+',' ',urldecode($note_time)));

            $map1['category'] = 6;
            $map1['create_time'] = $map['note_time'];
            $custom_ids = db('call_report_custom_cat')->where($map1)->order('id desc')->column('custom_id');
            foreach ($custom_ids as $key => $value) {
                if (!db('call_custom')->where(['category'=>6,'id'=>$value])->find()) {
                    unset($custom_ids[$key]);
                }
            }

            $map['id'] = array('in',$custom_ids);
            $data_list = CustomModel::where($map)->paginate();
        }else{
            $map['id'] = '';//过滤数据
            $data_list = CustomModel::where($map)->paginate();
        }
        
        
        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->hideCheckbox()
            ->addTopButton('back', [
                'title' => '返回列表',
                'icon'  => 'fa fa-reply',
                'href'  => url('index')
            ])
            ->addColumns([ // 批量添加数据列
                ['name', '客户名称'],
                ['tel', '客户电话'],
                ['mobile', '客户手机'],
                ['source', '来源'],
                ['email', '邮箱'],
                ['address', '地址'],
                ['note_time', '记录时间'],
                ['note_area', '记录地区'],
                ['fee', '成本'],
                ['extend_url', '推广链接'],
            ])
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }
    
    /**
     * [feeStatistics 所有类型客户成本]
     * @return [type] [description]
     */
    public function feeStatistics()
    {
        // 获取查询条件
        $map = $this->getMap();

        // 数据列表
        
        if (!$map) {
            $map['id'] = '';//过滤所有数据
        }
        $data_list = CustomModel::where($map)->field('*,avg(fee) as fees')->order('fees DESC')->group('category')->paginate()->each(function($item, $key) use ($map){
            $item->categorys = db('call_custom_cat')->where(['id'=>$item['category']])->value('title');
            $item->project = db('call_project_list')->where(['id'=>$item['project_id']])->value('col1');
        });
        
        // 分页数据
        $page = $data_list->render();

        $btnexport = [
            // 'class' => 'btn btn-info',
            'title' => '导出',
            'icon'  => 'fa fa-fw fa-file-excel-o',
            'href'  => url('feeexport',http_build_query($this->request->param()))
        ];
        $sources = db('call_custom')->column('source');
        foreach ($sources as $key => $value) {
            $list_source[$value] = $value;
        }
        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setSearchArea([
                ['daterange', 'note_time', '留言时间', '', '', ['format' => 'YYYY-MM-DD HH:mm:ss', 'time-picker' => 'true', 'time' => 'true', 'time' => 'true']],
                ['select', 'source', '平台来源', '', '', $list_source],

            ])
            ->hideCheckbox()
            ->addColumns([ // 批量添加数据列
                ['categorys', '客户分类'],
                ['project', '项目'],
                ['source', '客户来源'],
                ['fees', '成本'],
            ])
            ->addTopButton('custom', $btnexport)
            ->setRowList($data_list)// 设置表格数据
            ->fetch(); // 渲染模板
    }

    /**
     * [feeexport 导出]
     * @return [type] [description]
     */
    public function feeexport()
    {
        $map = $this->getMaps();

        //查询数据

        $data_list = CustomModel::where($map)->field('*,avg(fee) as fees')->order('fees DESC')->group('category')->paginate()->each(function($item, $key) use ($map){
            $item->categorys = db('call_custom_cat')->where(['id'=>$item['category']])->value('title');
            $item->project = db('call_project_list')->where(['id'=>$item['project_id']])->value('col1');
        });

        
        // 设置表头信息（对应字段名,宽度，显示表头名称）
        $cellName = [
            ['categorys','auto', '客户分类'],
            ['project','auto', '项目'],
            ['source', 'auto','客户来源'],
            ['fees','auto', '成本'],
        ];
        
        
        // 调用插件（传入插件名，[导出文件名、表头信息、具体数据]）
        plugin_action('Excel/Excel/export', ['所有类型客户成本', $cellName, $data_list]);
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