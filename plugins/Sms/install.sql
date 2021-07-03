


SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `dp_plugin_sms`
-- ----------------------------
DROP TABLE IF EXISTS `dp_plugin_sms`;
CREATE TABLE `dp_plugin_sms` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(32) NOT NULL DEFAULT '' COMMENT '模板名称',
  `code` varchar(128) NOT NULL DEFAULT '' COMMENT '模板id',
  `sign_name` varchar(128) NOT NULL DEFAULT '' COMMENT '短信签名',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '状态',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='短信模板表';