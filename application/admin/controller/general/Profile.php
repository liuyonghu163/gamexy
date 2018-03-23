<?php

namespace app\admin\controller\general;

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
class Profile extends Backend
{
    protected $userInfo = null;

     public function _initialize()
    {
        parent::_initialize();
        $admin = Session::get('admin');
        $userInfo=model('user')->get(['uselessid' => $admin->username]);
        $userWallet=Db::name('user_wallet')->where(['uid' => $userInfo['id']])->find();
        $user_count=model('user')->where(array('pid'=>$userInfo['id']))->count();
        $this->userInfo=$userInfo;
        $this->view->assign("userInfo", $userInfo);
        $this->view->assign("userWallet", $userWallet);
        $this->view->assign("user_count", $user_count);
    }
    /**
     * 查看
     */
    public function index()
    {
        $userInfo=$this->userInfo;
        $codeurl = explode(',', $userInfo['weixinurl']);
        if(isset($codeurl[1])){
            $codeurl=$codeurl;
        }else{
            $wechatObj = new Weiphps(); 
            $redata=$wechatObj->generateCode($userInfo['unionid']);
            $userInfo->save(array('weixinurl'=>$redata['comurl']));
            $codeurl=array(0=>$redata['url'],1=> $redata['exurl']);

        }
        //用户所有提现金额
        $withdrawals=$this->getCommission($userInfo['id']);
        $totalnum=0;
        $qxsum=model('withdrawals')->where(array('uid'=>$userInfo['id']))->sum('money');
        $totalnum=$totalnum*0.01-$qxsum;
        $this->view->assign("codeurl", $codeurl);
        $this->view->assign("totalnum", $totalnum);
        return $this->view->fetch();
    }

    /**
     * 更新个人信息
     */
    public function update()
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            $params = array_filter(array_intersect_key($params, array_flip(array('email', 'nickname', 'password', 'avatar'))));
            unset($v);
            if (isset($params['password']))
            {
                $params['salt'] = Random::alnum();
                $params['password'] = md5(md5($params['password']) . $params['salt']);
            }
            if ($params)
            {
                model('admin')->where('id', $this->auth->id)->update($params);
                //因为个人资料面板读取的Session显示，修改自己资料后同时更新Session
                $admin = Session::get('admin');
                $admin_id = $admin ? $admin->id : 0;
                if($this->auth->id==$admin_id){
                    $admin = model('admin')->get(['id' => $admin_id]);
                    Session::set("admin", $admin);
                }
                $this->success();
            }
            $this->error();
        }
        return;
    }
   
}
