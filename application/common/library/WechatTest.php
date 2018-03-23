<?php

namespace app\common\library;

use app\admin\model\AuthRule;
use fast\Tree;
use think\Exception;
use think\exception\PDOException;
use think\Db;
use fast\Random;
class WechatTest

{



    public $appId     = 'wx6a422240c30416ce';
    public $appSecret = '547b40ab9eb75c1c86b34521be425f97';
    public $token='Niuxingyu2017';
    public $downUrl='http://game.niuxingyu.com/download/';
    public $dbhost    = '47.104.105.93';
    public $dbname    = 'chessgame';
    public $dbuser    = 'root';
    public $dbpwd     = 'niuxingyu2017';
    public $md5str    = 'chess_';

    // public function __construct(){
    //     $config=get_addon_config('wechat');
    //     $this->appId=$config['app_id'];
    //     $this->appSecret=$config['secret'];
    //     $this->token=$config['token'];
    // }


    //验证消息

    public function valid()

    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){

            echo $echoStr;

            exit;

        }

    }
    //检查签名

    public function checkSignature()

    {

        $signature = $_GET["signature"];

        $timestamp = $_GET["timestamp"];

        $nonce = $_GET["nonce"];

        $token = $this->token;

        $tmpArr = array($token, $timestamp, $nonce);

        sort($tmpArr);

        $tmpStr = implode( $tmpArr );

        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){

            return true;

        }else{

            return false;

        }

    }
    //响应消息

    public function responseMsgs(){
        echo 111;exit;
    }
    //接收事件消息

    public function receiveEvent($object)

    {

        $content = "";

        switch ($object->Event)

        {

          case "subscribe":

           // $content = $this->followHandle($object);
           $content = "欢迎关注";
           break;

          case "unsubscribe":

            $content = "取消关注";

            break;

          case "SCAN":

            $content = '您已关注世纪棋牌'; 
            $result = $this->transmitText($object, $content);

            break;
         default:

            $content = "receive a new event: ";

            break;

        }

        $result = $this->transmitText($object, $content);

        return $result;

    }

    public function transmitText($object, $content)

    {
            $textTpl = "<xml>

                <ToUserName><![CDATA[%s]]></ToUserName>

                <FromUserName><![CDATA[%s]]></FromUserName>

                <CreateTime>%s</CreateTime>

                <MsgType><![CDATA[text]]></MsgType>

                <Content><![CDATA[%s]]></Content>

                </xml>";

            $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);

            return $result;
    }

}
