
DROP TABLE IF EXISTS `zg_call_adv`;
CREATE TABLE `zg_call_adv` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '公告id',
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '公告名称',
  `content` longtext COLLATE utf8_unicode_ci COMMENT '祥情',
  `create_time` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '正常 0删除',
  PRIMARY KEY (`id`),
  KEY `ind_title` (`title`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='公告表';

DROP TABLE IF EXISTS `zg_call_adv_log`;
CREATE TABLE `zg_call_adv_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '公告日志id',
  `adv_id` int(10) unsigned DEFAULT '0' COMMENT '公告id',
  `user_id` int(10) unsigned DEFAULT '0' COMMENT '员工id',
  `create_time` int(10) unsigned DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0' COMMENT '未读',
  PRIMARY KEY (`id`),
  KEY `ind_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='公告日志表';


DROP TABLE IF EXISTS `zg_call_custom`;
CREATE TABLE `zg_call_custom` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '客户id',
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '客户名称',
  `tel` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT '客户电话',
  `mobile` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT '客户手机',
  `note_time` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '记录时间',
  `note_area` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '记录地区',
  `source` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '来源',
  `extend_url` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '推广链接',
  `policy` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '政策',
  `fee` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '成本',
  `address` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '地址',
  `email` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '邮箱',
  `category` int(10) unsigned DEFAULT '0' COMMENT '类别id',
  `create_time` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '正常',
  PRIMARY KEY (`id`),
  KEY `ind_name` (`name`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='客户表';

DROP TABLE IF EXISTS `zg_call_custom_export_log`;
CREATE TABLE `zg_call_custom_export_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '客户导入日志id',
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '客户导入日志表',
  `rate` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '净得率',
  `create_time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='客户导入日志表';

DROP TABLE IF EXISTS `zg_call_porject_st`;
CREATE TABLE `zg_call_porject_st` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '项目设置id',
  `lable` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '字段名',
  `col` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '字段对应名',
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT '字段类别',
  `sort` int(10) unsigned DEFAULT '0' COMMENT '排序',
  `project_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  `create_time` int(10) unsigned DEFAULT NULL,
  `disabled` tinyint(1) DEFAULT '1' COMMENT '0失效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='项目设置表';

DROP TABLE IF EXISTS `zg_call_project`;
CREATE TABLE `zg_call_project` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '项目id',
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '项目名称',
  `describe` longtext COLLATE utf8_unicode_ci COMMENT '项目说明',
  `create_time` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '0失效',
  PRIMARY KEY (`id`),
  KEY `ind_title` (`title`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='项目表';

DROP TABLE IF EXISTS `zg_call_project_list`;
CREATE TABLE `zg_call_project_list` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '项目行记录id',
  `project_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  `col1` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col1',
  `col2` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col2',
  `col3` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col3',
  `col4` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col4',
  `col5` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col5',
  `col6` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col6',
  `col7` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col7',
  `col8` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col8',
  `col9` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col9',
  `col10` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col10',
  `col11` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col11',
  `col12` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col12',
  `col13` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col13',
  `col14` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col14',
  `col15` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col15',
  `col16` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col16',
  `col17` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col17',
  `col18` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col18',
  `col19` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col19',
  `col20` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col20',
  `col21` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col21',
  `col22` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col22',
  `col23` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col23',
  `col24` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col24',
  `col25` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col25',
  `col26` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col26',
  `col27` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col27',
  `col28` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col28',
  `col29` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col29',
  `col30` varchar(250) COLLATE utf8_unicode_ci NOT NULL COMMENT 'col30',
  `create_time` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '0失效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='项目行记录表';

DROP TABLE IF EXISTS `zg_call_alloc`;
CREATE TABLE `zg_call_alloc` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分配id',
  `op_id` int(10) unsigned DEFAULT '0' COMMENT '操作员id',
  `call_count` int(10) unsigned DEFAULT '0' COMMENT '呼叫次数',
  `alloc_count` int(10) unsigned DEFAULT '0' COMMENT '分配次数',
  `create_time` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '0失效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='分配表';

DROP TABLE IF EXISTS `zg_call_alloc_log`;
CREATE TABLE `zg_call_alloc_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分配日志id',
  `alloc_id` int(10) unsigned DEFAULT '0' COMMENT '分配id',
  `user_id` int(10) unsigned DEFAULT '0' COMMENT '用户id',
  `custom_id` int(10) unsigned DEFAULT '0' COMMENT '客户id',
  `call_count` int(10) unsigned DEFAULT '0' COMMENT '呼叫次数',
  `create_time` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '0失效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='分配日志表';


DROP TABLE IF EXISTS `zg_call_recover_data`;
CREATE TABLE `zg_call_recover_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '品牌id',
  `project_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  `custom_id` int(10) unsigned DEFAULT '0' COMMENT '客户id',
  `create_time` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '9公海1回收',
  PRIMARY KEY (`id`),
  KEY `ind_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='客户回收表';


DROP TABLE IF EXISTS `zg_call_speechcraft`;
CREATE TABLE `zg_call_speechcraft` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '话术id',
  `project_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '名称',
  `order` int(10) unsigned DEFAULT '0' COMMENT '排序',
  `content` longtext COLLATE utf8_unicode_ci COMMENT '内容',
  `create_time` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '0失效',
  PRIMARY KEY (`id`),
  KEY `ind_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='话术表';

DROP TABLE IF EXISTS `zg_call_item`;
CREATE TABLE `zg_call_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '商品id',
  `project_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '名称',
  `unit` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '单位',
  `price` decimal(20,3) NOT NULL COMMENT '商品价格',
  `note` longtext COLLATE utf8_unicode_ci COMMENT '备注',
  `create_time` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '0失效',
  PRIMARY KEY (`id`),
  KEY `ind_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='商品表';

DROP TABLE IF EXISTS `zg_call_trade`;
CREATE TABLE `zg_call_trade` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '话术id',
  `project_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  `title` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '名称',
  `total` decimal(20,3) NOT NULL COMMENT '总金额',
  `payment` decimal(20,3) NOT NULL COMMENT '支付金额',
  `brokerage` decimal(20,3) NOT NULL COMMENT '提成',
  `surplus` decimal(20,3) NOT NULL COMMENT '余额',  
  `note` longtext COLLATE utf8_unicode_ci COMMENT '备注',
  `sign_area_city` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '城市',
  `sign_area_area` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '区',
  `sign_area_province` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '省',
  `sign_time` int(10) unsigned DEFAULT NULL,
  `create_time` int(10) unsigned DEFAULT NULL,
  `status` varchar(20) DEFAULT 'progress' COMMENT 'progress 进行finsh完成 end人工结束',
  PRIMARY KEY (`id`),
  KEY `ind_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='商品表';

DROP TABLE IF EXISTS `zg_call_order`;
CREATE TABLE `zg_call_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单明细id',
  `trade_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  `item_id` int(10) unsigned DEFAULT '0' COMMENT '商品id',
  `price` decimal(20,3) NOT NULL COMMENT '商品价格',
  `num` decimal(20,3) NOT NULL COMMENT '数量',
  `create_time` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '0失效',
  PRIMARY KEY (`id`),
  KEY `ind_trade_id` (`trade_id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='订单明细表';

DROP TABLE IF EXISTS `zg_call_payment`;
CREATE TABLE `zg_call_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单回款id',
  `trade_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  `price` decimal(20,3) NOT NULL COMMENT '回款金额',
  `type` tinyint(1) DEFAULT '1' COMMENT '1订金 2货款 3尾款',
  `brank_account` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '名称',
  `is_notice` tinyint(1) DEFAULT '0' COMMENT '1已提醒',
  `notice_time` int(10) unsigned DEFAULT NULL,
  `sign_time` int(10) unsigned DEFAULT NULL,
  `create_time` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '0失效',
  PRIMARY KEY (`id`),
  KEY `ind_trade_id` (`trade_id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='订单回款表';

DROP TABLE IF EXISTS `zg_call_ondate`;
CREATE TABLE `zg_call_ondate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单回款id',
  `custom_id` int(10) unsigned DEFAULT '0' COMMENT '项目id',
  `ondate` int(10) unsigned DEFAULT NULL,
  `sign_time` int(10) unsigned DEFAULT NULL,
  `create_time` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0' COMMENT '0待回1已回',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='预约表';


DROP TABLE IF EXISTS `zg_call_auth`;
CREATE TABLE `zg_call_auth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '授权id',
  `custom` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '客户名称',
  `domain` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '域名',
  `ip` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '服务器IP',
  `online` tinyint(1) DEFAULT '1' COMMENT '0线下',
  `start_time` int(10) unsigned DEFAULT NULL,
  `end_time` int(10) unsigned DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1' COMMENT '0失效',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='授权表';
