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
class Deposit extends Admin
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

            if (db('deposit_set')->find()) {
                $data['id'] = 1;
                db('deposit_set')->update($data);
                $this->success('编辑成功');
            } else {
                db('deposit_set')->insert($data);
                $this->success('添加成功');
            }
        }
        $info = db('deposit_set')->find();

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems([
            ])
            ->addText('onlinestage', '网络平台分配比例', '', '', ['', '%'])
            ->addText('onlineshop', '网络机构分配比例', '', '', ['', '%'])
            ->addText('onlinecounsoller', '网络咨询师分配比例', '', '', ['', '%'])

            ->addText('offlinestage', '地面平台分配比例', '', '', ['', '%'])
            ->addText('offlineshop', '地面机构分配比例', '', '', ['', '%'])
            ->addText('offlinecounsoller', '地面咨询师分配比例', '', '', ['', '%'])

            ->addText('clacstage', '课程活动平台分配比例', '<code>平台抽成比例</code>', '', ['', '%'])
            ->addText('clacshop', '课程活动机构分配比例', '<code>平台抽成剩余*比例（80%*20%）</code>', '', ['', '%'])
            ->addText('claccounsoller', '课程活动咨询师分配比例', '<code>平台抽成剩余*比例（80%*80%）</code>', '', ['', '%'])

            ->addText('cladmin', '课程活动管理员分配比例', '', '', ['', '%'])
            ->addText('clactutor', '课程活动输导员分配比例', '', '', ['', '%'])
            ->addText('clactearch', '课程活动上课老师分配比例', '', '', ['', '%'])

            ->setFormData($info)
            ->hideBtn('back')
            ->fetch();
    }

}