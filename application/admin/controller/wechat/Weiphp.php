<?php

namespace app\admin\controller\wechat;

use app\common\controller\My;
use app\common\model\WechatResponse;
use EasyWeChat\Foundation\Application;
use think\Exception;
use app\common\library\Weiphps;

/**
 * 菜单管理
 *
 * @icon fa fa-list-alt
 */
class Weiphp extends My
{

    protected $wechatcfg = NULL;

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 
     */
    public function index()
    {
        $wechatObj = new Weiphps(); 
        
        if(isset($_GET['echostr'])){
            $wechatObj->valid();
        }else{
            $wechatObj->responseMsg();
        }
    }
    

    

}
