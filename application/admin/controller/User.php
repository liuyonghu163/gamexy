<?php

namespace app\admin\controller;

use app\admin\model\AuthGroup;
use app\admin\model\AuthGroupAccess;
use app\common\controller\Backend;
use think\Controller;
use think\Request;
use think\Db;
use fast\Random;
use think\Session;
use app\common\library\Weiphps;
/**
 * 推广成员
 *
 * @icon user
 */
class User extends Backend
{
    
    /**
     * User模型对象
     */
    protected $model = null;
    protected $childrenGroupIds = [];
    protected $childrenAdminIds = [];
    protected $userInfo=[];

    public function _initialize()
    {
        parent::_initialize();
        $admin = Session::get('admin');
        $userInfo=model('user')->get(['uselessid' => $admin->username]);
        $this->userInfo=$userInfo;
        $this->model = model('User');
        $this->childrenAdminIds = $this->auth->getChildrenAdminIds(true);
        $this->childrenGroupIds = $this->auth->getChildrenGroupIds($this->auth->isSuperAdmin() ? true : false);

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
                $userWallet=$this->user_wallet_info($value['id']);
                $value['ingot']=$userWallet['ingot'];
                $value['count_num']=0;
                $value['count_num']=$this->user_count_num($value['id']);
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
     /**
     * 设为代理
     */
    public function is_agent($ids=""){
        if($ids){
            $userInfo=$this->model->get($ids);
            $res=$userInfo->save(array('agent'=>1));
            if($res){
                $params['username']=$userInfo['uselessid'];
                $params['nickname']=$userInfo['nickname'];
                $params['salt']=Random::alnum();
                $password="123456";
                $params['password'] = md5(md5($password ).$params['salt']);
                $params['avatar']=$userInfo['headimgurl'];
                model('Admin')->create($params);
                $admin_id = model('Admin')->getLastInsID();
                if($admin_id){
                    $group=array('2');
               // $group = array_intersect($this->childrenGroupIds,  $group);
                // p($group);exit;
                // foreach ($group as $value)
                // {
                    $dataset[] = ['uid' => $admin_id, 'group_id' => $group[0]];
                   // }
                    model('AuthGroupAccess')->saveAll($dataset);
                    $this->success('设置成功');
                }else{
                    $userInfo->save(array('agent'=>0));
                     $this->error('更新失败');
                }
                
            }else{
                $this->error('更新失败');
            }
        }else{
            $this->error('参数错误');
        }
    }
    /**
     * 设为区域代理
     */
    public function is_agent_qu($ids=""){
        $data = array(
            'agent' => 2
        );
        $userInfo=$this->model->get($ids);
        $res = $userInfo->save($data);
        if($res !== false){
            $this->success('设为区域代理成功');
        }else{
            $this->error('设为区域代理失败');
        }

    }
     /**
     * 取消区域代理
     */
    public function is_agent_clenr($ids=""){
        $data = array(
            'agent' => 1
        );
        $userInfo=$this->model->get($ids);
        $res = $userInfo->save($data);
        if($res !== false){
            $this->success('取消区域代理成功');
        }else{
            $this->error('取消区域代理失败');
        }

    }
     /**
     * 取消区域代理
     */
    public function searchlist(){
        $searchlist=array();
        $list=$this->model->getStatusList();
        foreach ($list as $key => $value) {
            $lists[]=array(
                    'id'=>$key,
                    'name'=>$value,
                );
        }
        $searchlist['searchlist']=$lists;

        set_json(1,'',$searchlist);

    }
    /**
     * 封号处理
     */
    public function blockadeAccount($ids=""){
        $data = array(
            'status' => 0
        );
        $userInfo=$this->model->get($ids);
        $res = $userInfo->save($data);
        if($res !== false){
            $this->success('封号成功');
        }else{
            $this->error('失败');
        }

    }
    /**
     * 解封账号
     */
    public function unlockAccount($ids=""){
        $data = array(
            'status' => 1
        );
        $userInfo=$this->model->get($ids);
        $res = $userInfo->save($data);
        if($res !== false){
            $this->success('解封成功');
        }else{
            $this->error('失败');
        }
    }
    /**
     * 用户信息
     */
    public function infos($ids=""){
        $userInfo=$this->user_information($ids,$type=0);
        $viewdata=$this->user_infos($userInfo->uselessid);
        $this->view->assign("userInfo", $viewdata['userInfo']);
        $this->view->assign("userWallet", $viewdata['userWallet']);
        $this->view->assign("user_count", $viewdata['user_count']);
        $this->view->assign("codeurl", $viewdata['codeurl']);
        $this->view->assign("newqrimg", $viewdata['newqrimg']);
        $this->view->assign("totalnum", $viewdata['totalnum']);
        return $this->view->fetch();
    }
    
    /**
     * 用户提现
     */
    public function withdrawals(){
        $get=$this->request->get();
        $ids=isset($get['id']) ? $get['id'] : 0 ;
        if($ids){
            $userInfo=$this->user_information($ids,$type=0);
            $viewdata=$this->user_infos($userInfo->uselessid);
            //$totalnum=100;
            $this->view->assign("totalnum", $viewdata['totalnum']);
            $this->view->assign("withdrawals", $this->commission_sum($userInfo['id'],$userInfo['uselessid'],$userInfo['agent']));
            $qxsum=model('withdrawals')->where(array('uid'=>$ids))->sum('money');
            $this->view->assign("qxsum", intval($qxsum));
            $this->view->assign("codeurl", $viewdata['codeurl']);
            $this->view->assign("userInfo", $userInfo);
            return $this->view->fetch();
        }else{
            $this->error('失败'); 
        } 

    }
    /**
     * 申请提现
     */
     public function getapply(){
        $get=$this->request->get();
        $duihuan=isset($get['duihuan']) ? $get['duihuan'] : 100 ;
        $userInfo=$this->userInfo;
        $totalnum=$this->commission_number($userInfo['id'],$userInfo['uselessid'],$userInfo['agent']);
        if($duihuan!=$totalnum){
            $this->error('操作失败请重试');return;
        }

        if($totalnum<100){
                $this->error('提现金额不能小于100');return;
        }
        if ($this->request->isPost())
        {
              
            $params = $this->request->post("row/a");
            if ($params)
            {
                if(!preg_match("/^1[34578]{1}\d{9}$/",$params['phone'])){   
                  $this->error('联络手机号格式不对');return;
                }
                $params['money']=$totalnum;
                $params['uid']=$userInfo['id'];
                $params['uselessid']=$userInfo['uselessid'];
                model('withdrawals')->create($params);
                $this->success('提现成功','/admin/user/withdrawals?id='.$userInfo['id']);
            }
           // $this->error();
        }

        return $this->view->fetch();
    }
    /**
     * 修改密码
     */
    public function editpasswd(){
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if(strlen($params['password'])<6){
               $this->error('请填写6-16位字符'); 
            }
            if($params['password']!=$params['repassword']){
                $this->error('两次密码不一致');
            }
            $admin = Session::get('admin');
            $admininof=model('admin')->get($admin['id']);
            $password=$params['password'];
            $params['salt']=Random::alnum();
            $updata['password']=md5(md5($password ).$admininof['salt']);
            $res=$admininof->save($updata);
            if($res){
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }

        }
        return $this->view->fetch();
    }
}
