<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Db;
use think\Session;
class Index extends Frontend
{

    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
         if(!Session::get('userid')){
            $this->redirect('index/user/login');
        }
    }

    public function index()
    {
        return $this->fetch(); 
    }

    

}
