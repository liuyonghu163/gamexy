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
class Userer extends Backend
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

        if ($this->request->isAjax())
        {
            $url=$_SERVER['REQUEST_URI'];
            $pidarr=explode("?", $url);
            $pidstr=$pidarr[0];
            $pids=explode('/',$pidstr);
            $pid=end($pids);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $sort='pid';
            $order="ASC";
            $total = $this->model
                    ->where($where)
                    ->where('pid='.$pid.' or id='.$pid)
                    ->order($sort, $order)
                    ->count();
            $list = $this->model
                    ->where($where)
                    ->where('pid='.$pid.' or id='.$pid)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $userInfo=$this->model->get($pid);

            foreach ($list as $key => &$value) {
                $value['commissionOne']=$this->getCommissionOne($value['id'],$value['uselessid'])*0.01;
                $value['commissionTwo']=$this->getCommissionTwo($value['id'],$value['uselessid'])*0.01;
                $value['commissiontxt']=$value['commissionOne']."\r".$value['commissionTwo'];
                $value['totalnum']=$this->model
                    ->where(array('pid'=>$pid))
                    ->order($sort, $order)
                    ->count();
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign("getaarr", $get);
        return $this->view->fetch();
    }
     
    
}
