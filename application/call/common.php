<?php

// 门户模块公共函数库
use think\Db;

if (!function_exists('get_bn')) {
    /**
     * get_bn
     * @author zg
     * @return string
     */
    function get_bn()
    {
        $strs="QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";

        return substr(str_shuffle($strs),mt_rand(0,strlen($strs)-11),10);
    }
}
