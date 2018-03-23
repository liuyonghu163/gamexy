<?php

namespace app\admin\controller;

use app\common\controller\Backend;

use think\Controller;
use think\Request;
use think\Db;
use fast\Random;
use think\Session;

/**
 * 提现列表
 *
 * @icon fa fa-circle-o
 */
class Withdrawals extends Backend
{
    
    /**
     * Withdrawals模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Withdrawals');
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
     * 设为提现
     */
    public function is_state($ids=""){
        $withdrawalsInfo=$this->model->get($ids);
        $res=$withdrawalsInfo->save(array('state'=>2));
        if($res){
            $this->success();
        }else{
            $this->error('更新失败');
        }
    }
}
