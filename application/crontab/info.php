<?php

/**
 * 模块信息
 */
return [
  'name' => 'crontab',
  'title' => '定时任务',
  'identifier' => 'crontab.meishixiu.module',
  'icon' => 'glyphicon glyphicon-time',
  'description' => '模块依赖 composer 组件 <code>mtdowling/cron-expression</code> 和 <code>guzzlehttp/guzzle</code>',
  'author' => '周工',
  'author_url' => '',
  'version' => '1.0.0',
  'tables' => [
    'crontab',
    'crontab_log',
  ],
  'database_prefix' => 'msx_',
];
