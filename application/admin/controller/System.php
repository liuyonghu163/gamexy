<?php

namespace app\admin\controller;

use app\common\controller\Backend;

use think\Controller;
use think\Request;
use think\Db;
use fast\Random;
use think\Session;


/**
 * 公告列表
 *
 * @icon fa fa-circle-o
 */
class System extends Backend
{
    
    /**
     * System模型对象
     */
    protected $model = null;
    protected $userInfo = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('System');
        $admin = Session::get('admin');
        $userInfo=model('user')->get(['uselessid' => $admin->username]);
        $userWallet=Db::name('user_wallet')->where(['uid' => $userInfo['id']])->find();
        $user_count=model('user')->where(array('pid'=>$userInfo['id']))->count();
        $this->userInfo=$userInfo;
        $getStateList=$this->model->getStateList();
        $this->view->assign("getStateList", $getStateList);
        $this->view->assign("userInfo", $userInfo);
        $this->view->assign("userWallet", $userWallet);
        $this->view->assign("user_count", $user_count);

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个方法
     * 因此在当前控制器中可不用编写增删改查的代码,如果需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
      /**
     * 查看
     */
    public function index()
    {
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->where($where)
                    ->limit($offset, $limit)
                    ->order($sort, $order)
                    ->count();
            $list = $this->model
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            foreach ($list as $key => &$value) {
                $userInfo=model('user')->get($value['uid']);
                $value['nickname']=$userInfo['nickname'];
                $value['pic']=$userInfo['headimgurl'];  
                $value['uselessid']=$userInfo['uselessid'];     
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add()
    {
        //$sql = 'SELECT * FROM v_server WHERE type=1';
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                
                $result = $this->model->allowField(true)->save($params);
                if ($result !== false)
                {
                    $serdata = Db::table('v_server')->where('type',1)->select();
                    foreach ($serdata as $key => $value) {
                        $ip = $value['ip'];
                        $port = $value['port'];
                        $this->client($ip, $port);
                    }
                    $this->success();
                }
                else
                {
                    $this->error($this->model->getError());
                }
              
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }
    
}
