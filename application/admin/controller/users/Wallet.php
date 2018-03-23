<?php

namespace app\admin\controller\users;

use app\common\controller\Backend;

use think\Controller;
use think\Request;
use think\Db;

/**
 * 资金记录
 *
 * @icon fa fa-circle-o
 */
class Wallet extends Backend
{
    
    /**
     * UserWallet模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('UserWallet');

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
            $total = Db::table('v_wallet_user')
                    ->where($where)
                    ->limit($offset, $limit)
                    ->order($sort, $order)
                    ->count();
            $list = Db::table('v_wallet_user')
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            foreach ($list as $key => &$value) {
                $userInfo=model('user')->get($value['uid']);
                $value['pic']=$value['headimgurl'];  
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        $onedata = $this->model->where(array('uid'=>$row['uid'] ))->order('id desc')->find();
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                $userwallet=Db::table('v_wallet_user')
                    ->where(array('uid'=>$row['uid']))
                    ->find();
                $data=array(
                    'uid'      => $row['uid'],
                    'ingot'    => intval($userwallet['ingot'])+intval($params['ingot_change']),
                    'ingot_change'     =>$params['ingot_change'],
                    'gold'=>0,
                    'gold_change'=>0,
                    'roomcard'=>0,
                    'roomcard_change'=>0,
                    'source'   => 0,
                    'type'     => 17,
                    'info'     => $params['info'],
                    'remark' =>$params['info'],
                );
                $result = $this->model->allowField(true)->save($data);
                if ($result !== false)
                {
                    $this->success();
                }
                else
                {
                    $this->error($row->getError());
                }
               
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}
