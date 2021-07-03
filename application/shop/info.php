<?php

/**
 * 模块信息
 */
return [
  'name' => 'shop',
  'title' => '商城模块',
  'identifier' => 'shop.zxgbuynow.module',
  'icon' => 'fa fa-fw fa-shopping-bag',
  'description' => '商城',
  'author' => '周工',
  'author_url' => 'https://my.oschina.net/u/3144895',
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
