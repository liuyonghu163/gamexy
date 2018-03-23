<?php

namespace app\admin\controller;

use app\common\controller\Backend;

use think\Controller;
use think\Request;

/**
 * 建房记录
 *
 * @icon fa fa-circle-o
 */
class Room extends Backend
{
    
    /**
     * Room模型对象
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Room');

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
        $getTypeOne=$this->model->getTypeOne();
        $getTypeTwo=$this->model->getTypeTwo();
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
                $userInfo=model('user')->get($value['create_user']);
                $value['nickname']=$userInfo['nickname'].'('.$userInfo['uselessid'].')';
                $value['uselessid']=$userInfo['uselessid']; 
                $str=strval($value['type']);
                $value['type_text'] =$getTypeOne[$str[0]]."(".$getTypeTwo[$str[1]].")";
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

}
