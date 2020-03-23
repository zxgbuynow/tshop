ALTER TABLE `tshop1`.`call_ondate` 
ADD COLUMN `note` text NULL COMMENT '备注' AFTER `user_id`;

ALTER TABLE `tshop1`.`admin_role` 
ADD COLUMN `access_moblie` tinyint(1) NULL COMMENT '是否可查看手机号' AFTER `access`;

ALTER TABLE `tshop1`.`admin_role` 
MODIFY COLUMN `access_moblie` tinyint(1) NULL DEFAULT 1 COMMENT '是否可查看手机号' AFTER `access`;