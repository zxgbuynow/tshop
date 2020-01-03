<?php
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

/**
 * 菜单信息
 */
return [
  [
    'title' => '呼叫',
    'icon' => 'fa fa-fw fa-tel-bag',
    'url_type' => 'module_admin',
    'url_value' => 'call/home/index',
    'url_target' => '_self',
    'online_hide' => 0,
    'sort' => 8,
    'status' => 1,
    'child' => [
      [
        'title' => '首页',
        'icon' => 'fa fa-fw fa-list',
        'url_type' => 'module_admin',
        'url_value' => 'call/home/index',
        'url_target' => '_self',
        'online_hide' => 0,
        'sort' => 100,
        'status' => 1,
        'child' => [
        ]
      ],
      
      
    ],
  ],
];
