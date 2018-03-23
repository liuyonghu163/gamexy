<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use think\Db;
use think\Session;
class User extends Frontend
{

    protected $layout = '';
    protected $md5str    = 'chess_';

    public function _initialize()
    {
        parent::_initialize();
        
    }
    public function index(){
    	if(Session::get('userid')){
    		$this->redirect('index/index');
    	}
    	$this->redirect('index/user/login');
    }
    /**
     * 代理登录
     */
    public function login()
    {
    	if(Session::get('userid')){
    		$this->redirect('index/index');
    	}
    	if ($this->request->isPost())
        {
     		$uselessid = $this->request->post('uselessid');
            $pwd = $this->request->post('pwd');
            $respwd=md5($this->md5str.$pwd);
            $where=[
                'uselessid'  => $uselessid,
                'pwd'  => $respwd,
            ];
            $userInfo=Db::name('user')->where($where)->find();
            if($userInfo){
            	Session::set("userid", $userInfo['id']);
            	set_json(200,'登录成功');
            }else{
            	set_json(500,'登录失败');

            }
        }
        return $this->fetch(); 
    }
    /**
     * 注销登录
     */
   	public function logout(){
   		Session::delete("userid");
   		$this->success(__('退出成功'), 'index/user/login');
   	}
    

}
