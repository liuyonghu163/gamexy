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
class Gener extends Backend
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
        $get=$this->request->get();
        if(isset($get['id'])){
             $pid=$get['id'];
        }
        $userInfo=$this->userInfo;
        if ($this->request->isAjax())
        {
            $url=$_SERVER['REQUEST_URI'];
            $pidarr=explode("?", $url);
            $pidstr=$pidarr[0];
            $pids=explode('/',$pidstr);
            $pid=end($pids);
            // list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if(isset($get['filter']) && $get['filter']!='{}'){
                $timelist=json_decode($get['filter']);
                $timearr=explode(',',$timelist->ctime);
                $starttime=$timearr[0];
                $endttime=$timearr[1];

            }else{
                $starttime=date('Y-m-d', strtotime('-7 days'));
                $endttime=date('Y-m-d',time());
            }
            $shijc=strtotime($endttime)-strtotime($starttime);
            if($shijc<=0){
                echo '开始时间不能大于结束时间';exit;
            }
            $chaj=$shijc/(24*60*60);
           for ($i=0; $i <=$chaj ; $i++) { 
               $temptime=strtotime($endttime);
               $newarr[$i]=date('Y-m-d',$temptime-24*60*60*$i);
           }
           foreach ($newarr as $key => $value) {
               $list[$key]=array(
                    'ctime'=>$value,
                    'totalnum'=>$this->largearea_money_date($pid,$userInfo['uselessid'],$value,$value)*0.01,
                );
           }
            $limits=(intval($get['limit'])-1)*intval($get['offset']+1);
            $list=array_slice($list,0,$limits);
            $result = array("total" => $chaj+1, "rows" => $list);
            return json($result);
        }
        $days = date('t',time());
        $starttime=date('Y-m',time()).'-01';
        $endttime=date('Y-m',time()).'-'.$days;
        $yuetotal=0;
        $yuetotal=$this->getCommissiondate($pid,$userInfo['uselessid'],$starttime,$endttime);
        $yuetotal=$yuetotal+$this->largearea_money($pid,$userInfo['uselessid']);
        $this->view->assign("yuetotal", $yuetotal*0.01);
        $this->view->assign("getaarr", $get);
        return $this->view->fetch();
    }
     
    
}
