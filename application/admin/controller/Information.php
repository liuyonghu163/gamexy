<?php

namespace app\admin\controller;

use think\Session;
use app\admin\model\AdminLog;
use app\common\controller\Backend;
use fast\Random;
use think\Db;
use app\common\library\Weiphps;

/**
 * 个人配置
 *
 * @icon fa fa-user
 */
class Information extends Backend
{
    protected $userInfo = null;

     public function _initialize()
    {
        parent::_initialize();
       
    }
    /**
     * 查看
     */
    public function index()
    {
        $admin = Session::get('admin');
        $viewdata=$this->user_infos($admin->username);
        $this->view->assign("userInfo", $viewdata['userInfo']);
        $this->view->assign("userWallet", $viewdata['userWallet']);
        $this->view->assign("user_count", $viewdata['user_count']);
        $this->view->assign("codeurl", $viewdata['codeurl']);
        $this->view->assign("newqrimg", $viewdata['newqrimg']);
        $this->view->assign("totalnum", $viewdata['totalnum']);
        return $this->view->fetch();
    }
   
}
