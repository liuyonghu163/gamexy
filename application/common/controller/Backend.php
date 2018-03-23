<?php

namespace app\common\controller;

use app\admin\library\Auth;
use think\Config;
use think\Controller;
use think\Hook;
use think\Lang;
use think\Session;
use think\Db;
use app\common\library\Connector;
use app\common\library\Weiphps;
/**
 * 后台控制器基类
 */
class Backend extends Controller
{

    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];

    /**
     * 布局模板
     * @var string
     */
    protected $layout = 'default';

    /**
     * 权限控制类
     * @var Auth
     */
    protected $auth = null;

    /**
     * 快速搜索时执行查找的字段
     */
    protected $searchFields = 'id';

    /**
     * 是否是关联查询
     */
    protected $relationSearch = false;

    /**
     * 是否开启数据限制
     * 支持auth/personal
     * 表示按权限判断/仅限个人 
     * 默认为禁用,若启用请务必保证表中存在admin_id字段
     */
    protected $dataLimit = false;

    /**
     * 数据限制字段
     */
    protected $dataLimitField = 'admin_id';

    /**
     * 是否开启Validate验证
     */
    protected $modelValidate = false;

    /**
     * 是否开启模型场景验证
     */
    protected $modelSceneValidate = false;

    /**
     * Multi方法可批量修改的字段
     */
    protected $multiFields = 'status';

    /**
     * 引入后台控制器的traits
     */
    use \app\admin\library\traits\Backend;

    public function _initialize()
    {
        $modulename = $this->request->module();
        $controllername = strtolower($this->request->controller());
        $actionname = strtolower($this->request->action());

        $path = str_replace('.', '/', $controllername) . '/' . $actionname;

        // 定义是否Addtabs请求
        !defined('IS_ADDTABS') && define('IS_ADDTABS', input("addtabs") ? TRUE : FALSE);

        // 定义是否Dialog请求
        !defined('IS_DIALOG') && define('IS_DIALOG', input("dialog") ? TRUE : FALSE);

        // 定义是否AJAX请求
        !defined('IS_AJAX') && define('IS_AJAX', $this->request->isAjax());

        $this->auth = Auth::instance();

        // 设置当前请求的URI
        $path='information/index';
        
        $this->auth->setRequestUri($path);
        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin))
        {
            //检测是否登录
            if (!$this->auth->isLogin())
            {
                Hook::listen('admin_nologin', $this);
                $url = Session::get('referer');
                $url = $url ? $url : $this->request->url();
                $this->error(__('Please login first'), url('index/login', ['url' => $url]));
            }
            // 判断是否需要验证权限
            if (!$this->auth->match($this->noNeedRight))
            {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path))
                {
                    Hook::listen('admin_nopermission', $this);
                    $this->error(__('You have no permission'), '');
                }
            }
        }

        // 非选项卡时重定向
        if (!$this->request->isPost() && !IS_AJAX && !IS_ADDTABS && !IS_DIALOG && input("ref") == 'addtabs')
        {
            $url = preg_replace_callback("/([\?|&]+)ref=addtabs(&?)/i", function($matches) {
                return $matches[2] == '&' ? $matches[1] : '';
            }, $this->request->url());
            $this->redirect('index/index', [], 302, ['referer' => $url]);
            exit;
        }

        // 设置面包屑导航数据
        $breadcrumb = $this->auth->getBreadCrumb($path);
        array_pop($breadcrumb);
        $this->view->breadcrumb = $breadcrumb;

        // 如果有使用模板布局
        if ($this->layout)
        {
            $this->view->engine->layout('layout/' . $this->layout);
        }

        // 语言检测
        $lang = strip_tags(Lang::detect());

        $site = Config::get("site");

        $upload = \app\common\model\Config::upload();

        // 上传信息配置后
        Hook::listen("upload_config_init", $upload);

        // 配置信息
        $config = [
            'site'           => array_intersect_key($site, array_flip(['name', 'cdnurl', 'version', 'timezone', 'languages'])),
            'upload'         => $upload,
            'modulename'     => $modulename,
            'controllername' => $controllername,
            'actionname'     => $actionname,
            'jsname'         => 'backend/' . str_replace('.', '/', $controllername),
            'moduleurl'      => rtrim(url("/{$modulename}", '', false), '/'),
            'language'       => $lang,
            'fastadmin'      => Config::get('fastadmin'),
            'referer'        => Session::get("referer")
        ];
            
        Config::set('upload', array_merge(Config::get('upload'), $upload));
        
        // 配置信息后
        Hook::listen("config_init", $config);
        //加载当前控制器语言包
        $this->loadlang($controllername);
        //渲染站点配置
        $this->assign('site', $site);
        //渲染配置信息
        $this->assign('config', $config);
        //渲染权限对象
        $this->assign('auth', $this->auth);
        //渲染管理员对象
        $this->assign('admin', Session::get('admin'));
    }

    /**
     * 加载语言文件
     * @param string $name
     */
    protected function loadlang($name)
    {
        Lang::load(APP_PATH . $this->request->module() . '/lang/' . Lang::detect() . '/' . str_replace('.', '/', $name) . '.php');
    }

    /**
     * 渲染配置信息
     * @param mixed $name 键名或数组
     * @param mixed $value 值 
     */
    protected function assignconfig($name, $value = '')
    {
        $this->view->config = array_merge($this->view->config ? $this->view->config : [], is_array($name) ? $name : [$name => $value]);
    }

    /**
     * 生成查询所需要的条件,排序方式
     * @param mixed $searchfields 快速查询的字段
     * @param boolean $relationSearch 是否关联查询
     * @return array
     */
    protected function buildparams($searchfields = null, $relationSearch = null)
    {
        $searchfields = is_null($searchfields) ? $this->searchFields : $searchfields;
        $relationSearch = is_null($relationSearch) ? $this->relationSearch : $relationSearch;
        $search = $this->request->get("search", '');
        $filter = $this->request->get("filter", '');
        $op = $this->request->get("op", '', 'trim');
        $sort = $this->request->get("sort", "id");
        $order = $this->request->get("order", "DESC");
        $offset = $this->request->get("offset", 0);
        $limit = $this->request->get("limit", 0);
        $filter = json_decode($filter, TRUE);
        $op = json_decode($op, TRUE);
        $filter = $filter ? $filter : [];
        $where = [];
        $tableName = '';
        if ($relationSearch)
        {
            if (!empty($this->model))
            {
                $class = get_class($this->model);
                $name = basename(str_replace('\\', '/', $class));
                $tableName = $this->model->getQuery()->getTable($name) . ".";
            }
            $sort = stripos($sort, ".") === false ? $tableName . $sort : $sort;
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds))
        {
            $where[] = [$this->dataLimitField, 'in', $adminIds];
        }
        if ($search)
        {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v)
            {
                $v = stripos($v, ".") === false ? $tableName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
        foreach ($filter as $k => $v)
        {
            $sym = isset($op[$k]) ? $op[$k] : 'LIKE %...%';
            if (stripos($k, ".") === false)
            {
                $k = $tableName . $k;
            }
            $sym = strtoupper(isset($op[$k]) ? $op[$k] : $sym);
            $sym= 'LIKE %...%';

            switch ($sym)
            {
                case '=':
                case '!=':
                    $where[] = [$k, $sym, (string) $v];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                $where[] = [$k, 'LIKE', "%{$v}%"];
                    break;
                case 'NOT LIKE %...%':
                    $where[] = [$k, trimodel(str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, intval($v)];
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)':
                    $where[] = [$k, str_replace('(...)', '', $sym), explode(',', $v)];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr))
                        continue;
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '')
                    {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = $arr[1];
                    }
                    else if ($arr[1] === '')
                    {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, $sym, $arr];
                    break;
                case 'RANGE':
                case 'NOT RANGE':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr))
                        continue;
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '')
                    {
                        $sym = $sym == 'RANGE' ? '<=' : '>';
                        $arr = $arr[1];
                    }
                    else if ($arr[1] === '')
                    {
                        $sym = $sym == 'RANGE' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, str_replace('RANGE', 'BETWEEN', $sym) . ' time', $arr];
                    break;
                case 'LIKE':
                case 'LIKE %...%':
                    $where[] = [$k, 'LIKE', "%{$v}%"];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
        }
        $where = function($query) use ($where) {
            foreach ($where as $k => $v)
            {
                if (is_array($v))
                {
                    call_user_func_array([$query, 'where'], $v);
                }
                else
                {
                    $query->where($v);
                }
            }
        };
        return [$where, $sort, $order, $offset, $limit];
    }

    /**
     * 获取数据限制的管理员ID
     * 禁用数据限制时返回的是null
     * @return mixed
     */
    protected function getDataLimitAdminIds()
    {
        if (!$this->dataLimit)
        {
            return null;
        }
        $adminIds = [];
        if (in_array($this->dataLimit, ['auth', 'personal']))
        {
            $adminIds = $this->dataLimit == 'auth' ? $this->auth->getChildrenAdminIds(true) : [$this->auth->id];
        }
        return $adminIds;
    }

    /**
     * Selectpage的实现方法
     * 
     * 当前方法只是一个比较通用的搜索匹配,请按需重载此方法来编写自己的搜索逻辑,$where按自己的需求写即可
     * 这里示例了所有的参数，所以比较复杂，实现上自己实现只需简单的几行即可
     * 
     */
    protected function selectpage()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'htmlspecialchars']);

        //搜索关键词,客户端输入以空格分开,这里接收为数组
        $word = (array) $this->request->request("q_word/a");
        //当前页
        $page = $this->request->request("page");
        //分页大小
        $pagesize = $this->request->request("per_page");
        //搜索条件
        $andor = $this->request->request("and_or");
        //排序方式
        $orderby = (array) $this->request->request("order_by/a");
        //显示的字段
        $field = $this->request->request("field");
        //主键
        $primarykey = $this->request->request("pkey_name");
        //主键值
        $primaryvalue = $this->request->request("pkey_value");
        //搜索字段
        $searchfield = (array) $this->request->request("search_field/a");
        //自定义搜索条件
        $custom = (array) $this->request->request("custom/a");
        $order = [];
        foreach ($orderby as $k => $v)
        {
            $order[$v[0]] = $v[1];
        }
        $field = $field ? $field : 'name';

        //如果有primaryvalue,说明当前是初始化传值
        if ($primaryvalue !== null)
        {
            $where = [$primarykey => ['in', $primaryvalue]];
        }
        else
        {
            $where = function($query) use($word, $andor, $field, $searchfield, $custom) {
                foreach ($word as $k => $v)
                {
                    foreach ($searchfield as $m => $n)
                    {
                        $query->where($n, "like", "%{$v}%", $andor);
                    }
                }
                if ($custom && is_array($custom))
                {
                    foreach ($custom as $k => $v)
                    {
                        $query->where($k, '=', $v);
                    }
                }
            };
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds))
        {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = [];
        $total = $this->model->where($where)->count();
        if ($total > 0)
        {
            if (is_array($adminIds))
            {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($where)
                    ->order($order)
                    ->page($page, $pagesize)
                    ->field("{$primarykey},{$field}")
                    ->field("password,salt", true)
                    ->select();
        }
        //这里一定要返回有list这个字段,total是可选的,如果total<=list的数量,则会隐藏分页按钮
        return json(['list' => $list, 'total' => $total]);
    }
    public function make_qrcode($content, $errorLevel, $imgsize, $qrcode_path, $logo, $qrcode_path_new){
      Vendor('phpqrcode.phpqrcode');
      $object = new \QRcode();       
      ob_clean();
      $object::png($content, $qrcode_path, $errorLevel, $imgsize, 2);      
      $QR = $qrcode_path;//已经生成的原始二维码图   
      if ($logo !== FALSE) {   
          $QR = imagecreatefromstring(file_get_contents($QR));   
          $logo = imagecreatefromstring(file_get_contents($logo)); 
        if (imageistruecolor($logo))
         {
            imagetruecolortopalette($logo, false, 65535);//添加这行代码来解决颜色失真问题
         }  
          $QR_width = imagesx($QR);//二维码图片宽度   
          $QR_height = imagesy($QR);//二维码图片高度   
          $logo_width = imagesx($logo);//logo图片宽度   
          $logo_height = imagesy($logo);//logo图片高度   
          $logo_qr_width = $QR_width / 4;   
          $scale = $logo_width/$logo_qr_width;   
          $logo_qr_height = $logo_height/$scale;   
          $from_width = ($QR_width - $logo_qr_width) / 2;   
          //重新组合图片并调整大小   
          imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,   
          $logo_qr_height, $logo_width, $logo_height);   
      }   
      //输出图片
      imagepng($QR, $qrcode_path_new);   
    }
       //获取用户
   public function getCommission($uid,$username){
        $config=model('configs')->field('k,v')->select();
        foreach ($config as $key => $value) {
            $config_list[$value['k']]=$value['v']/10000;
        }
        $max=$config_list['FIRST_LEVEL_AGENT'];
        $min=$config_list['TWO_LEVEL_AGENT'];
        $small=$config_list['THREE_LEVEL_AGENT'];
        $totalnum=0;
        $admininfo=model('Admin')->where(array('username'=>$username))->find();
        $createtime=date('Y-m-d H:i:s',$admininfo['createtime']);
        //当前用户自己佣金
        $menum= model('UserWallet')->where(array('uid'=>$uid,'type'=>10))->where('ctime','>',$createtime)->sum('ingot_change');
        $totalnum=$totalnum+abs($menum)*$max;
        //当前用户一级用户
        $userList=model('user')->where(array('pid'=>$uid))->field('id,agent')->select();

        //当前用户所有一级用户佣金
        foreach ($userList as $key => $value) {
            //当前一级用户佣金
            $yi_num=model('user_wallet')->where(array('uid'=>$value['id'],'type'=>10))->where('ctime','>',$createtime)->sum('ingot_change');
            //判断当前用户是代理*min 普通玩家*max
            if($value['agent']==1){
                $totalnum=$totalnum+abs($yi_num)*$min;
            }else{
                $totalnum=$totalnum+abs($yi_num)*$max;
            }
            //当前用户二级用户
            $eruserList=model('user')->where(array('pid'=>$value['id']))->field('id,agent')->select();
            foreach ($eruserList as $ke => $va) {
                $er_num=model('user_wallet')->where(array('uid'=>$va['id'],'type'=>10))->where('ctime','>',$createtime)->sum('ingot_change');
                //判断当前用户是代理*small 普通玩家*min
                if($va['agent']==1){
                    //echo $small;exit;
                    $totalnum=$totalnum+abs($er_num)*$small;
                }else{
                    $totalnum=$totalnum+abs($er_num)*$min;
                }

              //  echo $er
                 //当前用户三级用户
                $sanuserList=model('user')->where(array('pid'=>$va['id']))->field('id,agent')->select();
                foreach ($sanuserList as $k=> $v) {
                    //普通玩家*small
                    $san_num=model('user_wallet')->where(array('uid'=>$v['id'],'type'=>10))->where('ctime','>',$createtime)->sum('ingot_change');
                    if($v['agent']==0){
                        $totalnum=$totalnum+abs($san_num)*$small;
                    }
                }
            }
        }
        return round($totalnum);
   }
   //获取当前用户佣金
   public function getCommissionInfo($uid,$username){
        set_time_limit(0);
        $config=model('configs')->field('k,v')->select();
        foreach ($config as $key => $value) {
            $config_list[$value['k']]=$value['v']/10000;
        }
        $max=$config_list['FIRST_LEVEL_AGENT'];
        $min=$config_list['TWO_LEVEL_AGENT'];
        $small=$config_list['THREE_LEVEL_AGENT'];
        $totalnum=0;
        $admininfo=model('Admin')->where(array('username'=>$username))->find();
        $createtime=date('Y-m-d H:i:s',$admininfo['createtime']);
        //当前用户自己佣金
        $menum= model('user_wallet')->where(array('uid'=>$uid,'type'=>10))->where('ctime','>',$createtime)->sum('ingot_change'); 
        $totalnum=$totalnum+abs($menum)*$max;
        return round($totalnum);
   }
   //获取当前一级用户抽水
   public function getCommissionOne($uid,$username){
       set_time_limit(0);
       $config=model('configs')->field('k,v')->select();
        foreach ($config as $key => $value) {
            $config_list[$value['k']]=$value['v']/10000;
        }
        $max=$config_list['FIRST_LEVEL_AGENT'];
        $min=$config_list['TWO_LEVEL_AGENT'];
        $small=$config_list['THREE_LEVEL_AGENT'];
        $totalnum=0;
        $admininfo=model('Admin')->where(array('username'=>$username))->find();
        $createtime=date('Y-m-d H:i:s',$admininfo['createtime']);
        //当前用户自己佣金
        $menum= model('user_wallet')->where(array('uid'=>$uid,'type'=>10))->where('ctime','>',$createtime)->sum('ingot_change'); 
        $totalnum=$totalnum+abs($menum)*$max;
        //当前用户一级用户
        $userList=model('user')->where(array('pid'=>$uid,'agent'=>0))->field('id,agent')->select();
        foreach ($userList as $key => $value) {
            //当前一级用户佣金
            $yi_num=model('user_wallet')->where(array('uid'=>$value['id'],'type'=>10))->where('ctime','>',$createtime)->sum('ingot_change');
            $totalnum=$totalnum+abs($yi_num)*$max;
        }
        return round($totalnum);
   }
   //获取当前二级用户抽水
    public function getCommissionTwo($uid,$username){
        set_time_limit(0);
        $config=model('configs')->field('k,v')->select();
        foreach ($config as $key => $value) {
            $config_list[$value['k']]=$value['v']/10000;
        }
        $max=$config_list['FIRST_LEVEL_AGENT'];
        $min=$config_list['TWO_LEVEL_AGENT'];
        $small=$config_list['THREE_LEVEL_AGENT'];
        $totalnum=0;
        $admininfo=model('Admin')->where(array('username'=>$username))->find();
        $createtime=date('Y-m-d H:i:s',$admininfo['createtime']);
        //当前用户自己佣金
        $menum= model('user_wallet')->where(array('uid'=>$uid,'type'=>10))->where('ctime','>',$createtime)->sum('ingot_change'); 
        $totalnum=$totalnum+abs($menum)*$min;
        //当前用户一级用户
        $userList=model('user')->where(array('pid'=>$uid,'agent'=>0))->field('id,agent')->select();
        foreach ($userList as $key => $value) {
            //当前一级用户佣金
            $yi_num=model('user_wallet')->where(array('uid'=>$value['id'],'type'=>10))->where('ctime','>',$createtime)->sum('ingot_change');
            $totalnum=$totalnum+abs($yi_num)*$min;
        }
        return round($totalnum);
        
    }
    //获取用户时间段抽水
   public function getCommissiondate($uid,$username,$starttime,$endttime){
        set_time_limit(0); 
        $config=model('configs')->field('k,v')->select();
        foreach ($config as $key => $value) {
            $config_list[$value['k']]=$value['v']/10000;
        }
        $max=$config_list['FIRST_LEVEL_AGENT'];
        $min=$config_list['TWO_LEVEL_AGENT'];
        $small=$config_list['THREE_LEVEL_AGENT'];
        $totalnum=0;
        $admininfo=model('Admin')->where(array('username'=>$username))->find();
        $createtime=date('Y-m-d H:i:s',$admininfo['createtime']);
        //当前用户自己佣金
        $menum= model('UserWallet')->where(array('uid'=>$uid,'type'=>10))->where('ctime','>',$createtime)->where('ctime','>=',$starttime.' 00:00:00')->where('ctime','<=',$endttime.' 23:59:59')->sum('ingot_change');
        $totalnum=$totalnum+abs($menum)*$max;
        //当前用户一级用户
        $userList=model('user')->where(array('pid'=>$uid))->field('id,agent')->select();

        //当前用户所有一级用户佣金
        foreach ($userList as $key => $value) {
            //当前一级用户佣金
            $yi_num=model('user_wallet')->where(array('uid'=>$value['id'],'type'=>10))->where('ctime','>',$createtime)->where('ctime','>=',$starttime.' 00:00:00')->where('ctime','<=',$endttime.' 23:59:59')->sum('ingot_change');
            //判断当前用户是代理*min 普通玩家*max
            if($value['agent']==1){
                $totalnum=$totalnum+abs($yi_num)*$min;
            }else{
                $totalnum=$totalnum+abs($yi_num)*$max;
            }
            //当前用户二级用户
            $eruserList=model('user')->where(array('pid'=>$value['id']))->field('id,agent')->select();
            foreach ($eruserList as $ke => $va) {
                $er_num=model('user_wallet')->where(array('uid'=>$va['id'],'type'=>10))->where('ctime','>',$createtime)->where('ctime','>=',$starttime.' 00:00:00')->where('ctime','<=',$endttime.' 23:59:59')->sum('ingot_change');
                //判断当前用户是代理*small 普通玩家*min
                if($va['agent']==1){
                    //echo $small;exit;
                    $totalnum=$totalnum+abs($er_num)*$small;
                }else{
                    $totalnum=$totalnum+abs($er_num)*$min;
                }

              //  echo $er
                 //当前用户三级用户
                $sanuserList=model('user')->where(array('pid'=>$va['id']))->field('id,agent')->select();
                foreach ($sanuserList as $k=> $v) {
                    //普通玩家*small
                    $san_num=model('user_wallet')->where(array('uid'=>$v['id'],'type'=>10))->where('ctime','>',$createtime)->where('ctime','>=',$starttime.' 00:00:00')->where('ctime','<=',$endttime.' 23:59:59')->sum('ingot_change');
                    if($v['agent']==0){
                        $totalnum=$totalnum+abs($san_num)*$small;
                    }
                }
            }
        }
        return round($totalnum);
   }
    //大区经理收益
    public function largearea_money($uid,$uselessid){
        //获取一级代理
        $totalnum=0;
        $userList=model('user')->where(array('pid'=>$uid))->where('agent','<>',0)->field('id,agent,uselessid')->select();
        foreach ($userList as $key => $value) {
            $totalnum=$totalnum+$this->getCommission($value['id'],$value['uselessid']);
            $eruserList=model('user')->where(array('pid'=>$value['id']))->where('agent','<>',0)->field('id,agent,uselessid')->select();

            foreach ($eruserList as $ke => $va) {
                $totalnum=$totalnum+$this->getCommission($va['id'],$va['uselessid']);
            }
        }   
        return $totalnum;
    }
    //大区经理收益时间
    public function largearea_money_date($uid,$uselessid,$starttime,$endttime){
        //获取一级代理
        $totalnum=0;
        $userList=model('user')->where(array('pid'=>$uid))->where('agent','<>',0)->field('id,agent,uselessid')->select();
        foreach ($userList as $key => $value) {
            $totalnum=$totalnum+$this->getCommissiondate($value['id'],$value['uselessid'],$starttime,$endttime);
            $eruserList=model('user')->where(array('pid'=>$value['id']))->where('agent','<>',0)->field('id,agent,uselessid')->select();

            foreach ($eruserList as $ke => $va) {
                $totalnum=$totalnum+$this->getCommissiondate($va['id'],$va['uselessid'],$starttime,$endttime);
            }
        }   
        return $totalnum;
    }
    //向客户端推送流动公告
    public function client($ip, $port){
        set_time_limit(0); 
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($socket, $ip, $port);
        $content['main'] = 3;
        $content['sub']  = 21;
        $data = json_encode($content);
        socket_write($socket , $data, strlen($data));
        socket_close( $socket );
    }
    
    //获取用户个人资料(type=0时$field为用户id,type=1时$field为用户uselessid)
    public function user_information($field,$type=0){
        if($type){
            $userInfo=model('user')->get(['uselessid' => $field]);
        }else{
            $userInfo=model('user')->get(['id' => $field]);
        }
        return $userInfo;
    }
    //统计当前推广成员总数
    public function user_count_num($uid){
        $user_count=model('user')->where(array('pid'=>$uid))->count();
        return $user_count;
    }
    //当前用户金币数,元宝数,房卡数
    public function user_wallet_info($uid){
        $userWallet=Db::table('v_wallet_user')->where(['uid' => $uid])->find();
        if(!$userWallet){
            $userWallet['ingot'] = 0;
            $userWallet['gold'] = 0;
            $userWallet['roomcard'] = 0;
        }
        return $userWallet;
    }
    //用户推广二维码
    public function my_qrcdoe_info($uid){
        $userInfo=model('user')->get(['id' => $uid]);
        $codeurl = explode(',', $userInfo['weixinurl']);
        if(isset($codeurl[1])){
            $codeurl=$codeurl;
        }else{
            $wechatObj = new Weiphps(); 
            $redata=$wechatObj->generateCode($userInfo['unionid']);
            $userInfo->save(array('weixinurl'=>$redata['comurl']));
            $codeurl=array(0=>$redata['url'],1=> $redata['exurl']);
        }
        
        $qrcode=$codeurl[0];
        $qrcode_path=ROOT_PATH."public/uploads/admin/qrimg/".$userInfo['uselessid'].".png";
        $logo = ROOT_PATH.'public/assets/img/qrlogo.png';
        $qrcode_path_new = ROOT_PATH.'public/uploads/admin/newqrimg/'.$userInfo['uselessid'].'.png';
        $this->make_qrcode($qrcode, 'H', 6, $qrcode_path, $logo, $qrcode_path_new);
        $newqrimg='/uploads/admin/newqrimg/'.$userInfo['uselessid'].'.png';
        $dataview=array(
            'newqrimg'=>$newqrimg,
            'codeurl'=>$codeurl,
        );
       return $dataview;

    }
    //用户累计佣金数
    function commission_sum($uid,$uselessid,$agent=1){
        $withdrawals=$this->getCommission($uid,$uselessid);
        $withdrawals=intval($withdrawals*0.01);
        return $withdrawals;
    }
    //用户佣金数
    function commission_number($uid,$uselessid,$agent=1){
        $totalnum=0;
        //用户提现金额
        $withdrawals=$this->commission_sum($uid,$uselessid,$agent);
        $qxsum=model('withdrawals')->where(array('uid'=>$uid))->sum('money');        
        $totalnum=$withdrawals-intval($qxsum);
        if($totalnum<0){
            $totalnum=0;
        }else{
            $totalnum= $totalnum;
        }
        return $totalnum;
    }
    //用户个人信息
    public function user_infos($uselessid){
        set_time_limit(0);
        $userInfo=$this->user_information($uselessid,$type=1);
        $userWallet=$this->user_wallet_info($userInfo['id']);
        $user_count=$this->user_count_num($userInfo['id']);
        $my_qrcdoe_info=$this->my_qrcdoe_info($userInfo['id']);
        $codeurl=$my_qrcdoe_info['codeurl'];
        $newqrimg=$my_qrcdoe_info['newqrimg'];
        $totalnum=$this->commission_number($userInfo['id'],$userInfo['uselessid'],$userInfo['agent']);
        $listdata=array(
                'userInfo'=>$userInfo,
                'userWallet'=>$userWallet,
                'user_count'=>$user_count,
                'codeurl'=>$codeurl,
                'newqrimg'=>$newqrimg,
                'totalnum'=>$totalnum,
            );
        return $listdata;
    }
    
}
