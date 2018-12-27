<?php
namespace app\api\home;

use \think\Request;
use \think\Db;
use think\Model;
use think\helper\Hash;
use think\Session;

use app\api\home\Hx as Hx;
/**
 * 前台首页控制器
 * @package app\index\controller
 */
class Index
{
    public function index()
    {
        $request = Request::instance();
        $params = $request->param();
        //获得定义接口
        $api =  array_flip(config('api'));
        if (isset($params['method'])) {
            //判断是否存在该方法
            $func = $api[$params['method']].'_'.$params['source'];
            if (method_exists($this,$func)) {
                return $this->$func($params);
            }else{
                return $this->error($params['method'].'方法不存在');
            }
            
        }else{
            return $this->error('method参数缺失');
        }
    }

    public function error($msg)
    {
        $data = [
            'code'=>'0',
            'msg'=>$msg,
            'data'=>null
        ];
        return json($data);
    }
    /**
     * login 用户端
     * @param string $value [description]
     */
    public function login_custom($params)
    {   
        //参数手机号，密码
        
        $username = trim($params['account']);
        $password = trim($params['password']);
        $ismobile = trim($params['ismobile']);


        //是否存在
        $map['username'] = $username;
        // $map['status'] = 1;
        $map['type'] = 0;
        $user = db('member')->where($map)->find();
        if (!$user) {
            return $this->error('用户不存在或被禁用！');
        }
        //密码是否正确
        if (!Hash::check((string)$password, $user['password'])) {
           return $this->error( '密码错误！');
        }
        //
        if ($ismobile) {
             session('user_mobile_auth',$user);
        }
        if (is_numeric($user['avar'])) {
            $user['avar'] = get_file_path($user['avar']);
        }
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$user
        ];
        return json($data);
    }
    /**
     * [logout_coustom description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function logout_custom($params)
    {
        $username = trim($params['account']);
        $ismobile = trim($params['ismobile']);
        if ($ismobile) {
             session('user_mobile_auth',null);
        }

        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);

    }
    /**
     * 注册
     * @return [type] [description]
     */
    public function register_custom($params)
    {
        //参数
        $data['username'] = trim($params['mobile']);
        $data['nickname'] = trim($params['account']);
        $data['email'] = trim($params['email']);
        $data['mobile'] = trim($params['mobile']);
        // $data['shopid'] = trim($params['agency']);
        $data['create_time'] = time();
        $data['shopid'] = 39;

        $data['birthday'] = trim($params['birthday']);
        $data['sex'] = trim($params['sex']);
        $data['edu'] = trim($params['edu']);
        $data['grade'] = trim($params['grade']);
        $data['profession'] = trim($params['profession']);
        $data['marital'] = trim($params['marital']);
        $data['province'] = trim($params['province']);
        $data['city'] = trim($params['city']);
        $data['tel'] = trim($params['tel']);
        $data['isconsolle'] = trim($params['isconsolle']);
        $data['consolletime'] = trim($params['consolletime']);

        $data['preference'] = trim($params['preference']);

        if (isset($params['ismobile'])) {
            $data['source_from'] = 1;
        }

        if (db('member')->where(['mobile'=>$data['mobile']])->find()) {
            return $this->error('账号已存在！');
        }
        //生成密码
        $data['password'] =  Hash::make((string)trim($params['password']));

        //是否为会员 
        if($isvip = db('vip_log')->where(['account'=>$data['mobile'],'status'=>0])->find()){
            $data['vipday'] = $isvip['vip'];
            $data['viptime'] = time();
            $data['viplastt'] = $isvip['vip']==12?30879000:604800;
            $data['is_diamonds']= 1;
        }
        
        //插入数据
        $me = db('member')->insert($data);
        if (!$me) {
            return $this->error('注册失败！请稍后重试');
        }
        db('vip_log')->where(['account'=>$data['mobile']])->update(['status'=>1]);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$me
        ];
        return json($data);
    }

    /**
     * 验证码 暂没
     * @return [type] [description]
     */
    public function vcode_custom($params)
    {
        //参数
        $account = trim($params['mobile']);

        //短信
        $code = $this->sendmsg($account);
        if (!$code) {
            return $this->error('发送失败，1小时只能获得3次');
        }
        
        //生成session 
        cache($account.'code',$code);

        //设置过期时间
        cache($account.$code, time() + 1800) ;

        // $code  = rand(1000,9999);

        // //生成session 
        // session($account.$code,1);

        $map['username'] = $account;
        $user = db('member')->where($map)->find();
        if (!$user) {
            return $this->error('手机号不存在');
        }

        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$code
        ];
        return json($data);
    }

    /**
     * 注册 验证
     * @return [type] [description]
     */
    public function confirm_custom($params)
    {
        //参数
        $username = trim($params['mobile']);
        $code = trim($params['code']);
        
        //检查过期时间
        if (cache($username.$code)&&cache($username.$code)<time()) {
            return $this->error('验证码已过期');
        }
        
        //检查是否正确
        if (cache($username.'code')!=$code) {
            return $this->error('验证码不正确');
        }
        

        //更新状态
        $data['status'] = 1;
        
        $map['username'] = $username;

        if(!db('member')->where($map)->update($data)){
            // return $this->error('服务器忙，请稍后');
        }
        
        //注销session
        // Session.set($username.$code,null);
        // Session.set($username.'code',null);

        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [用户信息]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function userinfo_custom($params)
    {
        //params
        $username = trim($params['account']);

        //是否存在
        $map['username'] = $username;
        $map['status'] = 1;
        //用户信息
        $user = db('member')->where($map)->find();
        if (!$user) {
            return $this->error('用户不存在');
        }
        // get_file_path
        if (is_numeric($user['avar'])) {
            $user['avar'] = get_file_path($user['avar']);
        }

        //用户积分
        $pmap['memberid'] = $user['id'];
        $pmap['behavior_type'] = 0;
        $user['point'] = db('member_point')->where($pmap)->sum('point');

         //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$user
        ];
        return json($data);

    }

    /**
     * [article_custom 首页好文章]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function article_custom($params)
    {
        //cid 
        $map['status'] = 1;
        if (isset($params['account'])) {
            $preference = db('member')->where(['id'=>$params['account']])->column('preference');
            if ($preference[0]) {
                $map['cid']= array('in',explode(',', $preference[0]));
            }
            
        }
        $article['list'] = db('cms_page')->where($map)->order('sort ASC, view DESC')->limit(10)->select();

        // foreach ($article['list'] as $key => $value) {
        //     unset($article['list'][$key]['content']);
        //     $article['list'][$key]['author'] = $value['userid']==0?'大观心理':db('member')->where('status',1)->column('nickname');
        // }
        foreach ($article['list'] as $key => $value) {
            unset($article['list'][$key]['content']);
            unset($article['list'][$key]['description']);
            $article['list'][$key]['author'] = $value['userid']==0?'大观心理':db('member')->where('status',1)->column('nickname');
            $article['list'][$key]['cover'] = get_file_path($value['cover']);
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$article
        ];
        return json($data);

    }

   
     /**
     * [lunbo 首页轮播]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
     public function lunbo_custom($params)
    {
        $map['tagname'] = 'custom';
        $map['status'] = 1;
        $ismobile = trim($params['ismobile']);
        $lunbo['pic'] = db('cms_advert')->where($map)->order('id DESC')->limit(10)->select();
        foreach ($lunbo['pic'] as $key => $value) {
            if (strstr($value['link'], 'article')) {//文章
                if ($ismobile) {
                    $lunbo['pic'][$key]['webview'] = "/mobile.php/artical/detail.html";
                    $lunbo['pic'][$key]['webparam'] = explode('.',explode('/', $value['link'])[1])[0];
                }else{
                    $lunbo['pic'][$key]['webview'] = '_www/view/artical/detail.html';
                    $lunbo['pic'][$key]['webparam'] = ['article_id'=>explode('.',explode('/', $value['link'])[1])[0]];
                }
                
                 
            }else if (strstr($value['link'], 'counsellor')) {
                if ($ismobile) {
                    $lunbo['pic'][$key]['webview'] = "/mobile.php/counsellor/detail.html";
                     $lunbo['pic'][$key]['webparam'] = explode('.',explode('/', $value['link'])[1])[0];
                }else{
                    $lunbo['pic'][$key]['webview'] = '_www/view/counsellor/detail.html';
                     $lunbo['pic'][$key]['webparam'] = ['counsellor_id'=>explode('.',explode('/', $value['link'])[1])[0]];
                }
                
               
            }else if (strstr($value['link'], 'clac')) {
                if ($ismobile) {
                    $lunbo['pic'][$key]['webview'] = "/mobile.php/clac/detail.html";
                    $lunbo['pic'][$key]['webparam'] = explode('.',explode('/', $value['link'])[1])[0];
                    $lunbo['pic'][$key]['webparam1'] = 0;
                }else{
                    $lunbo['pic'][$key]['webview'] = '_www/view/clac/detail.html';
                     $lunbo['pic'][$key]['webparam'] = ['acid'=>explode('.',explode('/', $value['link'])[1])[0],'actype'=>0];
                }
                
               
            }else if (strstr($value['link'], 'act')) {
                if ($ismobile) {
                    $lunbo['pic'][$key]['webview'] = "/mobile.php/clac/detail.html";
                     $lunbo['pic'][$key]['webparam'] = explode('.',explode('/', $value['link'])[1])[0];
                     $lunbo['pic'][$key]['webparam1'] = 1;
                }else{
                    $lunbo['pic'][$key]['webview'] = '_www/view/clac/detail.html';
                     $lunbo['pic'][$key]['webparam'] = ['acid'=>explode('.',explode('/', $value['link'])[1])[0],'actype'=>1];
                }
                
               
            }else if (strstr($value['link'], 'member')) {
                if ($ismobile) {
                    $lunbo['pic'][$key]['webview'] = "/mobile.php/member/member.html";
                }else{
                    $lunbo['pic'][$key]['webview'] = '_www/view/member/member.html';
                }
                
               
            }else{
                $class['pic'][$key]['webview'] = '_www/view/index.html';
                $class['pic'][$key]['webparam'] = [];
            }
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$lunbo
        ];
        return json($data);
    }

    /**
     * [adv_custom 首页adv]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
     public function adv_custom($params)
    {
        $map['tagname'] = 'single';
        $map['status'] = 1;
        $ismobile = trim($params['ismobile']);
        $adv = db('cms_advert')->where($map)->order('id DESC')->find();
        if (!$adv) {
            //返回信息
            $data = [
                'code'=>'1',
                'msg'=>'',
                'data'=>1
            ];
            return json($data);
        }
        if (strstr($adv['link'], 'article')) {//文章
            if ($ismobile) {
                $adv['webview'] = "/mobile.php/artical/detail.html";
                $adv['webparam'] = explode('.',explode('/', $adv['link'])[1])[0];
            }else{
                $adv['webview'] = '_www/view/artical/detail.html';
                $adv['webparam'] = ['article_id'=>explode('.',explode('/', $adv['link'])[1])[0]];
            }
            
             
        }else if (strstr($adv['link'], 'counsellor')) {
            if ($ismobile) {
                $adv['webview'] = "/mobile.php/counsellor/detail.html";
                $adv['webparam'] = explode('.',explode('/', $adv['link'])[1])[0];
            }else{
                $adv['webview'] = '_www/view/counsellor/detail.html';
                $adv['webparam'] = ['counsellor_id'=>explode('.',explode('/', $adv['link'])[1])[0]];
            }
            
           
        }else if (strstr($adv['link'], 'clac')) {
            if ($ismobile) {
                $adv['webview'] = "/mobile.php/clac/detail.html";
                $adv['webparam'] = explode('.',explode('/', $adv['link'])[1])[0];
            }else{
                $adv['webview'] = '_www/view/clac/detail.html';
                $adv['webparam'] = ['acid'=>explode('.',explode('/', $adv['link'])[1])[0]];
            }
            
           
        }else if (strstr($adv['link'], 'member')) {
            if ($ismobile) {
                $adv['webview'] = "/mobile.php/member/member.html";
            }else{
                $adv['webview'] = '_www/view/member/member.html';
            }
            
           
        }else{
            $adv['webview'] = '_www/view/index.html';
            $adv['webparam'] = [];
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$adv
        ];
        return json($data);
    }

    /**
     * [classList_custom 课程列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function classList_custom($params)
    {
        $map['typeid'] = '1';//课程
        $map['status'] = 1;
        $ismobile = trim($params['ismobile']);
        $class['pic'] = db('cms_advert')->where($map)->order('id DESC')->limit(10)->select();

        foreach ($class['pic'] as $key => $value) {
            if (strstr($value['link'], 'article')) {//文章
                if ($ismobile) {
                    $class['pic'][$key]['webview'] = "/mobile.php/artical/detail.html";
                    $class['pic'][$key]['webparam'] = explode('.',explode('/', $value['link'])[1])[0];
                }else{
                    $class['pic'][$key]['webview'] = '_www/view/artical/detail.html';
                    $class['pic'][$key]['webparam'] = ['article_id'=>explode('.',explode('/', $value['link'])[1])[0]];
                }
                
                 
            }else if (strstr($value['link'], 'counsellor')) {
                if ($ismobile) {
                    $class['pic'][$key]['webview'] = "/mobile.php/counsellor/detail.html";
                     $class['pic'][$key]['webparam'] = explode('.',explode('/', $value['link'])[1])[0];
                }else{
                    $class['pic'][$key]['webview'] = '_www/view/counsellor/detail.html';
                    $class['pic'][$key]['webparam'] = ['counsellor_id'=>explode('.',explode('/', $value['link'])[1])[0]];
                }
                
               
            }else{
                $class['pic'][$key]['webview'] = '';
                $class['pic'][$key]['webparam'] = [];
            }
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$class
        ];
        return json($data);
    }
    /**
     * [updatemember_custom 升级会员]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function updatemember_custom($params)
    {
        $map['typeid'] = '4';//会员升级
        $map['status'] = 1;
        $ismobile = trim($params['ismobile']);
        if (isset($params['account'])) {
            $m = db('member')->where(['id'=>$params['account']])->value('is_diamonds');
            if ($m!=1) {
                $info = db('cms_advert')->where($map)->order('id DESC')->find();

                if (strstr($info['link'], 'member')) {//会员升级
                    if ($ismobile) {
                        $info['webview'] = "/mobile.php/member/updatelv.html";
                        $info['webparam'] = '';
                    }else{
                        $info['webview'] = '_www/view/member/updatelv.html';
                        $info['webparam'] = [];
                    }
                }else{
                    $info['webview'] = '';
                    $info['webparam'] = [];
                }
            }else{
                //返回信息
                $data = [
                    'code'=>'1',
                    'msg'=>'',
                    'data'=>1
                ];
                return json($data);
            }
            
        }else{
           //返回信息
            $data = [
                'code'=>'1',
                'msg'=>'',
                'data'=>1
            ];
            return json($data); 
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$info
        ];
        return json($data);
    }
    /**
     * [activeList_custom 活动]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function activeList_custom($params)
    {
        $map['typeid'] = '2';//活动
        $map['status'] = 1;
        $ismobile = trim($params['ismobile']);
        $class['pic'] = db('cms_advert')->where($map)->order('id DESC')->limit(10)->select();
        foreach ($class['pic'] as $key => $value) {
            if (strstr($value['link'], 'article')) {//文章
                if ($ismobile) {
                    $class['pic'][$key]['webview'] = "/mobile.php/artical/detail.html";
                    $class['pic'][$key]['webparam'] = explode('.',explode('/', $value['link'])[1])[0];
                }else{
                    $class['pic'][$key]['webview'] = '_www/view/artical/detail.html';
                    $class['pic'][$key]['webparam'] = ['article_id'=>explode('.',explode('/', $value['link'])[1])[0]];
                }
                
                 
            }else if (strstr($value['link'], 'counsellor')) {
                if ($ismobile) {
                    $class['pic'][$key]['webview'] = "/mobile.php/counsellor/detail.html";
                     $class['pic'][$key]['webparam'] = explode('.',explode('/', $value['link'])[1])[0];
                }else{
                    $class['pic'][$key]['webview'] = '_www/view/counsellor/detail.html';
                     $class['pic'][$key]['webparam'] = ['counsellor_id'=>explode('.',explode('/', $value['link'])[1])[0]];
                }
                
               
            }else{
                $class['pic'][$key]['webview'] = '_www/view/index.html';
                $class['pic'][$key]['webparam'] = [];
            }
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$class
        ];
        return json($data);
    }

    /**
     * [category_custom 咨询分类]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function category_custom($params)
    {
        $category['list'] = db('cms_category')->where('status',1)->order('id DESC')->limit(8)->select();
        foreach ($category['list'] as $key => $value) {
            $category['list'][$key]['cover'] = get_file_path($value['cover']);
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$category
        ];
        return json($data);
    }
    /**
     * [deskrecommend_custom 平台推荐]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function deskrecommend_custom($params)
    {
        
        $map['b.online'] = 1;
        $map['a.recommond'] = 1;
        $recommend['list'] = db('member')->alias('a')->field('a.*,b.*')->join(' member_counsellor b',' b.memberid = a.id','LEFT')->where($map)->limit(3)->order('rand()')->select();
        // $recommend['list'] = db('member')->where($map)->order('sort ASC,recommond DESC')->limit(3)->order('rand()')->select();

        foreach ($recommend['list'] as $key => $value) {
            // unset($recommend['list'][$key]['intro']);
            // unset($recommend['list'][$key]['remark']);
            // if (!$value['memberid']) {
            //     unset($recommend['list'][$key]);
            //     continue;
            // }
            // if ($value['tags']) {
            //     $tags = explode(',', $value['tags']);
            //     if (empty(array_intersect($tags,$preferencearr))&&$preferencearr) {
            //         unset($recommend['list'][$key]);
            //         continue;
            //     }
                
            // }else{
            //     unset($recommend['list'][$key]);
            //     continue;
            // }
            // get_file_path
            if (is_numeric($recommend['list'][$key]['avar'])) {
                $recommend['list'][$key]['avar'] = get_file_path($recommend['list'][$key]['avar']);
            }
            //订单数
            $recommend['list'][$key]['trade'] = db('trade')->where(array('status'=>1,'mid'=>$value['memberid']))->count();
            //标识
            $smap['id'] = array('in',$value['tags']);
            // $recommend['list'][$key]['sign'] = implode('|', db('cms_category')->where($smap)->column('title')) ;
            $recommend['list'][$key]['signarr'] =  db('cms_category')->where($smap)->column('title') ;
            //星级
            $recommend['list'][$key]['start'] = db('member')->alias('a')->field('e.*')->join(' trade b',' b.mid = a.id','LEFT')->join(' calendar c',' c.tid = b.id','LEFT')->join(' evaluate e',' e.cid = c.id','LEFT')->where(array('a.id'=>$value['memberid']))->avg('sorce');
            //少于4星默认 4星
            if ($recommend['list'][$key]['start']<8) {
                $recommend['list'][$key]['start'] = (round(8 / 10, 2)*100).'%';
            }else{
                $recommend['list'][$key]['start'] = (round($recommend['list'][$key]['start'] / 10, 2)*100).'%';
            }
            //从业时间
            // $recommend['list'][$key]['employment'] = '从业'.ceil(date('Y',time())-date('Y',$value['employment'])).'年';
            //分中心
            $recommend['list'][$key]['shopname'] = $value['shopid']?db('shop_agency')->where(['id'=>$value['shopid']])->value('city'):'中国大陆';
        }
        $recommend['list'] = array_values($recommend['list']);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$recommend
        ];
        return json($data);
    }
    /**
     * [recommend_custom 推荐咨询师]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function recommend_custom($params)
    {
        $map['a.status'] = 1;
        $map['a.type'] = 1;
        $preferencearr = [];
        if (isset($params['account'])) {
            $preference = db('member')->where(['id'=>$params['account']])->column('preference');
            if ($preference&&$preference[0]) {
                $preferencearr = explode(',', $preference[0]);
            }
            
        }

        $page_no = intval($params['page_no']);
        $page_size = intval($params['page_size']);

        $startpg = ($page_no-1)*$page_size;
        $map['b.online'] = 1;
        // $recommend['list'] = db('member')->alias('a')->field('a.*,b.online,b.memberid,b.tags')->join(' member_counsellor b',' b.memberid = a.id','LEFT')->where($map)->order('a.sort ASC,a.recommond DESC')->limit($startpg, $page_size)->select();
        $recommend['list'] = db('member')->where(['status'=>1,'type'=>1,'online'=>1])->limit($startpg.','.$page_size)->order('recommond DESC,convert(nickname using gb2312) ASC')->select();
        // error_log(db('member')->getlastsql(),3,'/home/wwwroot/daguan/rec.log');
        $usersort = [];
        foreach ($recommend['list'] as $key => $value) {
            $co = db('member_counsellor')->where(['memberid'=>$value['id']])->find();
            $usersort[$key] = iconv('UTF-8', 'gbk//TRANSLIT//IGNORE',$value['nickname']) ;
            $recommend['list'][$key]['tearch'] = $co['tearch'];
            $recommend['list'][$key]['leader'] = $co['leader'];
            $recommend['list'][$key]['memberid'] = $co['memberid'];

            // unset($recommend['list'][$key]['intro']);
            // unset($recommend['list'][$key]['remark']);
            // if (!$value['memberid']) {
            //     unset($recommend['list'][$key]);
            //     continue;
            // }
            // if ($value['tags']) {
            //     $tags = explode(',', $value['tags']);
            //     if (empty(array_intersect($tags,$preferencearr))&&$preferencearr) {
            //         unset($recommend['list'][$key]);
            //         continue;
            //     }
                
            // }else{
            //     unset($recommend['list'][$key]);
            //     continue;
            // }
            // get_file_path
            if (is_numeric($recommend['list'][$key]['avar'])) {
                $recommend['list'][$key]['avar'] = get_file_path($recommend['list'][$key]['avar']);
            }
            //订单数
            $recommend['list'][$key]['trade'] = db('trade')->where(array('status'=>1,'mid'=>$value['id']))->count();
            //标识
            $smap['id'] = array('in',$co['tags']);
            // $recommend['list'][$key]['sign'] = implode('|', db('cms_category')->where($smap)->column('title')) ;
            $recommend['list'][$key]['signarr'] =  db('cms_category')->where($smap)->column('title') ;
            //星级
            $recommend['list'][$key]['start'] = db('member')->alias('a')->field('e.*')->join(' trade b',' b.mid = a.id','LEFT')->join(' calendar c',' c.tid = b.id','LEFT')->join(' evaluate e',' e.cid = c.id','LEFT')->where(array('a.id'=>$value['id']))->avg('sorce');
            //少于4星默认 4星
            if ($recommend['list'][$key]['start']<8) {
                $recommend['list'][$key]['start'] = (round(8 / 10, 2)*100).'%';
            }else{
                $recommend['list'][$key]['start'] = (round($recommend['list'][$key]['start'] / 10, 2)*100).'%';
            }

            //从业时间
            // $recommend['list'][$key]['employment'] = '从业'.ceil(date('Y',time())-date('Y',$value['employment'])).'年';
            //分中心
            $recommend['list'][$key]['shopname'] = $value['shopid']?db('shop_agency')->where(['id'=>$value['shopid']])->value('city'):'中国大陆';
        }

        $recommend['list'] = array_values($recommend['list']);
        array_multisort($usersort,SORT_ASC,$recommend['list']);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$recommend
        ];
        return json($data);
    }

    /**
     * [articlebycate_custom 分类文章]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function articlebycate_custom($params)
    {
        $map['type'] = 0;
        $map['status'] = 1;
        $cid = $params['cid'];
        if ($cid) {
            $map['cid'] = $cid;
        }
        $article['list'] = db('cms_page')->where($map)->order('sort ASC ,view DESC')->select();

        foreach ($article['list'] as $key => $value) {
            unset($article['list'][$key]['content']);
            $article['list'][$key]['author'] = $value['userid']==0?'大观心理':db('member')->where('status',1)->column('nickname');
        }
        foreach ($article['list'] as $key => $value) {
            unset($article['list'][$key]['content']);
            $article['list'][$key]['author'] = $value['userid']==0?'大观心理':db('member')->where('status',1)->column('nickname');
            $article['list'][$key]['cover'] = get_file_path($value['cover']);
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$article
        ];
        return json($data);
    }
    /**
     * [counsellor_custom 咨询师]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function counsellor_custom($params)
    {
        if (!trim($params['id'])) {
            return $this->error('参数缺失！');
        }
        $account = isset($params['account'])?$params['account']:'';
        //会员
        //is_diamonds
        $is_diamonds = 0;

        if ($account) {
            $is_diamonds = db('member')->where(['id'=>$account])->value('is_diamonds');
        }
        
        
        $counsellor =  db('member')->alias('a')->field('a.*,b.*')->join(' member_counsellor b',' b.memberid = a.id','LEFT')->where(array('a.id'=>$params['id']))->find();
        if (!$counsellor) {
            return $this->error('咨询师不存在或是已注销');
        }
        if (is_numeric($counsellor['avar'])) {
                $counsellor['avar'] = get_file_path($counsellor['avar']);
            }

        //订单数
        $counsellor['trade'] = db('trade')->where(array('status'=>1,'mid'=>$counsellor['memberid']))->count();
        //标识
        $smap['id'] = array('in',$counsellor['tags']);
        $counsellor['signarr'] = db('cms_category')->where($smap)->column('title') ;
        //从业时间
        // $counsellor['employment'] = '从业'.ceil(date('Y',time())-date('Y',$counsellor['employment'])).'年';
        
        //星级
        // $counsellor['start'] = db('member')->alias('a')->field('e.*')->join(' trade b',' b.mid = a.id','LEFT')->join(' calendar c',' c.tid = b.id','LEFT')->join(' evaluate e',' e.cid = c.id','LEFT')->where(array('a.id'=>$params['id']))->avg('sorce');
        $counsellor['start'] = $counsellor['sources'];
        //少于4星默认 4星
        if ($counsellor['start']<8) {
            $counsellor['start'] = 8;
            $counsellor['percent'] = (round(8 / 10, 2)*100).'%';
        }else{
            $counsellor['percent'] = (round($counsellor['start'] / 10, 2)*100).'%';
        }

        $counsellor['shopname'] = $counsellor['shopid']?db('shop_agency')->where(['id'=>$counsellor['shopid']])->value('city'):'中国大陆';
        
        //沟通方式
        $counsellor['chartArr'] = array(
            array(
                'chart'=>'wordchart',
                'status'=>$counsellor['iswordchart'],
                'price'=>$counsellor['wordchart'],
                'price1'=>$counsellor['wordchartlv'],
                'show'=>'文字咨询'
            ),
            array(
                'chart'=>'speechchart',
                'status'=>$counsellor['isspeechchart'],
                'price'=>$counsellor['speechchart'],
                'price1'=>$counsellor['speechchartlv'],
                'show'=>'语音咨询'

            ),
            // array(
            //     'chart'=>'videochart',
            //     'status'=>$counsellor['isvideochart'],
            //     'price'=>$counsellor['videochart'],
            //     'price1'=>$counsellor['videochartlv'],
            //     'show'=>'视频咨询'
            // ),
            array(
                'chart'=>'facechart',
                'status'=>$counsellor['isfacechart'],
                'price'=>$counsellor['facechart'],
                'price1'=>$counsellor['facechartlv'],
                'show'=>'面对面咨询'
            )
        );
        foreach ($counsellor['chartArr'] as $key => $value) {
            if (!in_array($value['chart'], explode(',', $counsellor['openchart']))) {
                unset($counsellor['chartArr'][$key]);
                continue;
            }
        }
        //
        $counsellor['is_diamonds'] = $is_diamonds;
        //登录状态
        $counsellor['isfav'] =0 ;
        if (isset($params['account'])) {//用户id
            $amap['type'] = 0;
            $amap['fid'] = $params['id'];
            $amap['mid'] = $params['account'];
            if (db('cms_fav')->where($amap)->find()) {
               $counsellor['isfav'] = 1;
            }
            
        }
        //评价
        $evmap['a.id'] = $params['id'];
        $evmap['e.id'] = array('neq','null');
        $counsellor['evalist'] = db('member')->alias('a')->field('e.*')->join(' trade b',' b.mid = a.id','LEFT')->join(' calendar c',' c.tid = b.id','LEFT')->join(' evaluate e',' e.cid = c.id','LEFT')->where($evmap)->limit(5)->select();
        if (isset($params['ismore'])) {
            $counsellor['evalist'] = db('member')->alias('a')->field('e.*')->join(' trade b',' b.mid = a.id','LEFT')->join(' calendar c',' c.tid = b.id','LEFT')->join(' evaluate e',' e.cid = c.id','LEFT')->where($evmap)->select();
        }
        

        foreach ($counsellor['evalist'] as $key => $value) {
            $u = db('member')->where(['id'=>$value['memberid']])->find();
            if (!$u) {
                unset($counsellor['evalist'][$key]);
                continue;
            }

            $counsellor['evalist'][$key]['nickname'] = $this->handleNm($u['nickname']);
            $counsellor['evalist'][$key]['avar'] = $u['avar'];
            $counsellor['evalist'][$key]['is_diamonds'] = $u['is_diamonds'];
            if (is_numeric($u['avar'])) {
                $counsellor['evalist'][$key]['avar'] = get_file_path($u['avar']);
            }
        }

        //相关文章
        $pmap['userid'] = $params['id'];
        $pmap['status'] = 1;
        $article['list'] = db('cms_page')->where($pmap)->order('sort ASC, view DESC')->limit(2)->select();
        foreach ($article['list'] as $key => $value) {
            unset($article['list'][$key]['content']);
            $article['list'][$key]['cover']  = $value['fcover'];
        }

        $counsellor['article'] = $article['list'];
        
        //预约时间
        $cmap['memberid'] = $params['id'];
        // $cmap['ondatetime'] = array('gt',strtotime(date('Y-m-d',time())));
        $cmap['ondatetime'] = array('gt',time());
        $cmap['status'] = 0;
        $counsellor['condate'] = db('connsellor_ondate')->where($cmap)->count();

        //挂靠分中心
        // $agmap['id'] = $counsellor['openshop']?array('in',$counsellor['openshop']):array('in',$counsellor['shopids']);
        $agmap['id'] = array('in',$counsellor['openshop']);
        $counsellor['shopidsnm'] = array_filter(db('shop_agency')->where($agmap)->column('city'));
        $counsellor['shopidsmap'] = array_filter(db('shop_agency')->where($agmap)->column('map_address'));
        //是否好友 下过单就是
        if ($account) {
            $ismap['memberid'] = $account;
            $ismap['mid'] = $params['id'];
            $ismap['paytype'] = 0;
            $ismap['created_time'] = array('gt',strtotime('2018-11-28'));
            $counsellor['isfrends'] = db('trade')->where($ismap)->count();

        }else{
            $counsellor['isfrends'] = 0;
        }
        
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$counsellor
        ];
        return json($data);
    }

    /**
     * [evalist_custom description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function evalist_custom($params)
    {

        $id = $params['id'];
        //评价
        $evmap['a.id'] = 503;
        $evmap['e.id'] = array('neq','null');
        $counsellor['evalist'] = db('member')->alias('a')->field('e.*')->join(' trade b',' b.mid = a.id','LEFT')->join(' calendar c',' c.tid = b.id','LEFT')->join(' evaluate e',' e.cid = c.id','LEFT')->where($evmap)->select();

        foreach ($counsellor['evalist'] as $key => $value) {
            $u = db('member')->where(['id'=>$value['memberid']])->find();
            if (!$u) {
                unset($counsellor['evalist'][$key]);
                continue;
            }

            $counsellor['evalist'][$key]['nickname'] = $this->handleNm($u['nickname']);
            $counsellor['evalist'][$key]['avar'] = $u['avar'];
            $counsellor['evalist'][$key]['is_diamonds'] = $u['is_diamonds'];
            if (is_numeric($u['avar'])) {
                $counsellor['evalist'][$key]['avar'] = get_file_path($u['avar']);
            }
        }

         //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$counsellor
        ];
        return json($data);
    }
    /**
     * [point 积分明细]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function point_custom($params)
    {
       if (!trim($params['account'])) {
            return $this->error('参数缺失！');
        }
        $member['list'] =  db('member')->alias('a')->field('a.*,b.*')->join(' member_point b',' b.memberid = a.id','LEFT')->where(array('a.username'=>$params['account']))->select();

        if (!$member) {
            return $this->error('用户不存在');
        }
        $pmap['memberid'] = $member['list'][0]['memberid'];
        $pmap['behavior_type'] = 0;
        $member['points'] = db('member_point')->where($pmap)->sum('point');
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$member
        ];
        return json($data);
    }

    /**
     * [trade 订单]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function trade_custom($params)
    {
        $account = trim($params['account']);

        $status = trim($params['status']);
        $page_no = trim($params['page_no']);
        $page_size = trim($params['page_size']);

        $map['memberid'] = $account;
        $map['paytype'] = 0;        

        if ($status == 'all') {
            $map['status'] = array('gt',0);
        }else{
            $map['status'] = $status;
        }
        $startpg = ($page_no-1)*$page_size;
        $data = db('trade')->where($map)->order('id DESC')->limit($startpg, $page_size)->select();

        foreach ($data as $key => $value) {
            $record = db('calendar')->where(['tid'=>$value['id']])->count();
            $data[$key]['process'] =  '当前进度：'.$record.'/'.$value['num'];
            $data[$key]['record'] =  $record;
        }
        $pages = array(
                'total'=>db('trade')->where($map)->order('id DESC')->count()
            );
        $trade['data']['pagers'] = $pages;
        $trade['data']['list'] = array_values($data);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$trade
        ];
        return json($data);
    }
    /**
     * [checkpassword 验证密码]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function checkpassword_custom($params)
    {
        $password = trim($params['password']);
        $account = trim($params['account']);

        //设置密码
        // $data['password'] =  Hash::make((string)$password);


        //更新
        $map['id'] = $account;
        $user =  db('member')->where($map)->find();
        if (!Hash::check((string)$password, $user['password'])) {
           return $this->error( '密码错误！');
        }

        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);

    }

    /**
     * [uppw_custom 更新密码]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function uppw_custom($params)
    {
        $password = trim($params['password']);
        $password_confirmation = trim($params['password_confirmation']);
        $account = trim($params['account']);
        if ($password!=$password_confirmation) {
            return $this->error('二次密码不一致');
        }
        //设置密码
        $data['password'] =  Hash::make((string)trim($password));

        //更新
        $map['id'] = $account;
        if(!db('member')->where($map)->update($data)){
            return $this->error('服务器忙，请稍后');
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [updatepreference_custom 更新preference]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function updatepreference_custom($params)
    {
        //参数
        $account = trim($params['account']);
        $pid = trim($params['pid']);
        $isActive = trim($params['isActive']);
        
        if (!$pid) {
            return $this->error('参数必填');
        }
        
        //获得用户信息
        $user = db('member')->where(['mobile'=>$account])->find();
        if ($user['preference']) {
            $preference = explode(',', $user['preference']);
        }else{
            $preference = array();
        }
        if (!$isActive) {
            foreach ($preference as $key => $value) {
                if ($value==$pid) {
                    unset($preference[$key]);
                }
            }
        }else{
            array_push($preference, $pid);
        }
        

        if ($preference) {
            $data['preference'] = implode(',', $preference);
        }else{
            $data['preference'] = '';
        }

        //更新状态
        // $data['preference'] = implode(',', $preference);
        $map['username'] = $account;
        if(!db('member')->where($map)->update($data)){
            // return $this->error('服务器忙，请稍后');
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [updatenickname 更新Nickname]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function updatenickname_custom($params)
    {
        //参数
        $account = trim($params['account']);
        $nickname = trim($params['nickname']);
        
        if (!$nickname) {
            return $this->error('参数必填');
        }
        

        //更新状态
        $data['nickname'] = $nickname;
        $map['username'] = $account;
        if(!db('member')->where($map)->update($data)){
            // return $this->error('服务器忙，请稍后');
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }
    /**
     * [updategender_custom 更新性别]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function updategender_custom($params)
    {
        //参数
        $account = trim($params['account']);
        $sex = trim($params['sex']);
        
        // if (!$sex) {
        //     return $this->error('参数必填');
        // }
        

        //更新状态
        $data['sex'] = $sex;
        $map['username'] = $account;
        if(!db('member')->where($map)->update($data)){
            return $this->error('服务器忙，请稍后');
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [contentinfo_custom 文章祥情]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function contentinfo_custom($params)
    {

        $id = $params['id'];
        if (!$id) {
            return $this->error('参数必填');
        }
        $article = db('cms_page')->where('id',$id)->find();

        //查询咨询师信息
        if ($article['userid']) {
            $article['counsellor'] = db('member')->where(['id'=>$article['userid']])->find();
            if (is_numeric($article['counsellor']['avar'])) {
                $article['counsellor']['avar'] = get_file_path($article['counsellor']['avar']);
            }
        }

        //收藏
        if (isset($params['account'])) {
            $fm['fid'] = $id;
            $fm['type'] = 3;
            $fm['mid'] = $params['account'];
            $article['isfav'] = db('cms_fav')->where($fm)->count();
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$article
        ];
        return json($data);

    }

    /**
     * [category_custom 咨询分类]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function allcategory_custom($params)
    {
        $category['list'] = db('cms_category')->where('status',1)->order('id DESC')->select();
        foreach ($category['list'] as $key => $value) {
            $category['list'][$key]['cover'] = get_file_path($value['cover']);
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$category
        ];
        return json($data);
    }
    /**
     * [counsellorlist_custom 咨询师列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function counsellorlist_custom($params)
    {

        //参数
        $category = '';
        if (isset($params['cat_id'])) {
            $category = trim($params['cat_id']);
        }
        if (isset($params['search_keywords'])) {
            $keyword = trim($params['search_keywords']);
            $map['a.nickname|s.title'] = array('like','%'.$keyword.'%');
        }
        

        $map['a.status'] = 1;
        $map['a.type'] = 1;

        $counsellor['list'] =  db('member')->alias('a')->field('a.*,b.*')->join(' member_counsellor b',' b.memberid = a.id','LEFT')->join(' shop_agency s',' a.shopid = s.id','LEFT')->where($map)->order('a.sort ASC,a.recommond DESC')->select();
        
        foreach ($counsellor['list'] as $key => $value) {
            if ($category) {
                if (!in_array($category, explode(',', $value['tags']))) {
                    unset($counsellor['list'][$key]);
                    continue;
                }
            }
            if (is_numeric($counsellor['list'][$key]['avar'])) {
                $counsellor['list'][$key]['avar'] = get_file_path($counsellor['list'][$key]['avar']);
            }  

            //标识
            $smap['id'] = array('in',$value['tags']);
            $counsellor['list'][$key]['sign'] = implode('|', db('cms_category')->where($smap)->column('title')) ;
            //从业时间
            $counsellor['list'][$key]['employment'] = '从业'.ceil(date('Y',time())-date('Y',$value['employment'])).'年'; 
        }   
        
        
        // if (!$counsellor) {
        //     return $this->error('咨询师不存在或是已注销');
        // }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$counsellor
        ];
        return json($data);
    }
   
    /**
     * [articallist_custom 文章列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function articallist_custom($params)
    {

        
        if (isset($params['cate_id'])&&!empty($params['cate_id'])) {
            $cate_id = trim($params['cate_id']);
            $map['cid'] = $cate_id;
        }

        if (isset($params['type'])) {
            $type = trim($params['type']);
            $map['type'] = $type;
        }

        if (isset($params['search_keywords'])&&!empty($params['search_keywords'])) {
            $keyword = trim($params['search_keywords']);
            $map['title'] = array('like','%'.$keyword.'%');
        }
        $map['status'] = 1;
        $article['list'] = db('cms_page')->where($map)->order('sort ASC, view DESC')->limit(10)->select();

        foreach ($article['list'] as $key => $value) {
            unset($article['list'][$key]['content']);
            $value['author'] = $value['author']?$value['author']:'ADMIN';
            $article['list'][$key]['author'] = $value['userid']==0?$value['author']:db('member')->where(['id'=>$value['userid']])->value('nickname');
            $article['list'][$key]['cover'] = get_file_path($value['cover']);
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$article
        ];
        return json($data);
    }
    /**
     * [agency_custom 机构列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function agency_custom($params)
    {
        $agency['list'] = db('shop_agency')->where('status',1)->select();

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$agency
        ];
        return json($data);
    }

    /**
     * [createTrade_custom 生成订单]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function createTrade_custom($params)
    {
        $counsellor_id = trim($params['counsellor_id']);
        $price = trim($params['price']);
        $chart = trim($params['chart']);
        $account = trim($params['account']);
        $num = trim($params['num']);

        //shopid mid uid tid payment title
        

        $data['mid'] = $counsellor_id;
        $data['memberid'] = $account;
        $data['payment'] = $price;
        $data['created_time'] = time();
        $data['num'] = $num;
        $data['chart'] = $chart;

        //冲值订单处理
        if (isset($params['type'])) {
            $buyname = db('member')->where('id',$account)->value('nickname');
            //赠送
            if (isset($params['username'])) {
                $username = $params['nm'];
                
                $data['title'] = $buyname .'赠送'.$username.'心窝会员';
                $data['username'] = $params['username'];
            }else{//升级
                
                $data['title'] = $buyname.'成为心窝会员';
            }
            
            
            //订单号
            $data['tid'] = date('YmdHis',time()).rand(1000,9999);
            //插入数据
            $data['paytype'] = 1;
            $trade = db('trade')->insert($data);
            if (!$trade) {
                return $this->error('生成订单');
            }
            // //生成消息
            // $msg['type'] = 0;
            // $msg['subtitle'] = $username[0].'成为心窝会员';
            // $msg['title'] = $username[0].'成为心窝会员';
            // $msg['descrption'] = $username[0].'成为心窝会员';
            // $msg['display'] = $username[0].'成为心窝会员';
            // $msg['sendid'] = $account;
            // $msg['reciveid'] = $counsellor_id;

            // $this->create_msg($msg);
            
            $ret = array('tid'=>$data['tid']);
            //返回信息
            $data = [
                'code'=>'1',
                'msg'=>'',
                'data'=>$ret
            ];
            return json($data);

        }

        //取会员
        $userinfo = db('member')->where('id',$account)->find();

        //取商品
        $goodsinfo = db('member_counsellor')->where('memberid',$counsellor_id)->find();

        //计算价格
        $price = $goodsinfo[$chart];
        //取会员价
        if ($userinfo['is_diamonds']) {
            $price = $goodsinfo[$chart.'lv'];
        }
        $data['payment'] = $price;
        //交易标题
        $str = '文字咨询';
        switch ($chart) {
            case 'speechchart':
                $str = '语音咨询';
                break;
            case 'videochart':
                $str = '视频咨询';
                break;
            case 'facechart':
                $str = '面对面咨询';
                break;
            
            default:
                break;
        }

        $counsellor = db('member')->where('id',$counsellor_id)->column('nickname');
        $username = db('member')->where('id',$account)->column('nickname');

        $data['title'] = $username[0].'预约'.$counsellor[0].$str;
        //机构 取会员机构
        
        // $data['shopid'] = db('member')->where('id',$account)->column('shopid')?db('member')->where('id',$account)->column('shopid')[0]:0;
        // if ($data['shopid']==0) {
        //     return $this->error('咨询师没设置所属机构');
        // }
        //机构 取咨询师机构counsellor_id
        $data['shopid'] = db('member')->where('id',$counsellor_id)->value('shopid');
        if (!$data['shopid']) {
            return $this->error('咨询师没设置所属机构');
        }
        //订单号
        $data['tid'] = date('YmdHis',time()).rand(1000,9999);
        //插入数据
        $trade = db('trade')->insert($data);
        if (!$trade) {
            return $this->error('生成订单失败');
        }
        //添加好友
        $hxaccount  = $userinfo['username'];
        $hxcounsellor  = db('member')->where('id',$counsellor_id)->value('username');
        if ($hxaccount&&$hxcounsellor) {
            Hx::addFriend($hxaccount,$hxcounsellor);
        }
        
        //生成消息
        $msg['type'] = 1;
        $msg['subtitle'] = '预约'.$counsellor[0].$str;
        $msg['title'] = '预约您的'.$str;
        $msg['descrption'] = $username[0].'预约您的'.$str;
        $msg['display'] = $username[0].'预约'.$counsellor[0].'的'.$str;
        $msg['sendid'] = $account;
        $msg['reciveid'] = $counsellor_id;
        $msg['tid'] = $data['tid'];

        $lastid = $this->create_msg($msg);
        $ret = array('tid'=>$data['tid'],'price'=>$price);
        //价格为0
        if ($price == 0) {
            db('trade')->where(['tid'=>$data['tid']])->update(['status'=>1]);//修改订单状态
            db('msg')->where(['tid'=>$data['tid']])->update(['is_pay'=>1]);//修改订单状态
            $ret = array('tid'=>$data['tid'],'flish'=>1);
        }
        //如果是会员
        // if ($userinfo['is_diamonds']&&$chart!='facechart') {
        //     db('trade')->where(['tid'=>$data['tid']])->update(['status'=>1]);//修改订单状态
        //     $ret = array('tid'=>$data['tid'],'flish'=>1);
        // }

        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$ret
        ];
        return json($data);


    }

    public function tradepay_custom($params)
    {
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }
    /**
     * [upavar_custom 头像]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function upavar_custom($params)
    {
        //参数
        $account = trim($params['account']);
        $avar = trim($params['avar']);
        
        if (!$avar) {
            return $this->error('参数必填');
        }
        

        //更新状态
        $data['avar'] =$this->_seve_img($avar);
        if (!$data['avar']) {
            return $this->error('头像上传失败，请稍后重试');
        }
        $map['id'] = $account;

        if(!db('member')->where($map)->update($data)){
            return $this->error('服务器忙，请稍后');
        }
        $info['avar'] = $data['avar'];
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$info
        ];
        return json($data);
    }
    /**
     * [getAvatar 头像]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getAvatar_custom($params)
    {
        $users = trim($params['users']);
        if (!$users) {
            return $this->error('参数缺失！');
        }

        $where['mobile'] = array('in',explode(',', $users)) ;
        $rs = db('member')->where($where)->column('mobile,avar,nickname');
        foreach ($rs as $key => $value) {
            if (is_numeric($value['avar'])) {
                $rs[$key]['avar'] = get_file_path($value['avar']);
            }
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);

    }

    /**
     * [calendatoday_custom 获得当前时间日程数据]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function calendatoday_custom($params)
    {
       $account = trim($params['account']);
       $cstime =  trim($params['day']);

       //当晚24点时间
       $cetime = strtotime(date('Y-m-d',$cstime))+24 * 60 * 60;
       //今天
       $today = strtotime(date('Y-m-d',time()));

       //日程
        $pmap['memberid'] = $account;

        $calendar['list'] = db('calendar')->where($pmap)->whereTime('start_time', 'between', [ intval($cstime) , $cetime])->select();
        // $times = array('9:00','9:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30','19:00');
        // $times = array('9:00~10:00','10:00~11:00','11:00~12:00','12:00~13:00','13:00~14:00','14:00~15:00','15:00~16:00','16:00~17:00','17:00~18:00','18:00~19:00','19:00~20:00','20:00~21:00');
        $times = array('0:00','1:00','2:00','3:00','4:00','5:00','6:00','7:00','8:00','9:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00','23:00');
        $timesarr['list'] = [];
        //过去的时间
        if ($today>strtotime(date('Y-m-d ',$cstime))) {
            foreach ($times as $key => $value) {
                //订单记录
                $tpoint = strtotime(date('Y-m-d',$cstime).$value);
                $timesarr['list'][$key]['t'] = $value;
                $timesarr['list'][$key]['s'] = 2;
            }
        }else{
            foreach ($times as $key => $value) {
                //订单记录
                // $sval = explode('~', $value)[0];
                $sval = $value;
                $tpoint = strtotime(date('Y-m-d',$cstime).$sval)+60;//加上60秒处理时间间隔
                $timesarr['list'][$key]['t'] = $value;
                $timesarr['list'][$key]['s'] = 0;
                if ($tpoint<time()) {
                    $timesarr['list'][$key]['s'] = 2;
                }
                foreach ($calendar['list'] as $k => $v) {
                    if ($tpoint>=$v['start_time']&&$tpoint<=$v['end_time']) {
                        $timesarr['list'][$key]['s'] = 1;
                    }
                }

                //查看是否设置了可约
                if ($timesarr['list'][$key]['s']==0) {
                    $tt = strtotime(date('Y-m-d',$cstime).$sval);
                    $cid = $account;
                    if (!db('connsellor_ondate')->where(['memberid'=>$cid,'ondatetime'=>$tt])->find()) {
                        $timesarr['list'][$key]['s'] = 2;
                    }
                }
            }
        }
        
        //咨询师
        $timesarr['user'] = db('member')->where(['id'=>$account])->column('username');

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$timesarr
        ];
        return json($data);
    }

    /**
     * [msg_shop 消息列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function msg_custom($params)
    {

        //查询消息
        $user['list'] =  db('msg')->where(1)->order('id DESC')->limit('30')->select();
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$user
        ];
        return json($data);
    }

    /**
     * [usersendSms_custom 发送验证码]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function usersendSms_custom($params)
    {
        //参数
        $account = trim($params['mobile']);

        //是否是会员
        if (!db('member')->where(['mobile'=>$account])->find()) {
            return $this->error('账号不存在');
        }
        
        //短信
        $code = $this->sendmsg($account);
        if (!$code) {
            return $this->error('发送失败，1小时只能获得3次');
        }
        
        //生成session 
        cache($account.'vcode',$code);

        //设置过期时间
        cache($account.$code,time() + 1800);

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }
    /**
     * [findPassword description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function findPassword_custom($params)
    {
        //参数
        $username = trim($params['mobile']);
        $code = trim($params['code']);
        $newpw = trim($params['newpw']);
        $rnewpw = trim($params['rnewpw']);


        //检查过期时间
        if (cache($username.$code)&&cache($username.$code)<time()) {
            return $this->error('验证码已过期');
        }
        
        //检查是否正确
        if (cache($username.'vcode')!=$code) {
            return $this->error('验证码不正确');
        }

        //生成密码
        $data['password'] =  Hash::make((string)trim($params['newpw']));

        //更新
        if(!db('member')->where(['mobile'=>$username])->update($data)){
            return $this->error('服务器忙，请稍后');
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [articalcate_custom 文章分类]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function articalcate_custom($params)
    {
        $ret = array();

        $cates = db('cms_articlecategory')->where('status',1)->order('sort DESC,id DESC')->limit(8)->select();
        foreach ($cates as $key => $value) {
            if (is_numeric($value['cover'])) {
                $cates[$key]['cover'] = get_file_path($value['cover']);
            }
        }
        $rs['data'] = array_values($cates);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);

        // $ret = array();
        // // $article = db('cms_page')->where('status',1)->order('view DESC')->limit(10)->select();

        // $cates = db('cms_category')->where('status',1)->order('id DESC')->select();

        // foreach ($cates as $k => $v) {
        //     $ret[$k]['name'] = $v['title'];
        //     $ret[$k]['cid'] = $v['id'];
        //     //取分类下数据
        //     $article = db('cms_page')->where(['status'=>1,'cid'=>$v['id']])->order('sort ASC, view DESC')->limit(10)->select();
        //     foreach ($article as $key => $value) {
        //         unset($value['content']);
        //         $ret[$k]['list'][$key] = $value;
        //         $ret[$k]['list'][$key]['author'] = $value['userid']==0?'ADMIN':db('member')->where('status',1)->column('nickname');
        //         $ret[$k]['list'][$key]['cover'] = get_file_path($value['cover']);
        //     }
        // }
        // $rs['data'] = array_values($ret);
        // //返回信息
        // $data = [
        //     'code'=>'1',
        //     'msg'=>'',
        //     'data'=>$rs
        // ];
        // return json($data);
    }

    /**
     * [articalcate_custom 文章分类]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function claccate_custom($params)
    {
        $ret = array();

        $cates = db('cms_clacategory')->where('status',1)->order('sort DESC,id DESC')->limit(4)->select();

        foreach ($cates as $key => $value) {
            if (is_numeric($value['cover'])) {
                $cates[$key]['cover'] = get_file_path($value['cover']);
            }
        }
        $rs['data'] = array_values($cates);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);
    }

    /**
     * [evaluate 评价]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function evaluate_custom($params)
    {   
        //参数
        $account = trim($params['account']);
        $c_id = trim($params['c_id']);
        $sorce = trim($params['sorce']);
        $cotent = trim($params['cotent']);

        $save['sorce'] = $sorce;
        $save['memberid'] = $account;
        $save['cotent'] = $cotent;
        $save['cid'] = $c_id;
        $save['create_time'] = time();
        if (!db('evaluate')->insert($save)) {
            $this->error('评论失败！');
        }
        db('calendar')->where(['id'=>$c_id])->update(['status'=>3]);

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [clacevaluate_custom 评价]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function clacevaluate_custom($params)
    {   
        //参数
        $account = trim($params['account']);
        $c_id = trim($params['c_id']);
        $actype = trim($params['actype']);
        $sorce = trim($params['sorce']);
        $cotent = trim($params['cotent']);

        // $save['sorce'] = $sorce;
        // $save['memberid'] = $account;
        // $save['cotent'] = $cotent;
        // $save['cid'] = $c_id;
        // $save['create_time'] = time();
        // if (!db('clac_evaluate')->insert($save)) {
        //     $this->error('评论失败！');
        // }
        $save['source'] = $sorce;
        $data['classid'] = $c_id;
        $data['type'] = $actype;
        $data['suid'] = $account;
        $data['msg'] = $cotent;
        $data['created_time'] = time();

        db('cms_reply')->insert($data);

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [oncalenda_custom 客户端预约时间]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function oncalenda_custom($params)
    {
        //title des tid createtime start_time memberid
        $account = trim($params['account']);
        $start_time = trim($params['hour']);
        $tid = trim($params['tid']);


        //添加
        $save['create_time'] = time();
        $save['memberid'] = $account;

        $save['start_time'] = strtotime($start_time);
        $save['end_time'] = strtotime($start_time)+60*60;
        $save['tid'] = $tid;

        $save['title'] = db('trade')->where('id',$tid)->value('title');
        $save['descrption'] = db('trade')->where('id',$tid)->column('title')[0];

        if (!db('calendar')->insert($save)) {
            $this->error('保存失败！');
        }

        //发送预约短信
        $content = $save['descrption'];
        $uid = db('trade')->where('id',$tid)->value('memberid');
        $mobile = db('member')->where(['id'=>$uid])->value('mobile');
        $sj = strtotime($start_time);
        $this->sendadvicemsg($mobile,$content,$sj);

        //发给咨询师
        $cmobile = db('member')->where(['id'=>$account])->value('mobile');
        $this->sendadvicemsg($cmobile,$content,$sj);

        //更新预约表状态
        db('connsellor_ondate')->where(['memberid'=>$account,'ondatetime'=>strtotime($start_time)])->update(['status'=>1]);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);

    }
    /**
     * [recordlist_custom 咨询记录列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function recordlist_custom($params)
    {
        $account = trim($params['account']);

        $status = trim($params['status']);
        $page_no = trim($params['page_no']);
        $page_size = trim($params['page_size']);

        $map['b.memberid'] = $account;
        

        if ($status == 'all') {
            // $map['status'] = array('gt',0);
        }else{
            $map['a.status'] = $status;
        }
        $startpg = ($page_no-1)*$page_size;

        $data = db('calendar')->alias('a')->field('a.*,b.chart')->join('trade b',' b.id = a.tid','LEFT')->where($map)->order('a.id DESC')->limit($startpg, $page_size)->select();
        foreach ($data as $key => $value) {
            switch ($value['chart']) {
                case 'speechchart':
                    $str = '语音咨询';
                    break;
                case 'videochart':
                    $str = '视频咨询';
                    break;
                case 'facechart':
                    $str = '面对面咨询';
                    break;
                
                default:
                    $str = '文字咨询';
                    break;
            }
            $data[$key]['chartkey'] = $value['chart'];
            $data[$key]['chart'] = $str;


            $member =  db('member')->alias('a')->field('a.*,b.mid')->join(' trade b',' b.memberid = a.id','LEFT')->where(array('b.id'=>$value['tid']))->find();
            $data[$key]['member'] =  $member['nickname'];
            $data[$key]['mid'] =  $member['mid'];
            $data[$key]['mobile'] =  db('member')->where(['id'=>$member['mid']])->value('mobile');
            $avar = db('member')->where(['id'=>$member['mid']])->value('avar');
            $data[$key]['avar'] =  $avar;
            if (is_numeric($avar)) {
                $data[$key]['avar'] =  get_file_path($avar);
            }
            $data[$key]['counsellor'] =  db('member')->where(['id'=>$member['mid']])->value('nickname');

            $data[$key]['st'] = date('Y-m-d H:i',$value['start_time']);
        }
        $pages = array(
                'total'=>db('calendar')->alias('a')->field('a.*')->join('trade b',' b.id = a.tid','LEFT')->where($map)->order('a.id DESC')->limit($startpg, $page_size)->count()
            );
        $trade['data']['pagers'] = $pages;
        $trade['data']['list'] = array_values($data);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$trade
        ];
        return json($data);
    }

    /**
     * [getUserMsgCount_custom 获取用户离线消息]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getUserMsgCount_custom($params)
    {   
        //参数
        $account = trim($params['account']);

        // $ret = Hx::getUserMsgCount($account);
        $ret = db('hx_msg')->where(['reciveid'=>$account,'status'=>0])->count();
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$ret
        ];
        return json($data);
    }

    /**
     * [getUserMsgCount_custom 获取用户好友]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getUserList_custom($params)
    {   
        //参数
        $account = trim($params['account']);

        $ret = Hx::showFriend($account);
        //处理用户列表
        $user = [];
        if ($ret) {
            foreach ($ret as $key => $value) {
                $u = db('member')->where(['username'=>$value])->find();
                if (!$u) {
                    continue;
                }
                $co = db('member_counsellor')->where(['memberid'=>$u['id']])->find();
                $user[$value]['nickname'] = $u['nickname'];
                $user[$value]['mid'] = $u['id'];
                $user[$value]['mobile'] = $value;
                $user[$value]['avar'] = $u['avar'];
                $smap['id'] = array('in',$co['tags']);
                @$user[$value]['signarr'] =  db('cms_category')->where($smap)->column('title');
                if (is_numeric($u['avar'])) {
                    $user[$value]['avar'] = get_file_path($u['avar']);
                }
                
            }
            array_multisort(array_column($user,'nickname'),SORT_ASC,$user);
        }
        

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$user
        ];
        
        return json($data);
    }



    /**
     * [getUserMsg_custom 获取用户离线消息]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getUserMsg_custom($params)
    {   
        //参数
        $account = trim($params['account']);

        $sl = "select+*+where+from='" . $account . "'+or+to='". '18321271831';
        $ret = Hx::chatRecord($sl);

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$ret
        ];
        return json($data);
    }

    /**
     * [cancleDate_custom 取消预约]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function cancleDate_custom($params)
    {
        //参数
        $cid = trim($params['cid']);

        if (isset($params['cid'])) {

            //短信通知

            // $mobile = db('calendar')->alias('a')->join('trade b',' b.id = a.tid','LEFT')->join(' member m',' m.id = b.memberid','LEFT')->where(array('a.id'=>$cid))->value('mobile');
            $mobile = db('calendar')->alias('a')->join('member m',' m.id = a.memberid','LEFT')->where(array('a.id'=>$cid))->value('mobile');
            
            if ($mobile) {
                // $counsellor = db('calendar')->alias('a')->join('member m',' m.id = a.memberid','LEFT')->where(array('a.id'=>$cid))->value('nickname');  
                $username = db('calendar')->alias('a')->join('trade b',' b.id = a.tid','LEFT')->join(' member m',' m.id = b.memberid','LEFT')->where(array('a.id'=>$cid))->value('nickname');  
                $sj = db('calendar')->alias('a')->join('member m',' m.id = a.memberid','LEFT')->where(array('a.id'=>$cid))->value('a.start_time');
                $this->sendcanlcemsg($mobile,$username,$sj);
            }
            $co = db('calendar')->where(['id'=>$cid])->find();
            db('calendar')->where(['id'=>$cid])->delete();
            db('connsellor_ondate')->where(['memberid'=>$co['memberid'],'ondatetime'=>$co['start_time']])->update(['status'=>0]);
            
            
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }
    /**
     * [cancleUp_custom description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function cancleUp_custom($params)
    {
        //参数
        $cid = trim($params['cid']);

        if (isset($params['cid'])) {

            db('calendar')->where(['id'=>$cid])->update(['status'=>1]);
            
            
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }
    /**
     * [getCurrentCander_custom 当前天后预约记录]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getCurrentCander_custom($params)
    {
        //参数
        $account = trim($params['account']);

        $firstday = date('Y-m-01', strtotime(date("Y-m-d")));
        $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
        $current = date('Y-m-d', time());
        $info =  db('connsellor_ondate')->where(['memberid'=>$account])->whereTime('ondatetime', 'between', [$current, $lastday])->select();
        // echo db('connsellor_ondate')->getlastsql();exit;
        $ret = array();

        //可约
        //约满
        //不可约
        
        foreach ($info as $key => $value) {
            if ($value['ondatetime']<time()) {
                continue;
            }
            //candar
            $ret[date('d',$value['ondatetime'])][] = $value['status'];
        }
        if (!$info) {
            //返回信息
            $data = [
                'code'=>'1',
                'msg'=>'',
                'data'=>[]
            ];
            return json($data);
        }
        $rs = array();
        $res = [];
        foreach ($ret as $k => $v) {
            $k = intval($k);
            $res[$k] =in_array(0, $v)?0:1;
            $rs['k'][] = $k;
        }
        $rs['v'] = $res;
        $rs['k'] = array_unique($rs['k']);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);
    }

    /**
     * [startondate_custom description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function startondate_custom($params)
    {
        //参数
        $account = trim($params['account']);//id
        $in = db('calendar')->alias('a')->join('trade b',' b.id = a.tid','LEFT')->where(array('b.memberid'=>$account))->order('a.start_time DESC')->find();
        if ($in['chart']=='wordchart') {
            return $this->error('文字咨询不能发起语音咨询');
        }
        $start_time = db('calendar')->alias('a')->join('trade b',' b.id = a.tid','LEFT')->where(array('b.memberid'=>$account))->order('a.start_time DESC')->value('start_time');
        $end_time = db('calendar')->alias('a')->join('trade b',' b.id = a.tid','LEFT')->where(array('b.memberid'=>$account))->order('a.start_time DESC')->value('end_time');

        $ret = 0;
        if ($start_time<time()&&time()<$end_time) {
            $ret = 1;
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$ret
        ];
        return json($data);
    }
    /**
     * [filteritems_custom description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function filteritems_custom($params)
    {
        $info = db('shop_agency')->where(['status'=>1])->select();
        foreach ($info as $key => $value) {
            $info[$key]['title'] = str_replace('大观', '', str_replace('心理咨询中心', '', $value['title']));
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$info
        ];
        return json($data);
    }

    /**
     * [searchcounsellor_custom 搜索咨询师]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function searchcounsellor_custom($params)
    {
        //参数
        //关键字
        if (array_key_exists("search_keywords",$params)){
            if (isset($params['search_keywords'])&&$params['search_keywords']!=''&&$params['search_keywords']!='undefined') {
                $keyword = trim($params['search_keywords']);
                $map['a.nickname|s.title'] = array('like','%'.$keyword.'%');
            } 
        }

        
        //性别
        if (array_key_exists("sex",$params)){
            if (isset($params['sex'])&&$params['sex']!=''&&$params['sex']!='undefined') {
                $sex = trim($params['sex']);
                $map['a.sex'] = array('in',$sex);
            }
        }
        //分中心
        if (array_key_exists("shopid",$params)){
            if (isset($params['shopid'])&&$params['shopid']!=''&&$params['shopid']!='undefined') {
                $shopid = trim($params['shopid']);
                $map['a.shopid'] = array('in',$shopid);
            }
        }
        //是否在线
        if (array_key_exists("online",$params)){
            if (isset($params['online'])&&$params['online']!=''&&$params['online']!='undefined') {
                $online = trim($params['online']);
                $map['b.online'] = array('in',$online);
            }
        }

        //今日是否有空
        if (array_key_exists("ondate",$params)){
            if (isset($params['ondate'])&&$params['ondate']!=''&&$params['ondate']!='undefined') {
                $ondate = explode(',', $params['ondate']);
            }
        }
        
        $map['a.status'] = 1;
        $map['a.type'] = 1;
        $counsellor['list'] =  db('member')->alias('a')->field('a.*,b.*')->join(' member_counsellor b',' b.memberid = a.id','LEFT')->join(' shop_agency s',' a.shopid = s.id','LEFT')->where($map)->limit(20)->order('recommond DESC,convert(nickname using gb2312) ASC')->select();
        foreach ($counsellor['list'] as $key => $value) {
            //今日是否有空
            if (isset($ondate)&&count($ondate)==1) {
                $isondate = db('connsellor_ondate')->where(['memberid'=>$value['memberid'],'status'=>0])->whereTime('ondatetime','today')->find();
                if ($ondate[0]==1&&!$isondate) {//在线并 今日没空的删除
                    unset($counsellor['list'][$key]);
                    continue;
                }
                if ($ondate[0]==0&&$isondate) {//不在线 今日设置有空的删除
                    unset($counsellor['list'][$key]);
                    continue;
                }
            }
            
            if (is_numeric($counsellor['list'][$key]['avar'])) {
                $counsellor['list'][$key]['avar'] = get_file_path($counsellor['list'][$key]['avar']);
            }  

            //标识
            $smap['id'] = array('in',$value['tags']);
            $counsellor['list'][$key]['sign'] = implode('|', db('cms_category')->where($smap)->column('title')) ;
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$counsellor
        ];
        return json($data);
    }

    /**
     * [clca_custom 课程活动]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function clca_custom($params)
    {

        $cateid = trim($params['cateid']);
        $page_no = trim($params['page_no']);
        $page_size = trim($params['page_size']);

        //活动结束的过滤
        $map['endtime'] = array('gt',time());

        if ($cateid != 'all') {
            $map['cateid'] = $cateid;
        }

        $startpg = ($page_no-1)*$page_size;
        $data = db('cms_clac_temp')->where($map)->order('id DESC')->limit($startpg, $page_size)->select();
        foreach ($data as $key => $value) {
            $data[$key]['pic'] =  get_file_path($value['pic']);
        }
        $pages = array(
                'total'=>db('cms_clac_temp')->where($map)->count()
            );
        $trade['data']['pagers'] = $pages;
        $trade['data']['list'] = array_values($data);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$trade
        ];
        return json($data);
    }

    /**
     * [clacsearch_custom description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function clacsearch_custom($params)
    {

        $kw = trim($params['kw']);
        $map = array();
        if ($kw) {
            $map['title'] = array('like','%'.$kw.'%');
        }
        $data = db('cms_clac_temp')->where($map)->order('id DESC')->select();
        foreach ($data as $key => $value) {
            $data[$key]['pic'] =  get_file_path($value['pic']);
        }
        $pages = array(
                'total'=>db('cms_clac_temp')->where($map)->count()
            );
        $trade['data']['pagers'] = $pages;
        $trade['data']['list'] = array_values($data);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$trade
        ];
        return json($data);

    }

    /**
     * [clcadetail 课程活动祥情]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function clcadetail_custom($params)
    {
        $type = trim($params['typeid']);
        $acid = trim($params['acid']);
        
        $map['id'] = $acid;
        if ($type==0) {//课程
            $paytype = 2;
            $info = db('cms_classes')->where($map)->find();
        }
        if ($type==1) {//活动
            $paytype = 3;
            $info = db('cms_active')->where($map)->find();
        }

        if ($info) {
            $info['pic'] = get_file_path($info['pic']);
            if (is_numeric($info['audio'])) {
                $info['audio'] = get_file_path($info['audio']);
            }

            $info['islimit'] = 0;
            //处理可报名数
            $ispaynum  =  db('trade')->where(['classid'=>$acid,'paytype'=>$paytype,'status'=>1])->count();
            if ($ispaynum>=$info['limitnum']) {
                $info['islimit'] = 1;
            }


            $info['isfav'] = 0;//是否收藏

            $tm['paytype'] = $type==0?2:3;
            $tm['classid'] = $acid;
            $tm['status'] = 1;
            $info['num'] = db('trade')->where($tm)->count();
            $info['ispay'] = 0;
            //登录状态
            if (isset($params['account'])) {//用户id
                $amap['type'] = $type==0?1:2;
                $amap['fid'] = $acid;
                $amap['mid'] = $params['account'];
                if (db('cms_fav')->where($amap)->find()) {
                   $info['isfav'] = 1;
                }
                //是否已报名
                $bm['paytype'] = $type==0?2:3;
                $bm['classid'] = $acid;
                $bm['status'] = 1;
                $bm['memberid'] = $params['account'];
                if (db('trade')->where($bm)->count()) {
                    $info['ispay'] = 1;
                }
                
            }
            //回复
            $rmap['type'] = $type;
            $rmap['classid'] = $acid;
            $rmap['status'] = 1;
            $reply = db('cms_reply')->where($rmap)->select();
            foreach ($reply as $key => $value) {
                $reply[$key]['suname'] = db('member')->where(['id'=>$value['suid']])->value('nickname');
                if ($value['ruid']) {
                    $reply[$key]['runame'] = db('member')->where(['id'=>$value['ruid']])->value('nickname');
                }
            }
            $info['reply'] = $reply ;


        }
        

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$info
        ];
        return json($data);
    }

    /**
     * [addfav 添加收藏]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function addfav_custom($params)
    {
        $type = trim($params['typeid']);
        $fid = trim($params['fid']);
        $mid = trim($params['account']);

        $map['type'] = $type;
        $map['fid'] = $fid;
        $map['mid'] = $mid;
        if (!db('cms_fav')->where($map)->find()) {
            $data['type'] = $type;
            $data['fid'] = $fid;
            $data['create_time'] = time();
            $data['mid'] = $mid;
            db('cms_fav')->insert($data);
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }
    /**
     * [delfav description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function delfav_custom($params)
    {
        $type = trim($params['typeid']);
        $fid = trim($params['fid']);
        $mid = trim($params['account']);

        $map['type'] = $type;
        $map['fid'] = $fid;
        $map['mid'] = $mid;
        if (db('cms_fav')->where($map)->find()) {
            db('cms_fav')->where($map)->delete();
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [favlist 收藏列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function favlist_custom($params)
    {

        $type = trim($params['typeid']);
        $mid = trim($params['account']);

        $map['type'] = $type;
        if ($type==1||$type==2) {
            $map['type'] = array('in','1,2');
        }
        $map['mid'] = $mid;
        $info = db('cms_fav')->where($map)->select();

        $modl = db('member');
        switch ($type) {
            case '0':
                $modl = db('member');
                break;
            case '1':
                $modl = db('cms_classes');
                break;
            case '2':
                $modl = db('cms_active');
                break;
            case '3':
                $modl = db('cms_page');
                break;
            
            default:
                $modl = db('member');
                break;
        }

        $data = [];
        //取数据
        foreach ($info as $key => $value) {
            $pop = $modl->where(['id'=>$value['fid']])->find();
            @$data[$key]['title'] = isset($pop['nickname'])?$pop['nickname']:$pop['title'];
            if ($type==0) {
                if (is_numeric($pop['avar'])) {
                   $data[$key]['pic'] =  get_file_path($pop['avar']);
                }else{
                    $data[$key]['pic'] =  $pop['avar'];
                }
                $mcounsellor = db('member_counsellor')->where(['memberid'=>$value['fid']])->find();
                //tearch
                $data[$key]['tearch'] = $mcounsellor['tearch'];
                //leader
                $data[$key]['leader'] = $mcounsellor['leader'];
                //nickname
                $data[$key]['nickname'] = $pop['nickname'];
                //slogan
                $data[$key]['slogan'] = $pop['slogan'];
                //online
                $data[$key]['online'] = $mcounsellor['online'];
                //memberid
                $data[$key]['memberid'] = $value['fid'];
                //订单数
                $data[$key]['trade'] = db('trade')->where(array('status'=>1,'mid'=>$mcounsellor['memberid']))->count();
                //标识
                $smap['id'] = array('in',$mcounsellor['tags']);
                $data[$key]['signarr'] =  db('cms_category')->where($smap)->column('title') ;
                //星级
                $data[$key]['start'] = db('member')->alias('a')->field('e.*')->join(' trade b',' b.mid = a.id','LEFT')->join(' calendar c',' c.tid = b.id','LEFT')->join(' evaluate e',' e.cid = c.id','LEFT')->where(array('a.id'=>$mcounsellor['memberid']))->avg('sorce');
                //少于4星默认 4星
                if ($data[$key]['start']<8) {
                    $data[$key]['start'] = (round(8 / 10, 2)*100).'%';
                }else{
                    $data[$key]['start'] = (round($data[$key]['start'] / 10, 2)*100).'%';
                }
                //分中心
                $data[$key]['shopname'] = $pop['shopid']?db('shop_agency')->where(['id'=>$pop['shopid']])->value('city'):'中国大陆';

            }
            if ($type==3) {
                if (is_numeric($pop['cover'])) {
                   $data[$key]['pic'] =  get_file_path($pop['cover']);
                }else{
                    $data[$key]['pic'] =  $pop['cover'];
                }
                //文章简介
                $data[$key]['description'] =  $pop['description'];
                //作者
                $data[$key]['author'] =  $pop['userid']?db('member')->where(['id'=>$pop['userid']])->value('nickname'):$pop['author'];
            }

            if ($type==1||$type==2) {
               $data[$key]['pic'] =  get_file_path($pop['pic']);
               $data[$key]['typeid'] = $type==1?0:1;
               $data[$key]['address'] = $pop['address'];
               $data[$key]['start_time'] = $pop['start_time'];

            }
            $data[$key]['id'] = $pop['id'];
            
        }

        //返回信息
        $res = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$data
        ];
        return json($res);
    }

    /**
     * [clacorder_custom 课程活动订单列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function clacorder_custom($params)
    {
        // $type = trim($params['typeid']);//订单类型
        $mid = trim($params['account']);
        $map['paytype'] = array('in','2,3');//2课程 或是 3 活动
        $map['memberid']= $mid;
        $map['status'] = 1;
        //订单数据
        // $info = db('trade')->join(' member_counsellor b',' b.memberid = a.id','LEFT')->where($map)->select();
        $info = db('trade')->where($map)->select();
        $data = [];
        foreach ($info as $key => $value) {
            if ($value['paytype']==2) {//课程
                $sm['id'] = $value['classid'];
                $r = db('cms_classes')->where($sm)->find();
                if ($r) {
                    $data[$key]['pic'] =  get_file_path($r['pic']);
                    $data[$key]['title'] =  $r['title'];
                    $data[$key]['address'] =  $r['address'];
                    $data[$key]['start_time'] =  $r['start_time'];
                    $data[$key]['typeid'] = $value['paytype']==2?0:1;
                    $data[$key]['id'] = $r['id'];
                }else{
                    continue;
                }
                
            }

            if ($value['paytype']==3) {//活动
                $sm['id'] = $value['classid'];
                $r = db('cms_active')->where($sm)->find();
                if ($r) {
                    $data[$key]['pic'] =  get_file_path($r['pic']);
                    $data[$key]['title'] =  $r['title'];
                    $data[$key]['address'] =  $r['address'];
                    $data[$key]['start_time'] =  $r['start_time'];
                    $data[$key]['typeid'] = $value['paytype']==3?1:0;
                    $data[$key]['id'] = $r['id'];
                }else{
                    continue;
                }
                
            }

            //查看评价
            $actype = $value['paytype'] ==2?0:1;
            $data[$key]['claceva'] = db('cms_reply')->where(['classid'=>$value['classid'],'type'=>$actype,'suid'=>$mid])->count();
            // $data[$key]['claceva'] = db('clac_evaluate')->where(['cid'=>$value['classid'],'actype'=>$actype,'memberid'=>$mid])->count();
        }
        //返回信息
        $res = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$data
        ];
        return json($res);


    }
    /**
     * [createClacTrade_custom description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function createClacTrade_custom($params)
    {
        $clacid = trim($params['clacid']);
        $account = trim($params['account']);
        $paytype = trim($params['paytype']);


        
        //取商品
        $map['id'] = $clacid;
        if ($paytype==2) {//课程
            $goodsinfo = db('cms_classes')->where($map)->find();
        }
        if ($paytype==3) {//活动
            $goodsinfo = db('cms_active')->where($map)->find();
        }
        
        

        //shopid  uid tid payment title paytype
        
        $data['classid'] = $clacid;

        $data['memberid'] = $account;
        $data['payment'] = $goodsinfo['price'];
        $data['created_time'] = time();
        $data['num'] = 1;
        $data['paytype'] = $paytype;

        
        $username = db('member')->where('id',$account)->column('nickname');

        $data['title'] = $username[0].'购买了'.$goodsinfo['title'];
        
        //机构 取咨询师机构counsellor_id
        $data['shopid'] = db('member')->where('id',$account)->value('shopid');
        
        //订单号
        $data['tid'] = date('YmdHis',time()).rand(1000,9999);
        //插入数据
        $trade = db('trade')->insert($data);
        if (!$trade) {
            return $this->error('生成订单失败');
        }

        //生成消息
        // $msg['type'] = 1;
        // $msg['subtitle'] = '预约'.$counsellor[0].$str;
        // $msg['title'] = '预约您的'.$str;
        // $msg['descrption'] = $username[0].'预约您的'.$str;
        // $msg['display'] = $username[0].'预约'.$counsellor[0].'的'.$str;
        // $msg['sendid'] = $account;
        // $msg['reciveid'] = $counsellor_id;
        // $msg['tid'] = $data['tid'];

        // $lastid = $this->create_msg($msg);
        
        $ret = array('tid'=>$data['tid'],'price'=>$goodsinfo['price']);

        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$ret
        ];
        return json($data);
    }

    /**
     * [clacshare_custom 分享]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function clacshare_custom($params)
    {
        $account = trim($params['account']);

        $set = db('cms_coupon_set')->find();
        if ($set) {
            $map['memberid'] = $account;
            if (db('cms_coupon')->where($map)->whereTime('created_time','today')->find()) {
                return $this->error('今日优惠券已发完，请明天参与');   
            }
            $n = rand($set['min'], $set['max']);
            $data['title'] = $n.'元抵用券';
            $data['price'] = $n;
            $data['created_time'] = time();
            $data['memberid'] = $account;

            db('cms_coupon')->insert($data);
            //返回信息
            $data = [
                'code'=>'1',
                'msg'=>'',
                'data'=>$data['title']
            ];
            return json($data);
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [couponmy_custom 我的可使用优惠券]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function couponmy_custom($params)
    {
        $account = trim($params['account']);

        //
        $map['use'] = 0;
        $map['memberid'] = $account;
        $info = db('cms_coupon')->where($map)->select();

        $rs['list'] = array_values($info);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);
    }

    /**
     * [couponlist_custom 我的可使用优惠券]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function couponlist_custom($params)
    {
        $account = trim($params['account']);

        //
        $map['memberid'] = $account;
        $map['use'] = 0;
        $info = db('cms_coupon')->where($map)->select();

        $ret = [];
        foreach ($info as $key => $value) {

            $ret[$key]['stautstx'] = $value['use']==0?'未使用':'已使用';
            $ret[$key]['title'] = $value['title'];
            $ret[$key]['created_time'] = $value['created_time'];
            $ret[$key]['status'] = $value['status'];
        }
        $rs['list'] = array_values($ret);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);
    }
    /**
     * [feedbackup_custom 反馈]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function feedbackup_custom($params)
    {

        $account = trim($params['account']);
        $ph = trim($params['ph']);
        $ct = trim($params['ct']);

        $data['uid'] = $account;
        $data['phone'] = $ph;
        $data['content'] = $ct;
        $data['create_time'] = time();

        db('cms_feedback')->insert($data);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);

    }
    /**
     * [clcareply_custom ]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function clcareply_custom($params)
    {
        $account = trim($params['account']);
        $typeid = trim($params['typeid']);
        $acid = trim($params['acid']);
        $msg = trim($params['msg']);

        if (isset($params['ruid'])) {
            $data['ruid'] = $params['ruid'];
        }

        $data['source'] = 8;
        $data['classid'] = $acid;
        $data['type'] = $typeid;
        $data['suid'] = $account;
        $data['msg'] = $msg;
        $data['created_time'] = time();

        db('cms_reply')->insert($data);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [sendhxmsg_custom description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function sendhxmsg_custom($params)
    {
        $sendid = trim($params['account']);
        $reciveid = trim($params['reciveid']);
        $msg = trim($params['msg']);
        $status = $params['status'];
        // $status = 0;
        // if (isset($params['status'])) {
        //    $status = $params['status'];
        // }


        $data['sendid'] = $sendid;
        $data['reciveid'] = $reciveid;
        $data['msg'] = $msg;
        $data['status'] = $status;
        $data['create_time'] = time();
        //二人会话标识
        $data['tag'] = 'u'.$sendid.'c'.$reciveid;

        db('hx_msg')->insert($data);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [hxmsgls_custom 会话列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function hxmsgls_custom($params)
    {

        $sendid = trim($params['account']);

        $map['sendid|reciveid'] = $sendid;
        $info = db('hx_msg')->where($map)->order('create_time DESC')->group('tag')->select();

        $now = date('Y-m-d',time());
        $res = [];
        //头像 时间
        foreach ($info as $key => $value) {
            //msg
            $smsg['tag'] = $value['tag'];
            $news = db('hx_msg')->where($smsg)->order('id DESC')->find();
            $res[$key]['nmsg'] = $news['msg'];

            //avar
            if ($news['sendid']==$sendid) {//取 rc头像
                $su = db('member')->where(['id'=>$news['reciveid']])->find();
                if (!$su) {
                    unset($info[$key]);
                    unset($res[$key]);
                    continue;
                }
                $res[$key]['cavar'] = is_numeric($su['avar'])?get_file_path($su['avar']):$su['avar'];
                $res[$key]['nickname'] = $su['nickname'];//name
                $res[$key]['mid'] = $su['id'];//id
                $res[$key]['account'] = $su['username'];//id
                $res[$key]['sendid'] = 1;//是发送者
            }else{
                $su = db('member')->where(['id'=>$news['sendid']])->find();
                if (!$su) {
                    unset($info[$key]);
                    unset($res[$key]);
                    continue;
                }
                $res[$key]['cavar'] = is_numeric($su['avar'])?get_file_path($su['avar']):$su['avar'];
                $res[$key]['nickname'] = $su['nickname'];//name
                $res[$key]['mid'] = $su['id'];//id
                $res[$key]['account'] = $su['username'];//id
                $res[$key]['sendid'] = 0;//是接受者
            }
            
            //newtime
            $newtime[$key] = $news['create_time']; 

            //time
            $res[$key]['times'] = date('Y-m-d',$news['create_time']) == $now? date('H:i',$news['create_time']):date('Y-m-d H:i',$news['create_time']);
            //状态
            $res[$key]['st'] = $news['status'];
        }
        array_multisort($newtime, SORT_DESC, $res);
        $rt['list'] = $res;
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$res
        ];
        return json($data);
    }

    /**
     * [hxmsgtwo_custom 二人沟通记录]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function hxmsgtwo_custom($params)
    {
        $sendid = trim($params['account']);
        $reciveid = trim($params['reciveid']);

        $now = date('Y-m-d',time());

        // $map['sendid'] = $sendid;
        // $map['reciveid'] = $reciveid;
        $map['tag'] = 'u'.$sendid.'c'.$reciveid;
        // $info =  db('hx_msg')->where($map)->order('id DESC')->limit(10)->select();
        $info =  db('hx_msg')->where($map)->order('id DESC')->select();
        //获得头像处理
        foreach ($info as $key => $value) {
             $ids[$key] = $value['id'];
            if ($value['sendid'] == $sendid) {
                $info[$key]['isme'] = 1;
                // @$info[$key]['rcavar'] = is_numeric(db('member')->where(['id'=>$value['reciveid']])->value('avar'))?get_file_path(db('member')->where(['id'=>$value['reciveid']])->value('avar')):db('member')->where(['id'=>$value['reciveid']])->value('avar');
                // @$info[$key]['sdavar'] = is_numeric(db('member')->where(['id'=>$value['sendid']])->value('avar'))?get_file_path(db('member')->where(['id'=>$value['sendid']])->value('avar')):db('member')->where(['id'=>$value['sendid']])->value('avar');
            }else{
                $info[$key]['isme'] = 0;
                // @$info[$key]['rcavar'] = is_numeric(db('member')->where(['id'=>$value['sendid']])->value('avar'))?get_file_path(db('member')->where(['id'=>$value['sendid']])->value('avar')):db('member')->where(['id'=>$value['sendid']])->value('avar');
                // @$info[$key]['sdavar'] = is_numeric(db('member')->where(['id'=>$value['reciveid']])->value('avar'))?get_file_path(db('member')->where(['id'=>$value['reciveid']])->value('avar')):db('member')->where(['id'=>$value['reciveid']])->value('avar');
            }
            
            // @$info[$key]['rcavar'] = is_numeric(db('member')->where(['id'=>$value['reciveid']])->value('avar'))?get_file_path(db('member')->where(['id'=>$value['reciveid']])->value('avar')):db('member')->where(['id'=>$value['reciveid']])->value('avar');
            @$info[$key]['sdavar'] = is_numeric(db('member')->where(['id'=>$value['sendid']])->value('avar'))?get_file_path(db('member')->where(['id'=>$value['sendid']])->value('avar')):db('member')->where(['id'=>$value['sendid']])->value('avar');
            $info[$key]['times'] = date('Y-m-d',$value['create_time']) == $now? date('H:i:s',$value['create_time']):date('Y-m-d H:i:s',$value['create_time']);
        }
        array_multisort($ids, SORT_ASC, $info);
        $rs['list'] = $info; 
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);
    }


    /**
     * [hxmsgup_custom up消息状态]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function hxmsgup_custom($params)
    {

        $sendid = trim($params['account']);
        $reciveid = trim($params['reciveid']);

        //二人会话标识
        // $map['tag'] = array('in',['u'.$sendid.'c'.$reciveid,'u'.$reciveid.'c'.$sendid]) ;
        $map['sendid'] = $sendid ;
        $map['reciveid'] = $reciveid ;
        db('hx_msg')->where($map)->update(['status'=>1]);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }
    /*
    |--------------------------------------------------------------------------
    | 商家版API
    |--------------------------------------------------------------------------
     */ 

    /**
     * login 用户端
     * @param string $value [description]
     */
    public function login_shop($params)
    {   
        //参数手机号，密码
        
        $username = trim($params['account']);
        $password = trim($params['password']);
        $isshop = trim($params['isshop']);

        //是否存在
        $map['username'] = $username;
        $map['status'] = 1;
        $map['type'] = 1;
        $user = db('member')->where($map)->find();
        if (!$user) {
            return $this->error('用户不存在或被禁用！');
        }
        if ($user['shopid']) {
            $user['agency'] = db('shop_agency')->where('id',$user['shopid'])->value('title');
        }else{
            $user['agency'] = '';
        }
        
        //密码是否正确
        if (!Hash::check((string)$password, $user['password'])) {
           return $this->error( '密码错误！');
        }

        if ($isshop) {
             session('user_counsellor_auth',$user);
        }
        if (is_numeric($user['avar'])) {
            $user['avar'] = get_file_path($user['avar']);
        }
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$user
        ];
        return json($data);
    }

    /**
     * [logout_shop description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function logout_shop($params)
    {
        $username = trim($params['account']);
        $isshop = trim($params['isshop']);
        if ($isshop) {
             session('user_counsellor_auth',null);
        }

        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);

    }
    /**
     * 注册
     * @return [type] [description]
     */
    public function register_shop($params)
    {
        //参数
        $data['username'] = trim($params['mobile']);
        $data['nickname'] = trim($params['account']);
        $data['email'] = trim($params['email']);
        $data['mobile'] = trim($params['mobile']);
        $data['shopid'] = trim($params['agency']);
        $data['create_time'] = time();

        if (db('member')->where(['mobile'=>$data['mobile']])->find()) {
            return $this->error('账号已存在！');
        }

        $data['type'] = 1;
        $data['status'] = 0;

        //生成密码
        $data['password'] =  Hash::make((string)trim($params['password']));

        //插入数据
        $me = db('member')->insert($data);

        //生成咨询师默认信息
        $cdata['memberid'] = $me;
        db('member_counsellor')->insert($cdata);
        if (!$me) {
            return $this->error('注册失败！请稍后重试');
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$me
        ];
        return json($data);
    }

    /**
     * 验证码 暂没
     * @return [type] [description]
     */
    public function vcode_shop($params)
    {
        //参数
        $account = trim($params['mobile']);

        //短信
        $code = $this->sendmsg($account);
        if (!$code) {
            return $this->error('发送失败，1小时只能获得3次');
        }
        
        //生成session 
        cache($account.'code',$code);

        //设置过期时间
        cache($account.$code, time() + 1800) ;

        $map['username'] = $account;
        $user = db('member')->where($map)->find();
        if (!$user) {
            return $this->error('手机号不存在');
        }

        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$code
        ];
        return json($data);
    }

    /**
     * 注册 验证
     * @return [type] [description]
     */
    public function confirm_shop($params)
    {
        //参数
        $username = trim($params['mobile']);
        $code = trim($params['code']);
        
        // if (!session($username.$code)) {
        //     return $this->error('验证码不正确');
        // }
        
        //检查过期时间
        if (cache($username.$code)&&cache($username.$code)<time()) {
            return $this->error('验证码已过期');
        }
        
        //检查是否正确
        if (cache($username.'code')!=$code) {
            return $this->error('验证码不正确');
        }

        //更新状态
        $data['status'] = 0;
        
        $map['username'] = $username;
        if(!db('member')->where($map)->update($data)){
            // return $this->error('服务器忙，请稍后');
        }
        
        //注销session
        // cache($username.$code,null);
        // cache($username.'code',null);

        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [用户信息]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function userinfo_shop($params)
    {
        //params
        $username = trim($params['account']);

        //是否存在
        $map['username'] = $username;
        // $map['type'] = 1;
        //用户信息
        $user = db('member')->where($map)->find();
        if (!$user) {
            return $this->error('用户不存在');
        }
        // get_file_path
        if (is_numeric($user['avar'])) {
            $user['avar'] = get_file_path($user['avar']);
        }
        //用户积分
        $pmap['memberid'] = $user['id'];
        $pmap['behavior_type'] = 0;
        $user['point'] = db('member_point')->where($pmap)->sum('point');

        //简介
        $user['intro'] = db('member_counsellor')->where(['memberid'=>$user['id']])->value('intro');
         //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$user
        ];
        return json($data);

    }

    /**
     * [article_shop 首页好文章]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function article_shop($params)
    {

        $article['list'] = db('cms_page')->where('status',1)->order('view DESC')->limit(10)->select();

        foreach ($article['list'] as $key => $value) {
            unset($article['list'][$key]['content']);
            $value['author'] = $value['author']?$value['author']:'大观心理咨询中心';
            $article['list'][$key]['author'] = $value['userid']==0?$value['author']:db('member')->where('status',1)->value('nickname');
            $article['list'][$key]['cover'] = get_file_path($value['cover']);
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$article
        ];
        return json($data);

    }

    /**
     * [lunbo 首页轮播]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function lunbo_shop($params)
    {
        $map['tagname'] = 'shop';
        $map['status'] = 1;
        $isshop = trim($params['isshop']);
        $lunbo['pic'] = db('cms_advert')->where($map)->order('id DESC')->limit(10)->select();
        foreach ($lunbo['pic'] as $key => $value) {
            if (strstr($value['link'], 'article')) {//文章
                if ($isshop) {
                    $lunbo['pic'][$key]['webview'] = "/counsellor.php/artical/detail.html";
                    $lunbo['pic'][$key]['webparam'] = explode('.',explode('/', $value['link'])[1])[0];
                }else{
                    $lunbo['pic'][$key]['webview'] = '_www/view/artical/detail.html';
                    $lunbo['pic'][$key]['webparam'] = ['article_id'=>explode('.',explode('/', $value['link'])[1])[0]];
                }
                
                 
            }else if (strstr($value['link'], 'counsellor')) {
                if ($isshop) {
                    $lunbo['pic'][$key]['webview'] = "/counsellor.php/counsellor/detail.html";
                     $lunbo['pic'][$key]['webparam'] = explode('.',explode('/', $value['link'])[1])[0];
                }else{
                    $lunbo['pic'][$key]['webview'] = '_www/view/counsellor/detail.html';
                     $lunbo['pic'][$key]['webparam'] = ['counsellor_id'=>explode('.',explode('/', $value['link'])[1])[0]];
                }
                
               
            }else if (strstr($value['link'], 'clac')) {
                if ($ismobile) {
                    $lunbo['pic'][$key]['webview'] = "/mobile.php/clac/detail.html";
                    $lunbo['pic'][$key]['webparam'] = explode('.',explode('/', $value['link'])[1])[0];
                    $lunbo['pic'][$key]['webparam1'] = 0;
                }else{
                    $lunbo['pic'][$key]['webview'] = '_www/view/clac/detail.html';
                     $lunbo['pic'][$key]['webparam'] = ['acid'=>explode('.',explode('/', $value['link'])[1])[0],'actype'=>0];
                }
                
               
            }else if (strstr($value['link'], 'act')) {
                if ($ismobile) {
                    $lunbo['pic'][$key]['webview'] = "/mobile.php/clac/detail.html";
                     $lunbo['pic'][$key]['webparam'] = explode('.',explode('/', $value['link'])[1])[0];
                     $lunbo['pic'][$key]['webparam1'] = 1;
                }else{
                    $lunbo['pic'][$key]['webview'] = '_www/view/clac/detail.html';
                     $lunbo['pic'][$key]['webparam'] = ['acid'=>explode('.',explode('/', $value['link'])[1])[0],'actype'=>1];
                }
                
               
            }else{
                $class['pic'][$key]['webview'] = '';
                $class['pic'][$key]['webparam'] = [];
            }

            // if (strstr($value['link'], 'article')) {//文章
            //     $lunbo['pic'][$key]['webview'] = '_www/view/article/detail.html';
            //     $lunbo['pic'][$key]['webparam'] = ['id'=>explode('.',explode('/', $value['link'])[1])[0]]; 
            // }else if (strstr($value['link'], 'counsellor')) {
            //     $lunbo['pic'][$key]['webview'] = '_www/view/counsellor/detail.html';
            //     $lunbo['pic'][$key]['webparam'] = ['id'=>explode('.',explode('/', $value['link'])[1])[0]];
            // }

        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$lunbo
        ];
        return json($data);
    }
    /**
     * [category_shop 咨询分类]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function category_shop($params)
    {
        $category['list'] = db('cms_category')->where('status',1)->order('id DESC')->limit(8)->select();
        foreach ($category['list'] as $key => $value) {
            $category['list'][$key]['cover'] = get_file_path($value['cover']);
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$category
        ];
        return json($data);
    }

    /**
     * [claccate_shop 文章分类]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function claccate_shop($params)
    {
        $ret = array();

        $cates = db('cms_clacategory')->where('status',1)->order('sort DESC,id DESC')->limit(4)->select();

        foreach ($cates as $key => $value) {
            if (is_numeric($value['cover'])) {
                $cates[$key]['cover'] = get_file_path($value['cover']);
            }
        }
        

        $rs['data'] = array_values($cates);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);
    }
    /**
     * [articalcate_custom 文章分类]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function articalcate_shop($params)
    {
        $ret = array();

        $cates = db('cms_articlecategory')->where('status',1)->order('sort DESC,id DESC')->limit(8)->select();

        $rs['data'] = array_values($cates);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);
    }
    /**
     * [recommend_shop 推荐咨询师]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function recommend_shop($params)
    {
        $recommend['list'] = db('member')->alias('a')->field('a.*,b.*')->join(' member_counsellor b',' b.memberid = a.id','LEFT')->where(array('a.status'=>1,'a.type'=>1))->order('a.recommond DESC')->select();

        foreach ($recommend['list'] as $key => $value) {
            //订单数
            $recommend['list'][$key]['trade'] = db('trade')->where(array('status'=>1,'mid'=>$value['memberid']))->count();
            //标识
            $smap['id'] = array('in',$value['tags']);
            $recommend['list'][$key]['sign'] = implode('|', db('cms_category')->where($smap)->column('title')) ;
            //从业时间
            $recommend['list'][$key]['employment'] = '从业'.ceil(date('Y',time())-date('Y',$value['employment'])).'年';
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$recommend
        ];
        return json($data);
    }
    /**
     * [counsellor_custom 咨询师]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function counsellor_shop($params)
    {

        if (!trim($params['id'])) {
            return $this->error('参数缺失！');
        }
        $counsellor =  db('member')->alias('a')->field('a.*,b.*')->join(' member_counsellor b',' b.memberid = a.id','LEFT')->where(array('a.id'=>$params['id']))->find();
        if (!$counsellor) {
            return $this->error('咨询师不存在或是已注销');
        }

        //咨询地点 
        $counsellor['spids'] = $counsellor['id'];
        if ($counsellor['shopids']) {
            $spids = $counsellor['shopids'].','.$counsellor['shopid'];
            $counsellor['spids'] = implode(',', array_unique(explode(',', $spids))) ;
        }
        $sm['id'] = array('in',$counsellor['spids']);
        $shopnm = db('shop_agency')->where($sm)->select();
        foreach ($shopnm as $key => $value) {
            $shopnm[$key]['ischecked'] = in_array($value['id'], explode(',', $counsellor['openshop']))?1:0;
            
        }
        $counsellor['shopnm'] = $shopnm;
        //咨询方式
        $chartarr = array(
            array(
                'chart'=>'wordchart',
                'show'=>'文字咨询'
            ),
            array(
                'chart'=>'speechchart',
                'show'=>'语音咨询'

            ),
            array(
                'chart'=>'videochart',
                'show'=>'视频咨询'
            ),
            array(
                'chart'=>'facechart',
                'show'=>'面对面咨询'
            )
        );
        foreach ($chartarr as $key => $value) {
            $chartarr[$key]['ischecked'] = in_array($value['chart'], explode(',', $counsellor['openchart']))?1:0;
        }
        $counsellor['chararr'] = $chartarr;
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$counsellor
        ];
        return json($data);
    }
    
    /**
     * [counsellorst_shop 咨询设置]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function counsellorst_shop($params)
    {
        $id = $params['id'];

        $online = $params['online'];
        $shopids = $params['shopids'];
        $openchart = $params['openchart'];

        if (!$id) {
            return $this->error('参数必填');
        }

        $sa['openshop'] = $shopids;
        $sa['openchart'] = $openchart;
        $sa['online'] = $online;
        db('member')->where('id',$id)->update($sa);

        $cs['online'] = $online;
        db('member_counsellor')->where('memberid',$id)->update($cs);

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [point 积分明细]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function point_shop($params)
    {
       if (!trim($params['account'])) {
            return $this->error('参数缺失！');
        }
        $member['list'] =  db('member')->alias('a')->field('a.*,b.*')->join(' member_point b',' b.memberid = a.id','LEFT')->where(array('a.username'=>$params['account']))->select();

        if (!$member) {
            return $this->error('用户不存在');
        }
        $pmap['memberid'] = $member['list'][0]['memberid'];
        $pmap['behavior_type'] = 0;
        $member['points'] = db('member_point')->where($pmap)->sum('point');
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$member
        ];
        return json($data);
    }

    

    /**
     * [articallist_shop 文章列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function articallist_shop($params)
    {

        $keyword = trim($params['search_keywords']);

        $map['title'] = array('like','%'.$keyword.'%');
        $map['status'] = 1;
        
        $article['list'] = db('cms_page')->where($map)->order('view DESC')->select();

        foreach ($article['list'] as $key => $value) {
            unset($article['list'][$key]['content']);
            $article['list'][$key]['author'] = $value['userid']==0?'ADMIN':db('member')->where('status',1)->column('nickname');
            $article['list'][$key]['cover'] = get_file_path($value['cover']);
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$article
        ];
        return json($data);
    }

    /**
     * [contentinfo_shop 文章祥情]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function contentinfo_shop($params)
    {

        $id = $params['id'];
        if (!$id) {
            return $this->error('参数必填');
        }
        $article = db('cms_page')->where('id',$id)->find();

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$article
        ];
        return json($data);

    }
    /**
     * [checkpassword 验证密码]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function checkpassword_shop($params)
    {
        $password = trim($params['password']);
        $account = trim($params['account']);

        //设置密码
        // $data['password'] =  Hash::make((string)$password);


        //更新
        $map['id'] = $account;
        $user =  db('member')->where($map)->find();
        if (!Hash::check((string)$password, $user['password'])) {
           return $this->error( '密码错误！');
        }

        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);

    }

    /**
     * [uppw_shop 更新密码]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function uppw_shop($params)
    {
        $password = trim($params['password']);
        $password_confirmation = trim($params['password_confirmation']);
        $account = trim($params['account']);
        if ($password!=$password_confirmation) {
            return $this->error('二次密码不一致');
        }
        //设置密码
        $data['password'] =  Hash::make((string)trim($password));

        //更新
        $map['id'] = $account;
        if(!db('member')->where($map)->update($data)){
            return $this->error('服务器忙，请稍后');
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [msg_shop 消息列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function msg_shop($params)
    {
        $id = trim($params['account']);

        //查询消息
        $map['reciveid'] = $id;
        $user =  db('msg')->where($map)->order('create_time DESC')->select();
        $ret = [];
        foreach ($user as $key => $value) {
            $ret[$value['type']][$key] = $value;
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$ret
        ];
        return json($data);
    }
    /**
     * [msginfo_shop 消息祥情]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function msginfo_shop($params)
    {
        $id = trim($params['id']);

        //查询消息
        $map['id'] = $id;
        $msg =  db('msg')->where($map)->find();
        

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$msg
        ];
        return json($data);
    }

    /**
     * [msgup_shop 消息状态更新]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function msgup_shop($params)
    {
        $id = trim($params['id']);
        $type = trim($params['type']);

        if ($type==1) {
            //查询消息
            $map['id'] = $id;
            $data['status'] = 1;
            if (!db('msg')->where($map)->update($data)) {
                // $this->error('更新失败！');
            }
        }else{
            //查询消息
            $map['id'] = $id;
            $notice = db('cms_notice')->where($map)->find();
            $account = trim($params['account']);

            $smap['reciveid'] = $account;
            $smap['noticeid'] = $id;
            if (!db('msg')->where($smap)->find()) {
                $data['title'] = $notice['title'];
                $data['descrption'] = $notice['content'];
                $data['reciveid'] = $account;
                $data['create_time'] = time();
                $data['status'] = 1;
                $data['type'] = 0;
                $data['noticeid'] = $id;
                db('msg')->insert($data);

                // $this->error('更新失败！');
            }
        }
        
        

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }
    /**
     * [income_shop 收入]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function income_shop($params)
    {
        $account = trim($params['account']);
        $map['mid'] = $account;
        $map['status'] = 1;
        
        $trade['list'] = db('trade')->where($map)->order('id DESC')->select();

        $clarr =db('shop_classes_allot')->alias('a')->join('cms_classes b',' b.id = a.classid','LEFT')->where('adminid','like',$account)->whereOr('tearchid','like','%'.$account.'%')->whereOr('coachid','like','%'.$account.'%')->select();

        $cltrarr = [];
        foreach ($clarr as $key => $value) {
            if ($value['adminid']!= $account &&!in_array($account, explode(',', $value['tearchid']) )&&!in_array($account, explode(',', $value['coachid']) ) ) {
                unset($clinc[$key]);

            }
            $clm['classid'] = $value['classid'];
            $clm['paytype'] = 2;
            $cltrarr = db('trade')->where($clm)->order('id DESC')->select();
        }

        $trade['list'] = array_merge($trade['list'],$cltrarr);

        $acarr =db('shop_acitve_allot')->alias('a')->join('cms_active b',' b.id = a.activeid','LEFT')->where('adminid','like',$account)->whereOr('tearchid','like','%'.$account.'%')->whereOr('coachid','like','%'.$account.'%')->select();

        $actrarr = [];
        foreach ($acarr as $key => $value) {
            if ($value['adminid']!= $account &&!in_array($account, explode(',', $value['tearchid']) )&&!in_array($account, explode(',', $value['coachid']) ) ) {
                unset($acarr[$key]);

            }
            $acm['classid'] = $value['classid'];
            $acm['paytype'] = 2;
            $actrarr = db('trade')->where($acm)->order('id DESC')->select();
        }

        $trade['list'] = array_merge($trade['list'],$actrarr);
        // $trade['income'] =db('trade')->where($map)->sum('payment'); 


        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$trade
        ];
        return json($data);
    }

    /**
     * [counsellorindex_shop 咨询师信息]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function counsellorindex_shop($params)
    {
        $account = trim($params['account']);

        //收入
        $map['mid'] = $account;
        $map['paytype'] = 0;
        $map['status'] = 1;

        $user['income'] = db('trade')->where($map)->sum('payment'); 

        //活动课程收入计算
        $clinc = db('shop_classes_allot')->alias('a')->join('cms_classes b',' b.id = a.classid','LEFT')->where('adminid','like',$account)->whereOr('tearchid','like','%'.$account.'%')->whereOr('coachid','like','%'.$account.'%')->select();

        $clincs = 0;
        foreach ($clinc as $key => $value) {
            if ($value['adminid']!= $account &&!in_array($account, explode(',', $value['tearchid']) )&&!in_array($account, explode(',', $value['coachid']) ) ) {
                unset($clinc[$key]);

            }
            if ($value['tearchid'] == $account) {
                $clincs += floatval($value['ascale'] * $value['price']/100);
            }

            if (in_array($account, explode(',', $value['tearchid'])) ) {
                $tarr =  array_flip( explode(',', $value['tearchid']) );
                $scarr = explode(',', $value['tscale']);
                if (!isset($scarr[$tarr[$account]])) {
                    $clincs += 0;
                }else{
                    $clincs += floatval( intval($scarr[$tarr[$account]]) * $value['price']/100);    
                }
                
            }

            if (in_array($account, explode(',', $value['coachid']) ) ){
                $tarr =  array_flip( explode(',', $value['coachid']) );
                $scarr = explode(',', $value['cscale']);

                if (!isset($scarr[$tarr[$account]])) {
                    $clincs += 0;
                }else{
                    $clincs += floatval( intval($scarr[$tarr[$account]]) * $value['price']/100);    
                }
            }
            
        }

        $user['income'] += $clincs;

        
        //活动
        $acinc = db('shop_acitve_allot')->alias('a')->join('cms_active b',' b.id = a.activeid','LEFT')->where('adminid','like',$account)->whereOr('tearchid','like','%'.$account.'%')->whereOr('coachid','like','%'.$account.'%')->select();

        $acincs = 0;
        foreach ($acinc as $key => $value) {
            if ($value['adminid']!= $account &&!in_array($account, explode(',', $value['tearchid']) )&&!in_array($account, explode(',', $value['coachid']) ) ) {
                unset($acinc[$key]);

            }
            if ($value['tearchid'] == $account) {
                $acincs += floatval($value['ascale'] * $value['price']/100);
            }

            if (in_array($account, explode(',', $value['tearchid'])) ) {
                $tarr =  array_flip( explode(',', $value['tearchid']) );
                $scarr = explode(',', $value['tscale']);
                if (!isset($scarr[$tarr[$account]])) {
                    $acincs += 0;
                }else{
                    $acincs += floatval( intval($scarr[$tarr[$account]]) * $value['price']/100);    
                }
                
            }

            if (in_array($account, explode(',', $value['coachid']) ) ){
                $tarr =  array_flip( explode(',', $value['coachid']) );
                $scarr = explode(',', $value['cscale']);

                if (!isset($scarr[$tarr[$account]])) {
                    $acincs += 0;
                }else{
                    $acincs += floatval( intval($scarr[$tarr[$account]]) * $value['price']/100);    
                }
            }
            
        }

        $user['income'] += $acincs;
        

        
        //积分
        $pmap['memberid'] = $account;
        $pmap['behavior_type'] = 0;
        $user['points'] = db('member_point')->where($pmap)->sum('point');

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$user
        ];
        return json($data);
    }

    /**
     * [calendatoday_shop 获得当前时间日程数据]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function calendatoday_shop($params)
    {
       $account = trim($params['account']);
       $cstime =  trim($params['day']);

       //当晚24点时间
       $cetime = strtotime(date('Y-m-d',$cstime))+24 * 60 * 60;
       //当天 0点
       $btime = strtotime(date('Y-m-d',$cstime));

       //日程
        $pmap['memberid'] = $account;

        $calendar['list'] = db('calendar')->where($pmap)->whereTime('start_time', 'between', [$btime, $cetime])->select();
        foreach ($calendar['list'] as $key => $value) {
            $uid  = db('trade')->where(['id'=>$value['tid']])->value('memberid');
            //获得用户信息 
            $calendar['list'][$key]['user'] = db('member')->field('id,username,nickname,avar')->where(['id'=>$uid])->find();
        }
        
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$calendar
        ];
        return json($data);
    }

    /**
     * [calendaall_shop 当月到月底的数据]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function calendaall_shop($params)
    {
       $account = trim($params['account']);
       $cstime =  trim($params['day']);

       

       //日程
        $pmap['memberid'] = $account;

        $calendar['list'] = db('calendar')->where($pmap)->whereTime('start_time', 'm')->select();
        foreach ($calendar['list'] as $key => $value) {
            if ($value['start_time']<$cstime) {
                // error_log($value['start_time'].'|||'.$cstime,3,'/home/wwwroot/daguan/time.log');
                unset($calendar['list'][$key]);
                continue;
            }
            $uid  = db('trade')->where(['id'=>$value['tid']])->value('memberid');
            //获得用户信息 
            $calendar['list'][$key]['user'] = db('member')->field('id,username,nickname,avar')->where(['id'=>$uid])->find();


        }
        $calendar['list'] = array_values($calendar['list']);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$calendar
        ];
        return json($data);
    }
    /**
     * [calendaadd_shop 添加日程]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function calendaadd_shop($params)
    {
        //title des tid createtime start_time memberid
        $account = trim($params['account']);
        $start_time = trim($params['start_time']);
        $end_time = trim($params['end_time']);
        $tid = trim($params['tid']);


        //添加
        $save['create_time'] = time();
        $save['memberid'] = $account;

        $save['start_time'] =strtotime($start_time);
        $save['end_time'] = strtotime($end_time);
        $save['tid'] = $tid;

        $save['title'] = db('trade')->where('id',$tid)->column('title')[0];
        $save['descrption'] = db('trade')->where('id',$tid)->column('title')[0];

        if (!db('calendar')->insert($save)) {
            $this->error('保存失败！');
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [recordlist_shop 咨询记录列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function recordlist_shop($params)
    {
        $account = trim($params['account']);

        $status = trim($params['status']);
        $page_no = trim($params['page_no']);
        $page_size = trim($params['page_size']);

        
        

        if ($status == 'all') {

            $startpg = ($page_no-1)*$page_size;
            $allmap['paytype'] = 0;
            $allmap['mid'] = $account;
            $allmap['status'] = 1;
            $tids = db('calendar')->where(['memberid'=>$account])->column('tid');
            if ($tids) {
                $tidarr = array_unique($tids);
                foreach ($tidarr as $key => $value) {
                    //查出大于1的数据
                    $tnum = db('trade')->where(['id'=>$value])->value('num');
                    if ($tnum>1) {
                        $cnum = db('calendar')->where(['tid'=>$value])->count();
                        if ($cnum<$tnum) {

                            unset($tidarr[$key]);
                        }
                    }
                }
                $allmap['id'] = array('not in',$tidarr);
            }
            // $data = db('calendar')->alias('a')->field('a.*,b.chart')->join('trade b',' b.id = a.tid','LEFT')->where($map)->order('a.id DESC')->limit($startpg, $page_size)->select();
            $data = db('trade')->where($allmap)->order('id DESC')->limit($startpg, $page_size)->select();
           

            foreach ($data as $key => $value) {
                switch ($value['chart']) {
                    case 'speechchart':
                        $str = '语音咨询';
                        break;
                    case 'videochart':
                        $str = '视频咨询';
                        break;
                    case 'facechart':
                        $str = '面对面咨询';
                        break;
                    
                    default:
                        $str = '文字咨询';
                        break;
                }

                $data[$key]['chartkey'] = $value['chart'];
                $data[$key]['chart'] = $str;

                $record = db('calendar')->where(['tid'=>$value['id']])->count();
                $data[$key]['process'] =  '当前进度：'.$record.'/'.$value['num'];


                $counsellor =  db('member')->where(array('id'=>$value['memberid']))->find();
                $data[$key]['member'] =  $counsellor['nickname'];
                $data[$key]['mid'] =  $counsellor['id'];
                $data[$key]['mobile'] =  $counsellor['mobile'];
                
                $data[$key]['cavar'] = is_numeric($counsellor['avar'])?get_file_path($counsellor['avar']):$counsellor['avar'];

                $data[$key]['st'] = date('Y-m-d H:i',$value['created_time']);
            }
            $pages = array(
                    'total'=>db('trade')->where($allmap)->count()
                );
            $trade['data']['pagers'] = $pages;
            $trade['data']['list'] = array_values($data);
            $trade['data']['sorce'] = 'trade';
            //返回信息
            $data = [
                'code'=>'1',
                'msg'=>'',
                'data'=>$trade
            ];
            return json($data);

            // $map['status'] = array('gt',0);
        }else{
            $map['a.status'] = $status;
            if ($status==1||$status==2) {
                $map['a.status'] = array('in','1,2,3');
            }
        }
        $map['a.memberid'] = $account;
        $startpg = ($page_no-1)*$page_size;

        $data = db('calendar')->alias('a')->field('a.*,b.chart,b.memberid')->join('trade b',' b.id = a.tid','LEFT')->where($map)->order('a.id DESC')->limit($startpg, $page_size)->select();
        foreach ($data as $key => $value) {
            switch ($value['chart']) {
                case 'speechchart':
                    $str = '语音咨询';
                    break;
                case 'videochart':
                    $str = '视频咨询';
                    break;
                case 'facechart':
                    $str = '面对面咨询';
                    break;
                
                default:
                    $str = '文字咨询';
                    break;
            }
            $data[$key]['chartkey'] = $value['chart'];
            $data[$key]['chart'] = $str;


            // $member =  db('member')->alias('a')->field('a.*,b.mid')->join(' trade b',' b.memberid = a.id','LEFT')->where(array('b.id'=>$value['tid']))->find();

            $counsellor =  db('member')->where(array('id'=>$value['memberid']))->find();

            $data[$key]['member'] =  $counsellor['nickname'];
            $data[$key]['mid'] =  $counsellor['id'];
            $data[$key]['mobile'] =  $counsellor['mobile'];
            
            $data[$key]['cavar'] = is_numeric($counsellor['avar'])?get_file_path($counsellor['avar']):$counsellor['avar'];

            // $data[$key]['member'] =  $member['nickname'];
            // $data[$key]['mid'] =  $member['mid'];
            // $data[$key]['mobile'] =  db('member')->where(['id'=>$member['mid']])->value('mobile');

            // $data[$key]['avar'] =  db('member')->where(['id'=>$member['mid']])->value('avar');
            // $data[$key]['counsellor'] =  db('member')->where(['id'=>$member['mid']])->value('nickname');

            $data[$key]['caid'] =  db('case')->where(['cid'=>$value['id']])->value('id');

            $data[$key]['st'] = date('Y-m-d H:i',$value['start_time']);
        }
        $pages = array(
                'total'=>db('calendar')->alias('a')->field('a.*')->join('trade b',' b.id = a.tid','LEFT')->where($map)->order('a.id DESC')->count()
            );
        $trade['data']['pagers'] = $pages;
        $trade['data']['list'] = array_values($data);
        $trade['data']['sorce'] = 'calendar';
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$trade
        ];
        return json($data);
    }

    /**
     * [upavar_shop 头像]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function upavar_shop($params)
    {
        //参数
        $account = trim($params['account']);
        $avar = trim($params['avar']);
        
        if (!$avar) {
            return $this->error('参数必填');
        }
        

        //更新状态
        $data['avar'] =$this->_seve_img($avar);
        if (!$data['avar']) {
            return $this->error('头像上传失败，请稍后重试');
        }
        $map['id'] = $account;

        if(!db('member')->where($map)->update($data)){
            return $this->error('服务器忙，请稍后');
        }

        $info['avar'] = $data['avar'];
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$info
        ];
        return json($data);
    }
    /**
     * [social_shop 社交信息]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function social_shop($params)
    {
        //参数
        $account = trim($params['account']);
        $weixin = trim($params['weixin']);
        $qq = trim($params['qq']);
        $alipay = trim($params['alipay']);
        
        if (!$weixin||!$qq||!$alipay) {
            return $this->error('参数必填');
        }
        

        //更新状态
        $data['weixin'] = $weixin;
        $data['qq'] = $qq;
        $data['alipay'] = $alipay;
        $map['username'] = $account;
        if(!db('member')->where($map)->update($data)){
            return $this->error('服务器忙，请稍后');
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }
    /**
     * [identifi_shop description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function identifi_shop($params)
    {
        //参数
        $account = trim($params['account']);
        $identifi = trim($params['identifi']);
        $cerfornt = trim($params['cerfornt']);
        $cerback = trim($params['cerback']);
        
        if (!$identifi||!$cerfornt||!$cerback) {
            return $this->error('参数必填');
        }
        

        //更新状态
        $data['cerfornt'] =$this->_seve_img($cerfornt);
        if (!$data['cerfornt']) {
            return $this->error('身份正面上传失败，请稍后重试');
        }
        $data['cerback'] =$this->_seve_img($cerback);
        if (!$data['cerback']) {
            return $this->error('身份反面上传失败，请稍后重试');
        }
        $data['identifi'] = $identifi;
        $map['id'] = $account;
        $data['status'] = 0;
        if(!db('member')->where($map)->update($data)){
            // return $this->error('服务器忙，请稍后');
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [agency_custom 机构列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function agency_shop($params)
    {
        $agency['list'] = db('shop_agency')->where('status',1)->select();

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$agency
        ];
        return json($data);
    }

    /**
     * [getAvatar 头像]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getAvatar_shop($params)
    {
        $users = trim($params['users']);
        if (!$users) {
            return $this->error('参数缺失！');
        }

        $where['mobile'] = array('in',explode(',', $users)) ;
        $rs = db('member')->where($where)->column('mobile,avar,nickname');
        // get_file_path
        foreach ($rs as $key => $value) {
            if (is_numeric($value['avar'])) {
                $rs[$key]['avar'] = get_file_path($value['avar']);
            }
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);

    }

    /**
     * [updateonline 更新状态]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function updateonline_shop($params)
    {
        //参数
        $account = trim($params['account']);
        $isActive = trim($params['isActive']);
        
        if (!$account) {
            return $this->error('参数必填');
        }
        
        //
        db('member')->where(['id'=>$account])->update(['online'=>$isActive]);
        //更新状态
        $data['online'] = $isActive;
        $map['memberid'] = $account;
        if(!db('member_counsellor')->where($map)->update($data)){
            // return $this->error('服务器忙，请稍后');
        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [usersendSms_custom 发送验证码]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function usersendSms_shop($params)
    {
        //参数
        $account = trim($params['mobile']);

        //是否是会员
        if (!db('member')->where(['mobile'=>$account])->find()) {
            return $this->error('账号不存在');
        }
        
        //短信
        $code = $this->sendmsg($account);
        if (!$code) {
            return $this->error('发送失败，1小时只能获得3次');
        }
        
        //生成session 
        cache($account.'vcode',$code);

        //设置过期时间
        cache($account.$code,time() + 1800);

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }
    /**
     * [findPassword description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function findPassword_shop($params)
    {
        //参数
        $username = trim($params['mobile']);
        $code = trim($params['code']);
        $newpw = trim($params['newpw']);
        $rnewpw = trim($params['rnewpw']);


        //检查过期时间
        if (cache($username.$code)&&cache($username.$code)<time()) {
            return $this->error('验证码已过期');
        }
        
        //检查是否正确
        if (cache($username.'vcode')!=$code) {
            return $this->error('验证码不正确');
        }

        //生成密码
        $data['password'] =  Hash::make((string)trim($params['newpw']));

        //更新
        if(!db('member')->where(['mobile'=>$username])->update($data)){
            return $this->error('服务器忙，请稍后');
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }
    /**
     * [getMemberInfoByTid_shop 获得咨询信息]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getMemberInfoByTid_shop($params)
    {
        //参数
        $tid = trim($params['tid']);
        $cid = trim($params['cid']);

        //订单信息
        $trade = db('trade')->where(['id'=>$tid])->find();

        //预约信息
        $ondate = db('calendar')->where(['id'=>$cid])->find();
        
        $rs = array();
        if ($trade&&$trade['memberid']) {
            $rs['user'] = db('member')->where(['id'=>$trade['memberid']])->find();
            if ($rs['user']) {
                unset($rs['user']['password']);
                //年龄
                $rs['user']['birthday'] = $this->calcAge($rs['user']['birthday']);
            }
        }
        $rs['trade'] = $trade;
        $rs['ondate'] = $ondate;
        //预约时间
        if ($rs['ondate']) {
            $rs['ondate']['timerange'] = date('m-d H:i',$rs['ondate']['start_time']).'-'.date('H:i',$rs['ondate']['end_time']);
        }
        if ($rs['trade']) {
            $str ='文字咨询';
            switch ($rs['trade']['chart']) {
                case 'speechchart':
                    $str = '语音咨询';
                    break;
                case 'videochart':
                    $str = '视频咨询';
                    break;
                case 'facechart':
                    $str = '面对面咨询';
                    break;
                
                default:
                    break;
            }
            
            $rs['trade']['chart'] = $str;
        }
        //返回案例信息 
        if (isset($params['caid'])) {
            $rs['case'] = db('case')->where(['id'=>$params['caid']])->find();
            //th
            $th = ['A','B','C','D','E','F','Total','avg'];
            //A
            $aarr = explode(',', $rs['case']['Avl']);
            $atotal = 0;
            foreach ($aarr as $key => $value) {
                $atr['A'.$th[$key].'vl_Avl'] = $value;
                $atotal += $value;
            }
            $rs['case']['atr'] = $atr;
            $rs['case']['atr']['Total'] = $atotal;
            $rs['case']['atr']['avg'] = round($atotal/6,2);
            //A2
            $_2arr = explode(',', $rs['case']['A2vl']);
            $_2atotal = 0;
            foreach ($_2arr as $key => $value) {
                $_2atr['_2A'.$th[$key].'vl_2Avl'] = $value;
                $_2atotal += $value;
            }
            $rs['case']['_2atr'] = $_2atr;
            $rs['case']['_2atr']['Total'] = $_2atotal;
            $rs['case']['_2atr']['avg'] = round($_2atotal/6,2);
            //B
            $_Barr = explode(',', $rs['case']['Bvl']);
            $btotal = 0;
            foreach ($_Barr as $key => $value) {
                $_btr['B'.$th[$key].'vl_Bvl'] = $value;
                $btotal += $value;
            }
            $rs['case']['_btr'] = $_btr;
            $rs['case']['_btr']['Total'] = $btotal;
            $rs['case']['_btr']['avg'] = round($btotal/6,2);
            //1m
            $_1marr = explode(',', $rs['case']['M1vl']);
            $_1mtotal = 0;
            foreach ($_1marr as $key => $value) {
                $_1matr['_1M'.$th[$key].'vl_M1vl'] = $value;
                $atotal += $value;
            }
            $rs['case']['_1matr'] = $_1matr;
            $rs['case']['_1matr']['Total'] = $_1mtotal;
            $rs['case']['_1matr']['avg'] = round($_1mtotal/6,2);
            //AM
            $_amarr = explode(',', $rs['case']['AMvl']);
            $_amtotal = 0;
            foreach ($_amarr as $key => $value) {
                $_amtr['A_M'.$th[$key].'vl_AMvl'] = $value;
                $_amtotal += $value;
            }
            $rs['case']['_amtr'] = $_amtr;
            $rs['case']['_amtr']['Total'] = $_amtotal;
            $rs['case']['_amtr']['avg'] = round($_amtotal/6,2);

            //2m
            $_2marr = explode(',', $rs['case']['M2vl']);
            $_2mtotal = 0;
            foreach ($_2marr as $key => $value) {
                $_2mtr['_2M'.$th[$key].'vl_M2vl'] = $value;
                $_2mtotal += $value;
            }
            $rs['case']['_2mtr'] = $_2mtr;
            $rs['case']['_2mtr']['Total'] = $_2mtotal;
            $rs['case']['_2mtr']['avg'] = round($_2mtotal/6,2);
            //plan
            $_parr = explode(',', $rs['case']['PLAN']);
            $ptotal = 0;
            foreach ($_parr as $key => $value) {
                $_ptr['p'.$th[$key].'vl_PLAN'] = $value;
                $ptotal += $value;
            }
            $rs['case']['_ptr'] = $_ptr;
            $rs['case']['_ptr']['Total'] = $ptotal;
            $rs['case']['_ptr']['avg'] = round($ptotal/6,2);

            //caseS
            $rs['case']['caseS'] = explode(',', $rs['case']['caseS']);
            //caseR9
            $rs['case']['caseR9'] = explode(',', $rs['case']['caseR9']);
            //caseSOR
            $rs['case']['caseSOR'] = explode(',', $rs['case']['caseSOR']);
            //caseS
            $rs['case']['casePNF'] = explode(',', $rs['case']['casePNF']);

        }
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);
    }
    /**
     * [caseAdd_shop 添加案例]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function caseAdd_shop($params)
    {
        //参数
        $param = trim($params['data']);
        $cid = trim($params['cid']);
        $truename = trim($params['truename']);
        $sex = trim($params['sex']);
        $birthday = trim($params['birthday']);
        $edu = trim($params['edu']);
        $grade = trim($params['grade']);
        $marital = trim($params['marital']);
        $profession = trim($params['profession']);
        $mobile = trim($params['mobile']);
        $chat = trim($params['chat']);
        $timerange = trim($params['timerange']);

        
        $rs = array();
        $parr = explode("&",$param);
        foreach($parr as $v) {
            $pqurey = explode("=", $v);
            if ($pqurey[1]=='') {
                continue;
            }
            if (array_key_exists($pqurey[0],$rs)) {
                $rs[$pqurey[0]] = $rs[$pqurey[0]].','.urldecode($pqurey[1]);
            }else{
                //select 判断
                if (strpos($pqurey[0],'_Avl')) {
                    if (array_key_exists('Avl',$rs)) {
                        $rs['Avl'] = $rs['Avl'].','.urldecode($pqurey[1]);
                    }else{
                        $rs['Avl'] = urldecode($pqurey[1]);
                    }
                    
                }elseif (strpos($pqurey[0], '_A2vl')) {
                    if (array_key_exists('A2vl',$rs)) {
                        $rs['A2vl'] = $rs['A2vl'].','.urldecode($pqurey[1]);
                    }else{
                        $rs['A2vl'] = urldecode($pqurey[1]);
                    }
                }elseif (strpos($pqurey[0], '_Bvl')) {
                    if (array_key_exists('Bvl',$rs)) {
                        $rs['Bvl'] = $rs['Bvl'].','.urldecode($pqurey[1]);
                    }else{
                        $rs['Bvl'] = urldecode($pqurey[1]);
                    }
                }elseif (strpos($pqurey[0], '_M1vl')) {
                    if (array_key_exists('M1vl',$rs)) {
                        $rs['M1vl'] = $rs['M1vl'].','.urldecode($pqurey[1]);
                    }else{
                        $rs['M1vl'] = urldecode($pqurey[1]);
                    }
                }elseif (strpos($pqurey[0],'_AMvl')) {
                    if (array_key_exists('AMvl',$rs)) {
                        $rs['AMvl'] = $rs['AMvl'].','.urldecode($pqurey[1]);
                    }else{
                        $rs['AMvl'] = urldecode($pqurey[1]);
                    }
                }elseif (strpos($pqurey[0],'_M2vl')) {
                    if (array_key_exists('M2vl',$rs)) {
                        $rs['M2vl'] = $rs['M2vl'].','.urldecode($pqurey[1]);
                    }else{
                        $rs['M2vl'] = urldecode($pqurey[1]);
                    }
                }elseif (strpos($pqurey[0],'_PLAN')) {
                    if (array_key_exists('PLAN',$rs)) {
                        $rs['PLAN'] = $rs['PLAN'].','.urldecode($pqurey[1]);
                    }else{
                        $rs['PLAN'] = urldecode($pqurey[1]);
                    }
                }else{
                    $rs[$pqurey[0]] = urldecode($pqurey[1]);
                }
                 
            }
                      
        }

        unset($params['data']);
        unset($params['method']);
        unset($params['source']);
        $params['create_time'] = time();
        foreach ($params as $key => $value) {
            $params[$key] = urldecode($value);
        }
        $pst = array_merge($rs,$params);

        if (isset($params['caid'])) {
            db('case')->where(['id'=>$params['caid']])->update($pst);
        }else{
            if (db('case')->insert($pst)) {
                $candar = db('calendar')->where(['id'=>$cid])->value('status');
                if ($candar<2) {
                    db('calendar')->where(['id'=>$cid])->update(['status'=>2]);
                }
            }
        }
        
        // error_log(json_encode($pst),3,'/home/wwwroot/daguan/case.log');
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);
    }

    /**
     * [calendatoday_shop 获得当前时间日程数据]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function calendaondate_shop($params)
    {
       $account = trim($params['account']);
       $cstime =  trim($params['day']);

       //当晚24点时间
       $cetime = strtotime(date('Y-m-d',$cstime))+24 * 60 * 60;
       //今天
       $today = strtotime(date('Y-m-d',time()));

       //日程
        $pmap['memberid'] = $account;

        $calendar['list'] = db('calendar')->where($pmap)->whereTime('start_time', 'between', [ $cstime , $cetime])->select();
        // $times = array('9:00','9:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30','19:00');
        // $times = array('9:00~10:00','10:00~11:00','11:00~12:00','12:00~13:00','13:00~14:00','14:00~15:00','15:00~16:00','16:00~17:00','17:00~18:00','18:00~19:00','19:00~20:00','20:00~21:00');
        $times = array('0:00','1:00','2:00','3:00','4:00','5:00','6:00','7:00','8:00','9:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00','23:00');
        $timearrr = array('0:00~5:00','6:00~18:00','19:00~24:00');
        $timesarr['list'] = [];
        //过去的时间
        if ($today>strtotime(date('Y-m-d ',$cstime))) {
            foreach ($times as $key => $value) {
                //订单记录
                $tpoint = strtotime(date('Y-m-d',$cstime).$value);
                $timesarr['list'][$key]['t'] = $value;
                $timesarr['list'][$key]['s'] = 2;
            }
            $timebt['0:00~5:00']['s'] = 2;
            $timebt['6:00~18:00']['s'] = 2;
            $timebt['19:00~24:00']['s'] = 2;
        }else{
            foreach ($times as $key => $value) {
                //订单记录
                $sval = $value;
                $tpoint = strtotime(date('Y-m-d',$cstime).$sval)+60;//加上60秒处理时间间隔
                $timesarr['list'][$key]['t'] = $value;
                $timesarr['list'][$key]['s'] = 0;
                if ($tpoint<time()) {
                    $timesarr['list'][$key]['s'] = 2;
                }
                foreach ($calendar['list'] as $k => $v) {
                    if ($tpoint>=$v['start_time']&&$tpoint<=$v['end_time']) {
                        $timesarr['list'][$key]['s'] = 1;
                    }
                }
                //查看是否设置了可约
                if ($timesarr['list'][$key]['s']==0) {
                    $tt = strtotime(date('Y-m-d',$cstime).$sval);
                    $cid = $account;
                    if (db('connsellor_ondate')->where(['memberid'=>$cid,'ondatetime'=>$tt])->find()) {
                        $timesarr['list'][$key]['s'] = 3;
                    }
                }

            }

            //处理批量
            $mbts = strtotime(date('Y-m-d',$cstime).' 0:00');
            $mbte = strtotime(date('Y-m-d',$cstime).' 5:00');
            $condatemp['memberid'] = $cid;
            if (db('connsellor_ondate')->where(['memberid'=>$cid])->whereTime('ondatetime', 'between', [ $mbts , $mbte])->find()) {
                $timebt['0:00~5:00']['s'] = 3;
            }else{
                $timebt['0:00~5:00']['s'] = 0;
                if ($mbte<time()) {
                    $timebt['0:00~5:00']['s'] = 2;
                }
                
            }

            $gbts = strtotime(date('Y-m-d',$cstime).' 6:00');
            $gbte = strtotime(date('Y-m-d',$cstime).' 18:00');
            $condatemp['memberid'] = $cid;
            if (db('connsellor_ondate')->where(['memberid'=>$cid])->whereTime('ondatetime', 'between', [ $gbts , $gbte])->find()) {
                $timebt['6:00~18:00']['s'] = 3;
            }else{
                $timebt['6:00~18:00']['s'] = 0;
                if ($gbte<time()) {
                    $timebt['6:00~18:00']['s'] = 2;
                }
                
            }

            $ebts = strtotime(date('Y-m-d',$cstime).' 19:00');
            $ebte = strtotime(date('Y-m-d',$cstime).' 24:00');
            $condatemp['memberid'] = $cid;
            if (db('connsellor_ondate')->where(['memberid'=>$cid])->whereTime('ondatetime', 'between', [ $ebts , $ebte])->find()) {
                $timebt['19:00~24:00']['s'] = 3;
            }else{
                $timebt['19:00~24:00']['s'] = 0;
                if ($ebte<time()) {
                    $timebt['19:00~24:00']['s'] = 2;
                }
                
            }
        }
        
        $timesarr['timebt'] = $timebt;
        //咨询师
        // $timesarr['user'] = db('member')->where(['id'=>$account])->column('username');

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$timesarr
        ];
        return json($data);
    }

    /**
     * [setcalenda_shop 设置]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function setcalenda_shop($params)
    {
        //参数
        $account = trim($params['account']);
        $cstime =  trim($params['hour']);

        if (isset($params['iscancle'])) {
            $data['ondatetime'] = strtotime($cstime);
            $data['memberid'] = $account;
            db('connsellor_ondate')->where($data)->delete();
            //返回信息
            $data = [
                'code'=>'1',
                'msg'=>'',
                'data'=>1
            ];
            return json($data);
        }

        $data['ondatetime'] = strtotime($cstime);
        $data['memberid'] = $account;
        if(!db('connsellor_ondate')->insert($data)){
            return $this->error('服务器忙，请稍后');
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }


    /**
     * [cancleDate_shop 取消预约]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function cancleDate_shop($params)
    {
        //参数
        $cid = trim($params['cid']);
        if (isset($params['cid'])) {
            //短信通知
            $mobile = db('calendar')->alias('a')->join('trade b',' b.id = a.tid','LEFT')->join(' member m',' m.id = b.memberid','LEFT')->where(array('a.id'=>$cid))->value('mobile');
            if ($mobile) {
                 // $username = db('calendar')->alias('a')->join('trade b',' b.id = a.tid','LEFT')->join(' member m',' m.id = b.memberid','LEFT')->where(array('a.id'=>$cid))->value('nickname');
                $counsellor = db('calendar')->alias('a')->join('member m',' m.id = a.memberid','LEFT')->where(array('a.id'=>$cid))->value('nickname');  
                $sj = db('calendar')->alias('a')->join('trade b',' b.id = a.tid','LEFT')->join(' member m',' m.id = b.memberid','LEFT')->where(array('a.id'=>$cid))->value('a.start_time');
                $this->sendcanlcemsg($mobile,$counsellor,$sj);
            }
            db('calendar')->where(['id'=>$cid])->delete();
            
            
        }
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [setallcalenda 批量设置]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function setallcalenda_shop($params)
    {
        //参数
        $account = trim($params['account']);
        $day =  trim($params['day']);
        $rangetm =  trim($params['range']);

        // $times = array('9:00~10:00','10:00~11:00','11:00~12:00','12:00~13:00','13:00~14:00','14:00~15:00','15:00~16:00','16:00~17:00','17:00~18:00','18:00~19:00','19:00~20:00','20:00~21:00');
        $times = array('0:00','1:00','2:00','3:00','4:00','5:00','6:00','7:00','8:00','9:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00','22:00','23:00');
        $timearrr = array('0:00~5:00'=>array('0:00','1:00','2:00','3:00','4:00','5:00'),'6:00~18:00'=>array('6:00','7:00','8:00','9:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00'),'19:00~24:00'=>array('19:00','20:00','21:00','22:00','23:00'));

        $rangearr = $timearrr[$rangetm];
        $arr = [];
        // foreach ($times as $key => $value) {
        //     $hour = $value;
        //     $data['ondatetime'] = strtotime($day.' '.$hour);
        //     $data['memberid'] = $account;
        //     if (!db('connsellor_ondate')->where($data)->find()&&in_array($value, $rangearr)) {
        //         $arr[] = $data;
        //         db('connsellor_ondate')->insert($data);
        //     }
            
        // }

        foreach ($rangearr as $key => $value) {
            $hour = $value;
            $data['ondatetime'] = strtotime($day.' '.$hour);
            $data['memberid'] = $account;
            if (!db('connsellor_ondate')->where($data)->find()) {
                db('connsellor_ondate')->insert($data);
            }
            
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [cancleallcalenda_shop description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function cancleallcalenda_shop($params)
    {   
        //参数
        $account = trim($params['account']);
        $day =  trim($params['day']);
        $rangetm =  trim($params['range']);

        $timearrr = array('0:00~5:00'=>array('0:00','1:00','2:00','3:00','4:00','5:00'),'6:00~18:00'=>array('6:00','7:00','8:00','9:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00'),'19:00~24:00'=>array('19:00','20:00','21:00','22:00','23:00'));

        $rangearr = $timearrr[$rangetm];

        foreach ($rangearr as $key => $value) {
            $hour = $value;
            $data['ondatetime'] = strtotime($day.' '.$hour);
            $data['memberid'] = $account;
            if (db('connsellor_ondate')->where($data)->find()) {
                $wmap['memberid'] = $account;
                $wmap['status'] = array('lt',4);
                $wmap['start_time'] = strtotime($day.' '.$hour);
                if (!db('calendar')->where($wmap)->find()) {
                    db('connsellor_ondate')->where($data)->delete();
                }
                
            }
            
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [getUserMsgCount_shop 获取用户离线消息]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getUserMsgCount_shop($params)
    {   
        //参数
        $account = trim($params['account']);

        $ret = Hx::getUserMsgCount($account);

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$ret
        ];
        return json($data);
    }

    /**
     * [getUserList_shop 获取用户好友]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getUserList_shop($params)
    {   
        //参数
        $account = trim($params['account']);

        $ret = Hx::showFriend($account);
        //处理用户列表
        $user = [];
        if ($ret) {
            foreach ($ret as $key => $value) {
                $u = db('member')->where(['username'=>$value])->find();
                if (!$u) {
                    continue;
                }
                $user[$value]['nickname'] = $u['nickname'];
                $user[$value]['mobile'] = $value;
                $user[$value]['avar'] = $u['avar'];
                $user[$value]['mid'] = $u['id'];
            }
            array_multisort(array_column($user,'nickname'),SORT_ASC,$user);
        }
        

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$user
        ];
        
        return json($data);
    }

    /**
     * [getCurrentCander_shop 当前天后预约记录]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function getCurrentCander_shop($params)
    {

        //参数
        $account = trim($params['account']);

        $firstday = date('Y-m-01', strtotime(date("Y-m-d")));
        $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
        $current = date('Y-m-d', time());
        $info =  db('calendar')->where(['memberid'=>$account])->whereTime('start_time', 'between', [$current, $lastday])->select();

        $ret = array();
        foreach ($info as $key => $value) {
            $ret[] = (int) date('d',$value['start_time']);
        }

        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>array_unique($ret)
        ];
        return json($data);
    }

    public function userIntro_shop($params)
    {
        //参数
        $intro = trim($params['intro']);
        $account = trim($params['account']);

        $data['intro'] = $intro;
        db('member_counsellor')->where(['memberid'=>$account])->update($data);
        
        $save['status'] = 0;
        db('member')->where(['id'=>$account])->update($save);

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [clca_shop 课程活动]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function clca_shop($params)
    {

        $cateid = trim($params['cateid']);
        $page_no = trim($params['page_no']);
        $page_size = trim($params['page_size']);
        $account = trim($params['account']);

        //活动结束的过滤
        $map['endtime'] = array('gt',time());

        if ($cateid != 'all') {
            $map['cateid'] = $cateid;
        }

        //管理员
        
        $startpg = ($page_no-1)*$page_size;
        $data = db('cms_clac_temp')->where($map)->order('id DESC')->limit($startpg, $page_size)->select();

        foreach ($data as $key => $value) {
            $data[$key]['pic'] =  get_file_path($value['pic']);
        }
        $pages = array(
                'total'=>db('cms_clac_temp')->where($map)->count()
            );
        $trade['data']['pagers'] = $pages;
        $trade['data']['list'] = array_values($data);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$trade
        ];
        return json($data);
    }

    /**
     * [clcadetail_shop 课程活动祥情]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function clcadetail_shop($params)
    {
        $type = trim($params['typeid']);
        $acid = trim($params['acid']);
        
        $map['id'] = $acid;
        if ($type==0) {//课程

            $info = db('cms_classes')->where($map)->find();
        }
        if ($type==1) {//活动
            $info = db('cms_active')->where($map)->find();
        }

        if ($info) {
            $info['pic'] = get_file_path($info['pic']);

            $info['isfav'] = 0;//是否收藏
            $tm['paytype'] = $type==0?2:3;
            $tm['classid'] = $acid;
            $tm['status'] = 1;
            $info['num'] = db('trade')->where($tm)->count();
            //登录状态
            if (isset($params['account'])) {//用户id
                $map['type'] = $type;
                $map['fid'] = $acid;
                $map['mid'] = $params['account'];
                if (db('cms_fav')->where($map)->find()) {
                   $info['isfav'] = 1;
                }
            }
        }
        

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$info
        ];
        return json($data);
    }

    /**
     * [clcamanger_shop 课程活动管理]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function clcamanger_shop($params)
    {
        //account 
        $account = trim($params['account']);

        //
        $info = db('cms_clac_temp')->select();
        $count = db('cms_clac_temp')->count();
        $ret = []; //ucount nlist flist  
        $map['adminid'] = $account;
        foreach ($info as $key => $value) {
            $map['classid'] = $value['classid'];
            //查询是否当前用户的
            if ($value['type']==0) {//课程
                $uclac = db('shop_classes_allot')->alias('a')->join('cms_classes b',' b.id = a.classid','LEFT')->where($map)->find();
            }else{
                $uclac = db('shop_classes_allot')->alias('a')->join('cms_active b',' b.id = a.classid','LEFT')->where($map)->find();
            }
            
            if ($uclac) {
                $ret[$key]['pic'] = get_file_path($uclac['pic']);
                $ret[$key]['id'] = $uclac['classid'];
                $ret[$key]['typeid'] = $value['type'];
                $ret[$key]['title'] = $uclac['title'];
                $ret[$key]['start_time'] = $uclac['start_time'];
                $ret[$key]['endtime'] = $uclac['endtime'];
                $ret[$key]['address'] = $uclac['address'];
            }
            
            
        }
        $rs = [];
        $rs['fcount'] = 0;
        if ($ret) {
            $nlist = [];
            $flist = [];
            foreach ($ret as $key => $value) {
                if ($value['endtime']<time()) {
                   $flist[] = $value; 
                }
                if ($value['endtime']>time()) {
                   $nlist[] = $value; 
                }
            }
            // $rs['list'] = $ret;
            $rs['nlist'] = $nlist;
            $rs['flist'] = $flist;
            $rs['fcount'] = count($flist);
            
        }
        $rs['count'] = $count;
        $rs['ucount'] = count($ret);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);

        
    }

    /**
     * [clcamy_shop 我的课程活动未结束的]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function clcamy_shop($params)
    {

        //account 
        $account = trim($params['account']);

        $map['adminid'] = $account;

        $info = db('shop_classes_allot')->alias('a')->join('cms_clac_temp b',' b.classid = a.classid','LEFT')->where($map)->select();
        $rs = [];

        foreach ($info as $key => $value) {
            if ($value['endtime']<time()) {
                continue;
            }
            $value['pic'] = get_file_path($value['pic']);
            $rs[] = $value;
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);

    }

    /**
     * [clcauserlist_shop 用户列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function clcauserlist_shop($params)
    {
        
        //clacid  
        $clacid = trim($params['clacid']);
        $clactype = trim($params['clactype']);

        if ($clactype==0) {//课程
            $info = db('cms_classes')->where(['id'=>$clacid])->find();
        }else{
            $info = db('cms_active')->where(['id'=>$clacid])->find();
        }

        $rs = [];
        if ($info) {
            $rs['title'] = $info['title'];
            //ulist
            $typeid = $clactype == 0?2:3;
            $map['classid'] = $clacid;
            $map['paytype'] = $typeid;
            $map['a.status'] = 1;
            $rs['ulist'] = db('trade')->alias('a')->field('b.id,b.username,b.nickname,b.sex,b.avar')->join('member b',' b.id = a.memberid','LEFT')->where($map)->select();

            foreach ($rs['ulist'] as $key => $value) {
                $rs['ulist'][$key]['sex']  = $value['sex']==1?'男':'女';

                if (is_numeric($value['avar'])||$value['avar']==null) {
                    $value['avar'] = get_file_path($value['avar']);
                }

                $rs['ulist'][$key]['avar']  = $value['avar'];

            }

        }
        
        //title  ulist
        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);

    }

    /**
     * [clcauserinfo 用户信息]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function clcauserinfo_shop($params)
    {
        $uid = trim($params['uid']);

        //用户
        $uinfo = db('member')->field('username,nickname,avar,sex,preference')->where(['id'=>$uid])->find();

        if (is_numeric($uinfo['avar'])||$uinfo['avar']==null) {
            $uinfo['avar'] = get_file_path($uinfo['avar']);
        }
        $uinfo['sex'] = $uinfo['sex']==1?'男':'女';

        if ($uinfo['preference']) {
            $pmap['id'] = array('in',$uinfo['preference']);
            $uinfo['preference'] = db('cms_category')->where($pmap)->value('title');
        }
        
        //课程
        $tmap['memberid'] = $uid;
        $tmap['paytype'] = array('in','2,3');
        $trade = db('trade')->where($tmap)->select();
            
        $rt = [];
        foreach ($trade as $key => $value) {
            if ($value['paytype']==2) {
                $s = db('cms_classes')->where(['id'=>$value['classid']])->find();
            }
            if ($value['paytype']==3) {
                $s = db('cms_active')->where(['id'=>$value['classid']])->find();
            }
            if ($s) {
                $s['pic'] = get_file_path($s['pic']);
                $s['type'] = $value['paytype']==2?0:1;
                $rt[] = $s;
            }
            
        }

        $rs = [];
        $rs['user'] = $uinfo;
        $rs['trade'] = $rt;

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);
    }

    /**
     * [msgsys_shop 系统消息]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function msgsys_shop($params)
    {
        $account = trim($params['account']);

        //msg
        $map['status'] = 1;
        $msgs = db('cms_notice')->where($map)->order('id DESC')->select();

        $rt = [];
        foreach ($msgs as $key => $value) {
            if ($value['obj']!=0) {
                if (!in_array($account, explode(',', $value['obj']))) {
                    continue;
                }
            }
            $rt[$key]['id'] = $value['id'];
            $rt[$key]['title'] = $value['title'];
            $rt[$key]['create_time'] = $value['create_time'];
            $rt[$key]['type'] = $value['type']==1?'分中心消息':'平台消息';
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rt
        ];
        return json($data);

    }   
    /**
     * [ismsg_shop description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function ismsg_shop($params)
    {
        $account = trim($params['account']);

        //smsg
        $map['status'] = 1;
        $msgs = db('cms_notice')->where($map)->order('id DESC')->select();

        $rt = [];
        foreach ($msgs as $key => $value) {
            if ($value['obj']!=0) {
                if (!in_array($account, explode(',', $value['obj']))) {
                    continue;
                }
            }
            $rt[$key]['id'] = $value['id'];
            $rt[$key]['title'] = $value['title'];
            $rt[$key]['create_time'] = $value['create_time'];
            $rt[$key]['type'] = $value['type']==1?'分中心消息':'平台消息';
        }

        foreach ($rt as $key => $value) {
            //查询当前咨询师是否已读
            if(db('msg')->where(['status'=>1,'noticeid'=>$value['id'],'reciveid'=>$account])->find()){
                unset($rt[$key]);
            }
        }
        $is = 0;
        if (count($rt)>0) {
            $is = 1;
        }

        //msg
        if (!$is) {
            $id = trim($params['account']);

            //查询消息
            $smap['reciveid'] = $id;
            $smap['status'] = 0;
            $user =  db('msg')->where($smap)->order('create_time DESC')->select();
            $ret = [];
            foreach ($user as $key => $value) {
                $ret[$value['type']][$key] = $value;
            }

            if (count($ret)>0) {
                $is = 1;
            }
        }
        

        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$is
        ];
        return json($data);
    }

    /**
     * [ismsg_shop description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function noticemsg_shop($params)
    {
        $account = trim($params['account']);

        //smsg
        $map['status'] = 1;
        $msgs = db('cms_notice')->where($map)->order('id DESC')->select();

        $rt = [];
        foreach ($msgs as $key => $value) {
            if ($value['obj']!=0) {
                if (!in_array($account, explode(',', $value['obj']))) {
                    continue;
                }
            }
            $rt[$key]['id'] = $value['id'];
            $rt[$key]['title'] = $value['title'];
            $rt[$key]['create_time'] = $value['create_time'];
            $rt[$key]['type'] = $value['type']==1?'分中心消息':'平台消息';
        }

        foreach ($rt as $key => $value) {
            //查询当前咨询师是否已读
            if(db('msg')->where(['status'=>1,'noticeid'=>$value['id'],'reciveid'=>$account])->find()){
                unset($rt[$key]);
            }
        }
        $is['1'] = 0;
        if (count($rt)>0) {
            $is['1'] = 1;
        }

        //msg
        $id = trim($params['account']);

        //查询消息
        $smap['reciveid'] = $id;
        $smap['status'] = 0;
        $user =  db('msg')->where($smap)->order('create_time DESC')->select();
        $ret = [];
        foreach ($user as $key => $value) {
            $ret[$value['type']][$key] = $value;
        }

        if (count($ret)>0) {
            $is['0'] = 1;
        }
        

        
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$is
        ];
        return json($data);
    }
    /**
     * [smsginfo_shop 系统消息祥情]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function smsginfo_shop($params)
    {
        $id = trim($params['id']);

        //查询消息
        $map['id'] = $id;
        $msg =  db('cms_notice')->where($map)->find();
        

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$msg
        ];
        return json($data);
    }

    /**
     * [articlemy_shop 文章列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function articlemy_shop($params)
    {

        $account = trim($params['account']);
        $map['userid'] = $account;
        $article['list'] = db('cms_page')->where($map)->order('sort ASC, view DESC')->select();

        foreach ($article['list'] as $key => $value) {
            unset($article['list'][$key]['content']);
            $article['list'][$key]['cover']  = $value['fcover'];
            // if (is_numeric($value['cover'])) {
            //     $article['list'][$key]['cover'] = get_file_path($value['cover']);
            // }
            
        }

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$article
        ];
        return json($data);
    }

    /**
     * [articleadd_shop description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function articleadd_shop($params)
    {
        $title = trim($params['title']);
        $account = trim($params['account']);
        $cover = trim($params['cover']);
        $cid = trim($params['cid']);
        $description = trim($params['description']);
        $content = trim($params['content']);

        if ($cover) {
            $img = explode(',', $cover);
            $data['fcover'] =$this->_seve_img($img[1]);
            if (!$data['fcover']) {
                return $this->error('封图上传失败，请稍后重试');
            }
            
        }
        

        $data['create_time'] = time();
        $data['userid'] = $account;
        $data['title'] = $title;
        $data['cid'] = $cid;
        $data['description'] = $description;
        $data['content'] = $content;

        db('cms_page')->insert($data);

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);

    }

    /**
     * [articleedit_shop description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function articleedit_shop($params)
    {
        $title = trim($params['title']);
        $account = trim($params['account']);
        
        $cid = trim($params['cid']);
        $description = trim($params['description']);
        $content = trim($params['content']);
        $id = trim($params['aid']);

        if (isset($params['cover'])) {
            $cover = trim($params['cover']);
            $img = explode(',', $cover);
            $data['fcover'] =$this->_seve_img($img[1]);
            if (!$data['fcover']) {
                return $this->error('封图上传失败，请稍后重试');
            }
        }
        

        $data['userid'] = $account;
        $data['title'] = $title;
        $data['cid'] = $cid;
        $data['description'] = $description;
        $data['content'] = $content;

        db('cms_page')->where(['id'=>$id])->update($data);

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);

    }
    /**
     * [articlemydl_shop description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function articlemydl_shop($params)
    {

        $id = trim($params['aid']);
        $map['id'] = $id;
        $article = db('cms_page')->where($map)->find();

        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$article
        ];
        return json($data);
    }
    public function test_shop($params)
    {
        $account  = $params['account'];
        $counsellor  = $params['counsellor'];

        $a = Hx::addFriend($account,$counsellor);
        // $a = Hx::test();
        print_r($a);exit;
    }

    /**
     * [hxmsgls_custom 会话列表]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function hxmsgls_shop($params)
    {

        $sendid = trim($params['account']);

        $map['sendid|reciveid'] = $sendid;
        $info = db('hx_msg')->where($map)->order('create_time DESC')->group('tag')->select();

        $now = date('Y-m-d',time());
        $res = [];
        //头像 时间
        foreach ($info as $key => $value) {
            //msg
            $smsg['tag'] = $value['tag'];
            $news = db('hx_msg')->where($smsg)->order('id DESC')->find();
            $res[$key]['nmsg'] = $news['msg'];

            //avar
            if ($news['sendid']==$sendid) {//取 rc头像
                $su = db('member')->where(['id'=>$news['reciveid']])->find();
                if (!$su) {
                    unset($info[$key]);
                    unset($res[$key]);
                    continue;
                }
                $res[$key]['cavar'] = is_numeric($su['avar'])?get_file_path($su['avar']):$su['avar'];
                $res[$key]['nickname'] = $su['nickname'];//name
                $res[$key]['mid'] = $su['id'];//id
                $res[$key]['account'] = $su['username'];//id
                $res[$key]['sendid'] = 1;//是发送者
            }else{
                $su = db('member')->where(['id'=>$news['sendid']])->find();
                if (!$su) {
                    unset($info[$key]);
                    unset($res[$key]);
                    continue;
                }
                $res[$key]['cavar'] = is_numeric($su['avar'])?get_file_path($su['avar']):$su['avar'];
                $res[$key]['nickname'] = $su['nickname'];//name
                $res[$key]['mid'] = $su['id'];//id
                $res[$key]['account'] = $su['username'];//id
                $res[$key]['sendid'] = 0;//是接受者
            }
            
            
            //newtime
            $newtime[$key] = $news['create_time'];

            //time
            $res[$key]['times'] = date('Y-m-d',$news['create_time']) == $now? date('H:i',$news['create_time']):date('Y-m-d H:i',$news['create_time']);
            //状态
            $res[$key]['st'] = $news['status'];
        }
        array_multisort($newtime, SORT_DESC, $res);
        $rt['list'] = $res;
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$res
        ];
        return json($data);
    }

    /**
     * [hxmsgtwo_custom 二人沟通记录]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function hxmsgtwo_shop($params)
    {
        $sendid = trim($params['account']);
        $reciveid = trim($params['reciveid']);

        // $map['sendid'] = $sendid;
        // $map['reciveid'] = $reciveid;
        $map['tag'] = 'u'.$reciveid.'c'.$sendid;
        // $info =  db('hx_msg')->where($map)->order('id DESC')->limit(10)->select();
        $info =  db('hx_msg')->where($map)->order('id DESC')->select();

        //获得头像处理
        $now = date('Y-m-d',time());
        foreach ($info as $key => $value) {
            $ids[$key] = $value['id'];
            if ($value['sendid'] == $sendid) {
                $info[$key]['isme'] = 1;
                // @$info[$key]['rcavar'] = is_numeric(db('member')->where(['id'=>$value['reciveid']])->value('avar'))?get_file_path(db('member')->where(['id'=>$value['reciveid']])->value('avar')):db('member')->where(['id'=>$value['reciveid']])->value('avar');
                // @$info[$key]['sdavar'] = is_numeric(db('member')->where(['id'=>$value['sendid']])->value('avar'))?get_file_path(db('member')->where(['id'=>$value['sendid']])->value('avar')):db('member')->where(['id'=>$value['sendid']])->value('avar');
            }else{
                $info[$key]['isme'] = 0;
                // @$info[$key]['rcavar'] = is_numeric(db('member')->where(['id'=>$value['sendid']])->value('avar'))?get_file_path(db('member')->where(['id'=>$value['sendid']])->value('avar')):db('member')->where(['id'=>$value['sendid']])->value('avar');
                // @$info[$key]['sdavar'] = is_numeric(db('member')->where(['id'=>$value['reciveid']])->value('avar'))?get_file_path(db('member')->where(['id'=>$value['reciveid']])->value('avar')):db('member')->where(['id'=>$value['reciveid']])->value('avar');
            }
            
            // @$info[$key]['rcavar'] = is_numeric(db('member')->where(['id'=>$value['reciveid']])->value('avar'))?get_file_path(db('member')->where(['id'=>$value['reciveid']])->value('avar')):db('member')->where(['id'=>$value['reciveid']])->value('avar');
            @$info[$key]['sdavar'] = is_numeric(db('member')->where(['id'=>$value['sendid']])->value('avar'))?get_file_path(db('member')->where(['id'=>$value['sendid']])->value('avar')):db('member')->where(['id'=>$value['sendid']])->value('avar');
            $info[$key]['times'] = date('Y-m-d',$value['create_time']) == $now? date('H:i:s',$value['create_time']):date('Y-m-d H:i:s',$value['create_time']);
        }
        array_multisort($ids, SORT_ASC, $info);
        $rs['list'] = $info; 
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>$rs
        ];
        return json($data);
    }

    /**
     * [hxmsgup_custom up消息状态]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function hxmsgup_shop($params)
    {

        $sendid = trim($params['account']);
        $reciveid = trim($params['reciveid']);

        //二人会话标识
        // $map['tag'] = array('in',['u'.$sendid.'c'.$reciveid,'u'.$reciveid.'c'.$sendid]) ;
        $map['sendid'] = $sendid ;
        $map['reciveid'] = $reciveid ;
        db('hx_msg')->where($map)->update(['status'=>1]);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }

    /**
     * [sendhxmsg_shop description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function sendhxmsg_shop($params)
    {
        $sendid = trim($params['account']);
        $reciveid = trim($params['reciveid']);
        $msg = trim($params['msg']);
        $status = $params['status'];
        // $status = 0;
        // if (isset($params['status'])) {
        //    $status = $params['status'];
        // }


        $data['sendid'] = $sendid;
        $data['reciveid'] = $reciveid;
        $data['msg'] = $msg;
        $data['status'] = $status;
        $data['create_time'] = time();
        //二人会话标识
        $data['tag'] = 'u'.$reciveid.'c'.$sendid;

        db('hx_msg')->insert($data);
        //返回信息
        $data = [
            'code'=>'1',
            'msg'=>'',
            'data'=>1
        ];
        return json($data);
    }
    /*
    |--------------------------------------------------------------------------
    | 公用方法
    |--------------------------------------------------------------------------
     */ 
    /**
     * [create_msg 创建消息]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function create_msg($data)
    {
        
        //消息类型 0 系统消息  1订单系统
        $data['type'] = $data['type'];
        $data['subtitle'] = trim($data['subtitle']);
        $data['title'] = trim($data['title']);
        $data['descrption'] = trim($data['descrption']);
        $data['sendid'] = trim($data['sendid']);
        $data['reciveid'] = trim($data['reciveid']);
        $data['create_time'] = time();

        //插入数据
        $me = db('msg')->insert($data);
        return db('msg')->getLastInsID();

    }
    /**
     * [_seve_img 上传头像]
     * @param  [type] $avar [description]
     * @return [type]       [description]
     */
    public function _seve_img($avar)
    {
        $imageName = "25220_".date("His",time())."_".rand(1111,9999).'.png';
        

        $path = "public/uploads/images/".date("Ymd",time());
        if (!is_dir($path)){ //判断目录是否存在 不存在就创建
            mkdir($path,0777,true);
        }
        $imageSrc=  $path."/". $imageName;  //图片名字

        $r = file_put_contents(ROOT_PATH ."public/".$imageSrc, base64_decode($avar));//返回的是字节数
        if (!$r) {
            return false;
        }else{
            return $imageSrc;
        }
    }

    /** 
     * 计算两个时间段是否有交集（边界重叠不算） 
     * 
     * @param string $beginTime1 开始时间1 
     * @param string $endTime1 结束时间1 
     * @param string $beginTime2 开始时间2 
     * @param string $endTime2 结束时间2 
     * @return bool 
     * @author blog.snsgou.com 
     */ 

    function is_time_cross($beginTime1 = '', $endTime1 = '', $beginTime2 = '', $endTime2 = '')  
    {  
        $status = $beginTime2 - $beginTime1;  
        if ($status > 0)  
        {  
            $status2 = $beginTime2 - $endTime1;  
            if ($status2 >= 0)  
            {  
                return false;  
            }  
            else  
            {  
                return true;  
            }  
        }  
        else  
        {  
            $status2 = $endTime2 - $beginTime1;  
            if ($status2 > 0)  
            {  
                return true;  
            }  
            else  
            {  
                return false;  
            }  
        }  
    }

    /**
     * [sendmsg description]
     * @param  [type] $mobile [description]
     * @return [type]         [description]
     */
    public function sendadvicemsg($mobile,$content,$sj)
    {
        $apikey = "8df6ed7129c50581eecdf1e875edbaa3"; 

        $text = '【大观心理】温馨提示：您有新的心理咨询预约：'.$content."，时间（".date('Y-m-d H:i',$sj)."）"; 

        // error_log($text,3,'/home/wwwroot/daguan/mobile.log');
        $ch = curl_init();
 
         /* 设置验证方式 */
         curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8',
             'Content-Type:application/x-www-form-urlencoded', 'charset=utf-8'));
         /* 设置返回结果为流 */
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         
         /* 设置超时时间*/
         curl_setopt($ch, CURLOPT_TIMEOUT, 10);
         
         /* 设置通信方式 */
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         
         // 发送短信
         $data = array('text'=>$text,'apikey'=>$apikey,'mobile'=>$mobile);
         $json_data = $this->send($ch,$data);
         // error_log($json_data,3,'/home/wwwroot/daguan/sendmsg.log');
         $array = json_decode($json_data,true); 
         // print_r($array);exit; 
         if ($array['code']==0) {
            return true;
         }else{
            return false;
         }
    }

    /**
     * [sendmsg description]
     * @param  [type] $mobile [description]
     * @return [type]         [description]
     */
    public function sendcanlcemsg($mobile,$content,$sj)
    {
        $apikey = "8df6ed7129c50581eecdf1e875edbaa3"; 

        $text = "【大观心理】温馨提示：您的心理咨询预约".$content."（".date('Y-m-d H:i',$sj)."）已取消。"; 

        // $text = '【大观心理】温馨提示：您有新的心理咨询预约：'.$content; 

        // error_log($text,3,'/home/wwwroot/daguan/mobile.log');
        $ch = curl_init();
 
         /* 设置验证方式 */
         curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8',
             'Content-Type:application/x-www-form-urlencoded', 'charset=utf-8'));
         /* 设置返回结果为流 */
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         
         /* 设置超时时间*/
         curl_setopt($ch, CURLOPT_TIMEOUT, 10);
         
         /* 设置通信方式 */
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

         // 发送短信
         $data = array('text'=>$text,'apikey'=>$apikey,'mobile'=>$mobile);
         $json_data = $this->send($ch,$data);
         // error_log($json_data,3,'/home/wwwroot/daguan/sendmsg.log');
         $array = json_decode($json_data,true); 
         if ($array['code']==0) {
            return true;
         }else{
            return false;
         }
    }

    /**
     * [sendmsg description]
     * @param  [type] $mobile [description]
     * @return [type]         [description]
     */
    public function sendmsg($mobile)
    {
        $apikey = "8df6ed7129c50581eecdf1e875edbaa3"; 

        $code  = rand(1000,9999);
        $text="【大观心理】您的验证码是".$code; 

        // error_log($text,3,'/home/wwwroot/daguan/mobile.log');
        $ch = curl_init();
 
         /* 设置验证方式 */
         curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8',
             'Content-Type:application/x-www-form-urlencoded', 'charset=utf-8'));
         /* 设置返回结果为流 */
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         
         /* 设置超时时间*/
         curl_setopt($ch, CURLOPT_TIMEOUT, 10);
         
         /* 设置通信方式 */
         curl_setopt($ch, CURLOPT_POST, 1);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         
         // 发送短信
         $data = array('text'=>$text,'apikey'=>$apikey,'mobile'=>$mobile);
         $json_data = $this->send($ch,$data);
         // error_log($json_data,3,'/home/wwwroot/daguan/sendmsg.log');
         $array = json_decode($json_data,true);  
         if ($array['code']==0) {
            return $code;
         }else{
            return false;
         }
    }
    /**
     * [send description]
     * @param  [type] $ch   [description]
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    function send($ch,$data){
         curl_setopt ($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/sms/single_send.json');
         curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
         $result = curl_exec($ch);
         $error = curl_error($ch);
         // checkErr($result,$error);
         return $result;
     }
    /**
     * [calcAge description]
     * @param  [type] $birthday [description]
     * @return [type]           [description]
     */
    public function calcAge($birthday) {  
        $age = 0;  
        if(!empty($birthday)){  
            
              
            list($y1,$m1,$d1) = explode("-",date("Y-m-d", $age));  
              
            list($y2,$m2,$d2) = explode("-",date("Y-m-d"), time());  
              
            $age = $y2 - $y1;  
            if((int)($m2.$d2) < (int)($m1.$d1)){  
                $age -= 1;  
            }  
        }  
        return $age;  
    }

    public function handleNm($str = null)
    {
        if ($str==null) {
            return '';
        }
          //判断是否包含中文字符
        if(preg_match("/[\x{4e00}-\x{9fa5}]+/u", $str)) {
            //按照中文字符计算长度
            $len = mb_strlen($str, 'UTF-8');
            //echo '中文';
            if($len >= 3){
                //三个字符或三个字符以上掐头取尾，中间用*代替
                $str = mb_substr($str, 0, 1, 'UTF-8') . '*' . mb_substr($str, -1, 1, 'UTF-8');
            } elseif($len == 2) {
                //两个字符
                $str = mb_substr($str, 0, 1, 'UTF-8') . '*';
            }
        } else {
            //按照英文字串计算长度
            $len = strlen($str);
            //echo 'English';
            if($len >= 3) {
                //三个字符或三个字符以上掐头取尾，中间用*代替
                $str = substr($str, 0, 1) . '*' . substr($str, -1);
            } elseif($len == 2) {
                //两个字符
                $str = substr($str2, 0, 1) . '*';
            }
        }
        return $str;
    }  
}   

