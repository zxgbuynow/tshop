<?php


namespace app\cms\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\cms\model\Classes as ClassModel;
use app\cms\model\Classestemp as ClassestempModel;
use app\cms\model\Category as CategoryModel;
use app\cms\model\Agency as AgencyModel;
use util\Tree;
use think\Db;

/**
 * 属性控制器
 * @package app\cms\admin
 */
class Couponset extends Admin
{
    /**
     * 菜单列表
     * @return mixed
     */
    public function index()
    {
        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();

            if (db('cms_coupon_set')->find()) {
                $data['id'] = 1;
                db('cms_coupon_set')->update($data);
                $this->success('编辑成功');
            } else {
                db('cms_coupon_set')->insert($data);
                $this->success('添加成功');
            }
        }
        $info = db('cms_coupon_set')->find();

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
                ['number', 'min', '最小面值'],
                ['number', 'max', '最大面值'],
            ])
            ->addStatic('name', '每日分享次数','','1')
            ->setFormData($info)
            ->fetch();
    }

}