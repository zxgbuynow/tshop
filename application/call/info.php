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
 * 模块信息
 */
return [
  'name' => 'call',
  'title' => '呼叫系统',
  'identifier' => 'call.zxgbuynow.module',
  'icon' => 'fa fa-fw fa-call-bag',
  'description' => '呼叫',
  'author' => '周工',
  'author_url' => 'https://my.oschina.net/u/3144895',
  'version' => '1.0.0',
  'tables' => [
    'call_adv',
    'call_adv_log',
    'call_custom',
    'call_custom_export_log',
    'call_porject_st',
    'call_project',
    'call_project_list',
    'call_alloc',
    'call_alloc_log',
    'call_recover_data',
    'call_speechcraft',
    'call_item',
    'call_trade',
    'call_order',
    'call_payment',
    'call_ondate',
    'call_auth',
  ],
  'database_prefix' => 'zg_',
];
