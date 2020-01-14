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

namespace plugins\Sms;

use app\common\controller\Plugin;

/**
 * 演示插件
 * @package plugin\Sms
 * @author 蔡伟明 <314013107@qq.com>
 */
class Sms extends Plugin
{
    /**
     * @var array 插件信息
     */
    public $info = [
        // 插件名[必填]
        'name'        => 'Sms',
        // 插件标题[必填]
        'title'       => '短信插件',
        // 插件唯一标识[必填],格式：插件名.开发者标识.plugin
        'identifier'  => 'sms.ming.plugin',
        // 插件图标[选填]
        'icon'        => 'fa fa-fw fa-envelope-o',
        // 插件描述[选填]
        'description' => '阿里大鱼短信插件',
        // 插件作者[必填]
        'author'      => '蔡伟明',
        // 作者主页[选填]
        'author_url'  => 'http://www.dolphinphp.com',
        // 插件版本[必填],格式采用三段式：主版本号.次版本号.修订版本号
        'version'     => '1.0.0',
        // 是否有后台管理功能
        'admin'       => '1',
    ];

    /**
     * @var array 管理界面字段信息
     */
    public $admin = [
        'title'        => '短信模板列表', // 后台管理标题
        'table_name'   => 'plugin_sms', // 数据库表名，如果没有用到数据库，则留空
        'order'        => 'id,title', // 需要排序功能的字段，多个字段用逗号隔开
        'filter'       => '', // 需要筛选功能的字段，多个字段用逗号隔开
        'search_title' => '', // 搜索框提示文字,一般不用填写
        'search_field' => [ // 需要搜索的字段，如果需要搜索，则必填，否则不填
            'title' => '模板名称',
            'code'  => '模板ID'
        ],
        'search_url' => '', // 搜索框url链接,如：'user/index'，一般不用填写

        // 后台列表字段
        'columns' => [
            ['title', '模板名称'],
            ['code', '模板ID'],
            ['sign_name', '短信签名'],
            ['status', '状态', 'switch'],
            ['right_button', '操作', 'btn'],
        ],

        // 右侧按钮
        'right_buttons' => [
            'edit',          // 使用系统自带的编辑按钮
            'delete',        // 使用系统自带的删除按钮
        ],

        // 顶部栏按钮
        'top_buttons' => [
            'add',    // 使用系统自带的添加按钮
            'enable', // 使用系统自带的启用按钮
            'disable',// 使用系统自带的禁用按钮
            'delete', // 使用系统自带的删除按钮
        ],
    ];

    /**
     * @var array 新增或编辑的字段
     */
    public $fields = [
        ['text', 'title', '模板名称', '必填，自定义填写，用于区分用途，比如：注册验证、密码修改'],
        ['text', 'code', '模板ID', '必填'],
        ['text', 'sign_name', '短信签名', '在阿里大鱼后台设置的短信签名'],
        ['radio', 'status', '立即启用', '', ['1' => '是', '0' => '否'], 1],
    ];

    /**
     * @var string 原数据库表前缀
     */
    public $database_prefix = 'dp_';

    /**
     * 安装方法必须实现
     * 一般只需返回true即可
     * 如果安装前有需要实现一些业务，可在此方法实现
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    public function install(){
        return true;
    }

    /**
     * 卸载方法必须实现
     * 一般只需返回true即可
     * 如果安装前有需要实现一些业务，可在此方法实现
     * @author 蔡伟明 <314013107@qq.com>
     * @return bool
     */
    public function uninstall(){
        return true;
    }
}