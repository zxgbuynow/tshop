
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
