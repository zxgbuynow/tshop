<?php


namespace app\cms\model;

use think\Model as ThinkModel;

/**
 * 菜单模型
 * @package app\cms\model
 */
class Classestemp extends ThinkModel
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__CMS_CLAC_TEMP__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    
    public function updateclid($data)
    {   
        $map['type'] = $data['type'];
        $map['classid'] = $data['classid'];
        db('cms_clac_temp')->where($map)->update($data);
    }

    
}