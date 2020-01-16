/*
// +----------------------------------------------------------------------
// | 海豚PHP框架 [ DolphinPHP ]
// +----------------------------------------------------------------------
// | 版权所有 2016~2017 河源市卓锐科技有限公司 [ http://www.zrthink.com ]
// +----------------------------------------------------------------------
// | 官方网站: http://dolphinphp.com
// +----------------------------------------------------------------------
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `dolphin_plugin_hello`
-- ----------------------------
create table admin_plugin_form
(
  id          int auto_increment
    primary key,
  title       varchar(150) not null
  comment '标题',
  create_time int          not null,
  update_time int          not null
) comment '表单数据表';

create table admin_plugin_form_data
(
  id          int auto_increment
    primary key,
  form_id     int             not null,
  input_type  varchar(100)    not null,
  create_time int             null,
  update_time int             not null,
  placeholder varchar(255)    not null,
  title       text            not null,
  sort        int default '1' not null
) comment '表单数据项';

