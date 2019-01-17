<?php

namespace app\shop\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\shop\model\Item as ItemModel;
use app\shop\model\Cat as CatModel;
use app\shop\model\Brand as BrandModel;
use app\shop\model\Props as PropsModel;
use app\shop\model\CatRelBrands as CatRelBrandsModel;
use app\shop\model\CatRelProps as CatRelPropsModel;
use util\Tree;
use think\Db;
use think\Hook;

/**
 * 商品列表
 */
class Item extends Admin
{
    // 定时任务列表
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        // 获取查询条件
        $map = $this->getMap();

        // 获取排序
        $default_order = input('?param._order/s') ? '' : ' item_id DESC';
        $order = $this->getOrder($default_order);

        // 数据列表
        $data_list = ItemModel::where($map)->paginate();

        // 分页数据
        $page = $data_list->render();

        // 使用ZBuilder快速创建数据表格
        return ZBuilder::make('table')
            ->setPageTitle('商品列表')// 设置页面标题
            ->setTableName('item')// 设置数据表名
            ->setSearch(['item_id' => 'ID', 'title' => '标题', 'type'=>'类型'])// 设置搜索参数
            ->addOrder('item_id,is_offline')// 添加排序
            ->addColumns([ // 批量添加列
                ['item_id', 'ID'],
                ['bn', '商品编码'],
                ['title', '商品标题'],
                ['cat', '商品类目名称'],
                ['brand', '商品品牌'],
                ['price', '商品价格'],
                ['is_offline', '上架', 'status', '', ['0' => '下架:danger', '1' => '上架:success']],
                ['store', '库存'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete')// 批量添加顶部按钮
            ->addRightButtons('edit,enable,disable,delete')// 批量添加右侧按钮
            ->setRowList($data_list)// 设置表格数据
            ->setPages($page)// 设置分页数据
            ->raw('cat,brand,store') // 使用原值
            ->fetch(); // 渲染页面
    }

    // 添加
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            // 验证
            $result = $this->validate($data, 'Item');

            if(true !== $result) $this->error($result);
            $data['created_time'] = time();
            $data['image_default_id'] = explode(',', $data['list_image'])[0];
            print_r($data);exit;
            if ($Item = ItemModel::create($data)) {
                // 记录行为
                action_log('item_add', 'Item', $Itme['id'], UID, $data['title']);
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }
        $spec_url = url('spec');

        $js = <<<EOF
            <script type="text/javascript">
                function checkSchedule(cid) {
                    $.post("{$spec_url}", { "cat_id": cid },
                    function(data){
                        console.log(data);
                        
                        if (data.code == 1){
                            var html = '';
                            
                            $('#speclist').html(html);
                        }

                    }, "json");
                }
            
                $(function(){
                    $("#cat_id").change(function(){
                        var cid = $(this).children('option:selected').val();
                        checkSchedule(cid);
                    });
                });
            </script>
EOF;
        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['linkage', 'cat_id', '父类', '<code>必选</code>', CatModel::getTreeList(0),'',url('get_brands'), 'brand_id,spec_desc'],
                ['text', 'title', '商品标题'],
                ['textarea', 'sub_title', '商品子标题'],
                ['text', 'unit', '计价单位'],
                ['text', 'bn', '编码','',get_bn()],
                ['select', 'brand_id', '品牌'],
                ['images', 'list_image', '商品图片' ,'', '', '', '', '', ['size' => '80,80']],
                ['text', 'order_sort', '排序'],
                ['radio', 'is_offline', '上架状态', '', ['否', '是'], 1],
                ['radio', 'nospec', '单品/多规格', '', ['单品', '多规格'], 0],
                ['mcheckbox', 'spec_desc', '属性'],
                ['mtable', 'sku', '商品规格(sku)']
            ])
            ->setTrigger('nospec','1','sku')
            ->setTrigger('nospec','1','spec_desc')
            ->setExtraJs($js)
            ->fetch();
        
    }

    public function get_brands($cat_id=null)
    {
        $arr['code'] = '1'; //判断状态
        $arr['msg'] = '请求成功'; //回传信息
        //list
        $b = db('cat_rel_brands')->where(['cat_id'=>$cat_id])->value('brand_id');
        if (!$b) {
            $arr['list'] = []; //数据
            return json($arr);
        }
        $m['id'] = array('in',$b); 
        $l = db('brand')->where($m)->select();
        $rs = [];
        foreach ($l as $key => $value) {
            $rs[$key]['key']=$value['id'];
            $rs[$key]['value']=$value['brand_name'];
        }
        $arr['list'] = $rs;
        // $arr['list'] = [
        //     ['key' => '0', 'value' => '广州'],
        //     ['key' => '1', 'value' => '深圳'],
        // ]; //数据

        return json($arr);
    }

    public function spec($cat_id=null)
    {
        $arr['code'] = '1'; //判断状态
        $arr['msg'] = '请求成功'; //回传信息
        //list
        $b = db('cat_rel_props')->where(['cat_id'=>$cat_id])->value('prop_id');
        if (!$b) {
            $arr['list'] = []; //数据
            return json($arr);
        }
        $m['id'] = array('in',$b); 
        $l = db('props')->where($m)->select();
        $rs = [];
        foreach ($l as $key => $value) {
            $rs[$key]['key']=$value['id'];
            $rs[$key]['value']=$value['prop_name'];
        }
        $arr['list'] = $rs;


        return json($arr);
    }

    // // 编辑
    // public function edit($id = null)
    // {
        

    // }

    // // 禁用
    // public function disable($record = [])
    // {
    //     $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
    //     if (empty($ids) || is_array($ids) && count($ids) < 1) return $this->error('缺少参数');

    //     $CrontabModel = new CrontabModel();
    //     $result = $CrontabModel->save(
    //         ['status' => 'disable'],
    //         ['id' => ['in', $ids]]
    //     );
    //     if (false !== $result) {
    //         return $this->success('操作成功');
    //     } else {
    //         return $this->error('操作失败');
    //     }
    // }

    // // 启用
    // public function enable($record = [])
    // {
    //     $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
    //     if (empty($ids) || is_array($ids) && count($ids) < 1) return $this->error('缺少参数');

    //     $CrontabModel = new CrontabModel();
    //     $result = $CrontabModel->save(
    //         ['status' => 'normal'],
    //         ['id' => ['in', $ids]]
    //     );
    //     if (false !== $result) {
    //         return $this->success('操作成功');
    //     } else {
    //         return $this->error('操作失败');
    //     }
    // }


}