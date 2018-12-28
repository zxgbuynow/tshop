
-- -----------------------------
-- 表结构 `zg_item`
-- -----------------------------
DROP TABLE IF EXISTS `zg_item`;
CREATE TABLE `zg_item` (
  `item_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'item_id',
  `cat_id` int(10) unsigned NOT NULL COMMENT '商品类目ID',
  `brand_id` int(10) unsigned NOT NULL COMMENT '品牌',
  `title` varchar(90) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '商品标题',
  `sub_title` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '商品子标题',
  `bn` varchar(45) COLLATE utf8_unicode_ci NOT NULL COMMENT 'bn',
  `price` decimal(20,3) NOT NULL COMMENT '商品价格',
  `cost_price` decimal(20,3) DEFAULT NULL COMMENT '商品成本价格',
  `mkt_price` decimal(20,3) NOT NULL COMMENT '商品市场价格',
  `weight` decimal(20,3) NOT NULL DEFAULT '0.000' COMMENT '商品重量',
  `unit` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '件' COMMENT '计价单位',
  `image_default_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '商品默认图',
  `list_image` longtext COLLATE utf8_unicode_ci COMMENT '商品图片',
  `order_sort` int(10) unsigned DEFAULT '0' COMMENT '排序',
  `created_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `modified_time` int(10) unsigned NOT NULL COMMENT '最后更新时间',
  `is_timing` tinyint(1) DEFAULT '0' COMMENT '是否定时上下架',
  `nospec` tinyint(1) NOT NULL DEFAULT '0',
  `spec_desc` longtext COLLATE utf8_unicode_ci COMMENT '销售属性序列化',
  `props_name` longtext COLLATE utf8_unicode_ci COMMENT '商品属性',
  `is_offline` tinyint(1) DEFAULT '0' COMMENT '是否是线下商品',
  `barcode` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '商品级别的条形码',
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4  COMMENT='商品表';

DROP TABLE IF EXISTS `zg_store`;
CREATE TABLE `zg_store` (
  `item_id` int(10) unsigned NOT NULL COMMENT '商品 ID',
  `store` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '总商品数量',
  `freez` int(10) unsigned DEFAULT '0' COMMENT 'sku预占库存总和',
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4  COMMENT='商品库存表';

DROP TABLE IF EXISTS `zg_sku`;
CREATE TABLE `zg_sku` (
  `sku_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'sku_id',
  `item_id` int(10) unsigned NOT NULL COMMENT '商品id',
  `title` varchar(90) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '商品标题',
  `bn` varchar(30) COLLATE utf8_unicode_ci NOT NULL COMMENT '商品编号',
  `price` decimal(20,3) NOT NULL COMMENT '商品价格',
  `cost_price` decimal(20,3) DEFAULT '0.000' COMMENT '成本价',
  `mkt_price` decimal(20,3) DEFAULT '0.000' COMMENT '原价',
  `barcode` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '条形码',
  `weight` decimal(20,3) NOT NULL DEFAULT '0.000' COMMENT '商品重量',
  `created_time` int(10) unsigned DEFAULT NULL COMMENT '创建时间',
  `modified_time` int(10) unsigned DEFAULT NULL COMMENT '最后更新时间',
  `spec_key` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '销售属性值组成的key(从小到大用排序并用“_”连接，无规格则为“0”)，方便辨识是哪个SKU',
  `spec_info` longtext COLLATE utf8_unicode_ci COMMENT '物品描述',
  `spec_desc` longtext COLLATE utf8_unicode_ci,
  `status` varchar(6) COLLATE utf8_unicode_ci DEFAULT 'normal' COMMENT 'sku状态',
  `shop_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `image_default_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '商品默认图',
  `cat_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商品类目ID',
  `brand_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '品牌',
  PRIMARY KEY (`sku_id`),
  KEY `ind_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4  COMMENT='SKU表';

DROP TABLE IF EXISTS `zg_sku_store`;
CREATE TABLE `zg_sku_store` (
  `item_id` int(10) unsigned NOT NULL COMMENT '商品 ID',
  `sku_id` int(10) unsigned NOT NULL COMMENT 'sku ID',
  `store` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商品数量',
  `freez` int(10) unsigned DEFAULT '0' COMMENT 'sku预占库存',
  PRIMARY KEY (`item_id`,`sku_id`),
  KEY `ind_sku_id` (`sku_id`),
  KEY `ind_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4  COMMENT='SKU库存表';

DROP TABLE IF EXISTS `zg_cat`;
CREATE TABLE `zg_cat` (
  `cat_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `parent_id` int(10) unsigned DEFAULT NULL COMMENT '分类父级ID',
  `cat_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `cat_logo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '一级分类logo',
  `cat_path` varchar(100) COLLATE utf8_unicode_ci DEFAULT ',' COMMENT '分类路径(从根至本结点的路径,逗号分隔,首部有逗号)',
  `level` varchar(1) COLLATE utf8_unicode_ci DEFAULT '1',
  `is_leaf` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否叶子结点（true：是；false：否）',
  `disabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否屏蔽（true：是；false：否）',
  `child_count` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '子类别数量',
  `params` longtext COLLATE utf8_unicode_ci COMMENT '参数表结构(序列化) array(参数组名=>array(参数名1=>别名1|别名2,参数名2=>别名1|别名2))',
  `order_sort` int(10) unsigned DEFAULT '0',
  `modified_time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`cat_id`),
  KEY `ind_cat_path` (`cat_path`),
  KEY `ind_cat_name` (`cat_name`),
  KEY `ind_modified_time` (`modified_time`),
  KEY `ind_ordersort` (`order_sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4  COMMENT='分类表';

DROP TABLE IF EXISTS `zg_props`;
CREATE TABLE `zg_props` (
  `prop_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '属性id',
  `prop_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `search` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'select',
  `show` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '是否显示',
  `is_def` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否系统默认属性',
  `show_type` varchar(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'text',
  `prop_type` varchar(6) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'spec',
  `prop_memo` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `order_sort` int(10) unsigned NOT NULL DEFAULT '1',
  `modified_time` int(10) unsigned DEFAULT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`prop_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4  COMMENT='属性表';

DROP TABLE IF EXISTS `zg_prop_values`;
CREATE TABLE `zg_prop_values` (
  `prop_value_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '属性值ID',
  `prop_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '属性ID',
  `prop_value` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '属性值',
  `prop_image` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '属性图片',
  `order_sort` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`prop_value_id`),
  KEY `ind_prop_id` (`prop_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4  COMMENT='属性值表';

DROP TABLE IF EXISTS `zg_brand`;
CREATE TABLE `zg_brand` (
  `brand_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '品牌id',
  `brand_name` varchar(50) COLLATE utf8_unicode_ci NOT NULL COMMENT '品牌名称',
  `order_sort` int(10) unsigned DEFAULT '0' COMMENT '排序',
  `brand_desc` longtext COLLATE utf8_unicode_ci COMMENT '品牌介绍(保留字段)',
  `brand_logo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '品牌图片标识',
  `modified_time` int(10) unsigned DEFAULT NULL,
  `disabled` tinyint(1) DEFAULT '0' COMMENT '失效',
  PRIMARY KEY (`brand_id`),
  KEY `ind_brand_name` (`brand_name`)
) ENGINE=InnoDB DEFAULT  CHARSET=utf8mb4  COMMENT='品牌表';
