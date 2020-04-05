ALTER TABLE `tshop1`.`call_ondate` 
ADD COLUMN `note` text NULL COMMENT '备注' AFTER `user_id`;

ALTER TABLE `tshop1`.`admin_role` 
ADD COLUMN `access_moblie` tinyint(1) NULL COMMENT '是否可查看手机号' AFTER `access`;

ALTER TABLE `tshop1`.`admin_role` 
MODIFY COLUMN `access_moblie` tinyint(1) NULL DEFAULT 1 COMMENT '是否可查看手机号' AFTER `access`;

ALTER TABLE `tshop1`.`admin_user` 
ADD COLUMN `extension` varchar(50) NULL COMMENT '绑定分机' AFTER `is_maner`;

CREATE TABLE `call_message` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(32) NOT NULL DEFAULT '' COMMENT '名称',
  `content` varchar(250) NOT NULL DEFAULT '' COMMENT '内容',
  `touser_type` tinyint(1) DEFAULT '0' COMMENT '对象 0 个人 1 组 2全员',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户',
  `role` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '角色ID',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '状态：0禁用，1启用',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='消息表';

CREATE TABLE `call_message_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '消息id',
  `is_read` tinyint(1) DEFAULT '0' COMMENT '是否已读 0 未读 1 已读',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COMMENT='消息读取日志表';

ALTER TABLE `tshop1`.`call_message_log` 
ADD COLUMN `user_id` int(10) NULL COMMENT '用户' AFTER `is_read`;

ALTER TABLE `tshop1`.`call_message` 
ADD COLUMN `oper_id` int(10) NULL COMMENT '操作人' AFTER `status`;

ALTER TABLE `tshop1`.`call_custom` 
ADD COLUMN `record_time` varchar(100) NULL COMMENT '记录时间' AFTER `area`,
ADD COLUMN `call_time` varchar(100) NULL COMMENT '最后一次通话时间' AFTER `record_time`;

ALTER TABLE `tshop1`.`call_alloc` 
ADD COLUMN `name` varchar(100) NULL COMMENT '任务名称' AFTER `way`;

ALTER TABLE `tshop1`.`call_custom` 
ADD COLUMN `batch_id` varchar(100) NULL COMMENT '任务批次' AFTER `call_time`,
ADD INDEX `ind_batch_id`(`batch_id`) USING BTREE;
ALTER TABLE `tshop1`.`call_custom_export_log` 
ADD COLUMN `batch_id` varchar(100) NULL COMMENT '批次' AFTER `create_time`,
ADD INDEX `ind_batch_id`(`batch_id`) USING BTREE;

ALTER TABLE `tshop1`.`call_alloc` 
ADD COLUMN `batch_id` varchar(100) NULL COMMENT '批次' AFTER `name`;

ALTER TABLE `tshop1`.`call_speechcraft` 
ADD COLUMN `alloc_id` int(10) NULL COMMENT '任务id' AFTER `tags`;

CREATE TABLE `call_msg_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `content` varchar(250) NOT NULL DEFAULT '' COMMENT '短信内容',
  `custom_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人ID',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='短信发送表'

ALTER TABLE `tshop1`.`call_log` 
ADD COLUMN `role_id` int(10) NULL COMMENT '角色ID' AFTER `extension`;
