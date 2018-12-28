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
  'name' => 'shop',
  'title' => '商城模块',
  'identifier' => 'shop.zxgbuynow.module',
  'icon' => 'fa fa-fw fa-shopping-bag',
  'description' => '商城',
  'author' => '睡眼朦胧中',
  'author_url' => 'http://www.dolphinphp.com/',
  'version' => '1.0.0',
  'tables' => [
    'item',
    'props',
    'props_values',
    'store',
    'sku',
    'sku_store',
    'cat',
    'brand'
  ],
  'database_prefix' => 'zg_',
];
