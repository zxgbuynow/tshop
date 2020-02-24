<?php


namespace app\call\admin;

use app\common\controller\Common;
use think\Cache;
use think\Db;

/**
 * 用于处理ajax请求的控制器
 * @package app\call\controller
 */
class Ajax extends Common
{
    /**
     * [getEextensionSatatus 查看分机状态并签入]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getEextensionSt($id)
    {
        //查询分机状态
        if (!$id) {
            return json(['code' => 0, 'msg' => '缺少分机号']);
        }
        $params['exten'] = db('call_extension')->where(['id'=>$id])->value('title');
        $status = ring_up_new('getOneStatus',$params);
        $ret = json_decode($status,true);
        if ($ret['status']==0) {
            return json(['code' => 0, 'msg' => $ret['msg']]);
        }
        if ($ret['status']==true&&!isset($ret['data'])) {
            return json(['code' => 0, 'msg' => $ret['msg'] ]);
        }
        //签入日志
        $s['user_id'] = is_signin();
        $s['extension_id'] = $id;
        $s['create_time'] = time();
        $s['extension'] = $params['exten'];
        db('call_extension_log')->insert($s);
        $result = [
            'code' => 1,
            'msg'  => '请求成功',
            'list' => ['id'=>$params['exten'],'uid'=>$s['user_id']]
        ];

        //登录
        $auth['uid'] = is_signin();
        $auth['exten'] = $params['exten'];
        session('user_auth_extension', $auth);

        return json($result);
    }
    /**
     * 获取联动数据
     * @param string $table 表名
     * @param int $pid 父级ID
     * @param string $key 下拉选项的值
     * @param string $option 下拉选项的名称
     * @param string $pidkey 父级id字段名
     * @author zg
     * @return \think\response\Json
     */
    public function getLevelData($table = '', $pid = 0, $key = 'id', $option = 'name', $pidkey = 'pid')
    {
        if ($table == '') {
            return json(['code' => 0, 'msg' => '缺少表名']);
        }

        $data_list = Db::name($table)->where($pidkey, $pid)->column($option, $key);

        if ($data_list === false) {
            return json(['code' => 0, 'msg' => '查询失败']);
        }

        if ($data_list) {
            $result = [
                'code' => 1,
                'msg'  => '请求成功',
                'list' => format_linkage($data_list)
            ];
            return json($result);
        } else {
            return json(['code' => 0, 'msg' => '查询不到数据']);
        }
    }

    
}