<?php

namespace app\common\library;

use app\admin\model\AuthRule;
use fast\Tree;
use think\Exception;
use think\exception\PDOException;
use think\Db;
use fast\Random;
class Weiphps

{



    private $appId     = 'wx6a422240c30416ce';
    private $appSecret = '547b40ab9eb75c1c86b34521be425f97';
    private $token='Niuxingyu2017';
    private $downUrl='http://game.niuxingyu.com/download/';

    public function __construct(){
        $config=get_addon_config('wechat');
        $this->appId=$config['app_id'];
        $this->appSecret=$config['secret'];
        $this->token=$config['token'];
    }


    //验证消息

    public function valid()

    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){

            echo $echoStr;

            exit;

        }

    }

    //响应消息

    public function responseMsg()

    {
        //$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = file_get_contents("php://input");
        if (!empty($postStr)){

          $this->logger("R ".$postStr);

          $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

          $RX_TYPE = trim($postObj->MsgType);

          switch ($RX_TYPE)

          {

            case "event":

              $result = $this->receiveEvent($postObj);

              break;

            case "text":

              $result = $this->receiveText($postObj);

              break;

            case "image":

              $result = $this->receiveImage($postObj);

              break;

            case "location":

              $result = $this->receiveLocation($postObj);

              break;

            case "voice":

              $result = $this->receiveVoice($postObj);

              break;

            case "video":

              $result = $this->receiveVideo($postObj);

              break;

            case "link":

              $result = $this->receiveLink($postObj);

              break;

            default:

              $result = "unknow msg type: ".$RX_TYPE;

              break;

          }

          $this->logger("T ".$result);

          echo $result;

        }else {

          echo "访问方式不对";

          exit;

        }

    }

    //获取用户信息以及UnionID 并存入数据库

    public function getUserInfo($openid, $unionid, $agent = 0){

       // $openid='otgZw0qZtqpoEuzahCZeAkSMgjvE';
        // $unionid='oH2t4wZpaiiK4WDOE2wQBKsb6twI';
        $accessToken = $this->operationFile();//获取access_token
        if($accessToken){
            $getUserinfoUrl = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$accessToken."&openid=".$openid."&lang=zh_CN";
            $userInfoResult = $this->getResult($getUserinfoUrl);
            if($userInfoResult){
                $unid = (string)$userInfoResult->unionid;
                $res = $this->selectMysqlNums($unid);//查询数据库里是否存在此用户
                if(!$res){
                    $pid = $this->selectMysqlRows($unionid);
                    $piduserinfo=Db::name('user')->where(array('id'=>$pid))->find();
                    $url = $this->generateCode($userInfoResult->unionid);//生成带参数的二维码
                    $userid=MakeCard();
                    $defaultpwd = '123456';
                    $params_salt = Random::alnum();
                    $password =  md5(md5($defaultpwd) . $params_salt);
                    $userInfo = array(
                        'pid'            => $pid,
                        'uselessid'      => $userid,
                        'unionid'        => $userInfoResult->unionid,
                        'openid'         => $userInfoResult->openid,
                        'nickname'       => $userInfoResult->nickname,
                        'sex'            => $userInfoResult->sex,
                        'headimgurl'     => $userInfoResult->headimgurl,
                        'city'           => $userInfoResult->city,
                        'province'       => $userInfoResult->province,
                        'country'        => $userInfoResult->country,
                        'remark'         => $userInfoResult->remark,
                        'pwd'            => $password,
                        'weixinurl'      => $url['comurl'],
                        'source'         => 1,
                        'agent'          => $agent
                    );
                    $result = $this->operationMysql($userInfo);
                    if($result){
                        $resarr=array(
                            'code'=>1,
                            'id'=>$userid,
                            'pwd'=>$defaultpwd,
                            'pname'=>'星月棋牌',
                            );
                        if($piduserinfo){
                             $resarr['pname']=$piduserinfo['nickname'];
                        }
                        return $resarr;
                    }else{
                        return false;
                    }
                }else{
                    $userInfo=Db::name('user')->where(array('unionid'=>$unid))->find();
                    $piduserinfo=Db::name('user')->where(array('id'=>$userInfo['pid']))->find();
                    if($userInfo){
                         $resarr = array(
                            'code' => 2,
                            'id'   => $userInfo['uselessid'],
                            'pwd'  => 'sjqp'.$userInfo['uselessid'],
                            'pname'=>'星月棋牌',
                        );
                    }else{
                         $resarr = false;
                    }
                    if($piduserinfo){
                         $resarr['pname']=$piduserinfo['nickname'];
                    }
                    return $resarr;
                }

            }else{
                return false;
            }
           
        }else{
            return false;
        }
       


    }



    //获取微信access_token

    public function getAccessToken(){

        $getAccessTokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appId."&secret=".$this->appSecret;
        $accessResult = $this->getResult($getAccessTokenUrl);
        if(isset($accessResult->access_token)){
            return $accessResult->access_token;
        }
        return 0;

    }



    //存储access_token到文件中

    public function operationFile(){

        $accessToken = $this->getAccessToken();
        if($accessToken){
            return $accessToken;
        }else{
            return 0;
        }

    }

    //查询条数

    private function selectMysqlNums($unionid){

        $num=Db::name('user')->where(array('unionid'=>$unionid))->find();   
        if($num){

            return true;

        }else{

            return false;

        }   

    }

    //查询数据库

    public function selectMysqlRows($unionid){

        
        $data=Db::name('user')->where(array('unionid'=>$unionid))->find(); 
        if($data){
            return $data['id'];  
        } else{
            return 0;
        }
        

    }

    //查询游戏ID

    public function selectUselessid($id){

        $con = mysql_connect($this->dbhost, $this->dbuser, $this->dbpwd);

        if (!$con){

            die('Could not connect: ' . mysql_error());

        }

        mysql_select_db($this->dbname, $con);

        $res = mysql_query("select * from chess_user where id=".$id);

        if($res){

            while($row = mysql_fetch_array($res)){

                $uselessid = $row['uselessid'];

            }

            mysql_close($con);

            return $uselessid;

        }else{

            return false;

        }

    }

    //执行存储数据库

    public function operationMysql($data){

        Db::name('user')->insert($data);
        $userId = Db::name('user')->getLastInsID();
        if($userId){
            return $userId;
        }else{
            return false;
        }

    }

    //生成参数的二维码

    public function generateCode($unionid){

        $accessToken = $this->operationFile();
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$accessToken;

        $arr = array(

            //'expire_seconds' => 2592000,

            'action_name' => 'QR_LIMIT_STR_SCENE',

            'action_info' => array(

                "scene" => array(

                    "scene_str" => $unionid

                )

            )

        );

        $data = json_encode($arr);
        
        $result = $this->postResult($data, $url);

        $exurl = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$result['ticket'];//微信接口图片地址

        $resdata = array(

            'ticket' => $result['ticket'],

            'url'    => $result['url'],

            'exurl'  => $exurl,

            'comurl' => $result['url'].','.$exurl

        );

        return $resdata;

    }



    //关注处理

    public function followHandle($object){
        $openid = (string)$object->FromUserName;
        $unionid = (string)$object->EventKey;
        if(isset($unionid) && !empty($unionid)){
            $idarr_str = explode("_", $unionid);
            //$idarr = substr($unionid, 8);
            $idarr=$idarr_str ? $idarr_str[1] : 0;
            $id = $this->getUserInfo($openid, $idarr, 0);
            if(is_array($id)){   
                if($id['code'] == 1){
                    $content = "欢迎关注星月棋牌 \n欢迎加入".$id['pname']."的战队\n游戏ID:".$id['id']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a> ";
                }else{
                    $content = "欢迎关注星月棋牌 \n欢迎加入".$id['pname']."的战队\n游戏ID:".$id['id']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a> ";
                }             

            }else{
                $content = "欢迎关注星月棋牌,网络好像掉线了,请重新操作！";
            }   

        }else{
            $id = $this->getUserInfo($openid, '', 1);
            if(is_array($id)){
                if($id['code'] == 1){
                    $content = "欢迎关注星月棋牌 \n欢迎加入".$id['pname']."的战队\n游戏ID:".$id['id']."\n初始密码:".$id['pwd']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a>";
                }else{
                    $content = "欢迎关注星月棋牌 \n欢迎加入".$id['pname']."的战队\n游戏ID:".$id['id']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a> ";
                }
            }else{
                $content = "欢迎关注星月棋牌,网络好像掉线了,请重新操作。";
            }   
        }
        return $content;
    }



    //扫描二维码处理(已关注)

    public function scanningHandle($object){

        $openid = (string)$object->FromUserName;

        $unionid = (string)$object->EventKey;

        if(isset($unionid) && !empty($unionid)){

            $id = $this->getUserInfo($openid, $unionid, 0);

            if(is_array($id)){

                if($id['code'] == 1){

                    $content = "您已关注星月棋牌\n欢迎加入".$id['pname']."的战队\n游戏ID:".$id['id']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a> ";

                }else{

                    

                    $content = "您已关注星月棋牌\n欢迎加入".$id['pname']."的战队\n游戏ID:".$id['id']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a> ";

                }

            }else{

                $content = "您已关注星月棋牌\n欢迎加入".$id['pname']."的战队\n游戏ID:".$id['id']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a>";

            }

        }else{

            $id = $this->getUserInfo($openid, '', 1);

            if(is_array($id)){

                if($id['code'] == 1){

                    $content = "您已关注星月棋牌 \n欢迎加入".$id['pname']."的战队\n游戏ID:".$id['id']."\n初始密码:".$id['pwd']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a>";

                }else{

                    $content = "您已关注星月棋牌 \n欢迎加入".$id['pname']."的战队\n游戏ID:".$id['id']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a> ";

                }

            }else{

                $content = "欢迎关注星月棋牌,网络好像掉线了,请重新操作。";

            }

            //$content = '您已关注牛星雨'."\n<a href='".$this->$downUrl."?".rand()."'>点击下载游戏</a> ";

        }
        return $content;

    }



    //接收文本消息

    public function receiveText($object)

    {

        switch ($object->Content)

        {

          case "文本":

            $content = "这是个文本消息";

            break;

          case "图文": break;

          case "单图文":

            $content = array();

            $content[] = array("Title"=>"单图文标题",  "Description"=>"单图文内容", "PicUrl"=>"http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");

            break;

          case "多图文":

            $content = array();

            $content[] = array("Title"=>"多图文1标题", "Description"=>"", "PicUrl"=>"http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");

            $content[] = array("Title"=>"多图文2标题", "Description"=>"", "PicUrl"=>"http://d.hiphotos.bdimg.com/wisegame/pic/item/f3529822720e0cf3ac9f1ada0846f21fbe09aaa3.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");

            $content[] = array("Title"=>"多图文3标题", "Description"=>"", "PicUrl"=>"http://g.hiphotos.bdimg.com/wisegame/pic/item/18cb0a46f21fbe090d338acc6a600c338644adfd.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");

            break;

          case "音乐":

            $content = array("Title"=>"最炫民族风", "Description"=>"歌手：凤凰传奇", "MusicUrl"=>"http://121.199.4.61/music/zxmzf.mp3", "HQMusicUrl"=>"http://121.199.4.61/music/zxmzf.mp3");

            break;

          default:

            $content = date("Y-m-d H:i:s",time());

            break;

        }

        if(is_array($content)){

          if (isset($content[0]['PicUrl'])){

            $result = $this->transmitNews($object, $content);

          }else if (isset($content['MusicUrl'])){

            $result = $this->transmitMusic($object, $content);

          }

        }else{

          $result = $this->transmitText($object, $content);

        }

        return $result;

    }

    //接收事件消息

    public function receiveEvent($object)

    {

        $content = "";

        switch ($object->Event)

        {

          case "subscribe":
             $content = '欢迎加入'; 
           // $content = $this->followHandle($object);

            break;

          case "unsubscribe":

            $content = "取消关注";

            break;

          case "SCAN":

            $content = '您已关注牛星雨'; 

           // $content = $this->scanningHandle($object); 

            break;

          case "CLICK":

            switch ($object->EventKey)

            {

              case "COMPANY":

                $content = "。";

                break;

              default:

                $content = "点击菜单：".$object->EventKey;

                break;

            }

            break;

          case "LOCATION":

            $content = "上传位置：纬度 ".$object->Latitude.";经度 ".$object->Longitude;

            break;

          case "VIEW":

            $content = "跳转链接 ".$object->EventKey;

            break;

          default:

            $content = "receive a new event: ".$object->Event;

            break;

        }

        $result = $this->transmitText($object, $content);

        return $result;

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

    //curl post方式获取接口数据

    public function postResult($data, $url){

        $curl = curl_init();

        curl_setopt($curl,CURLOPT_URL,$url);

        curl_setopt($curl,CURLOPT_POST,1);

        curl_setopt($curl,CURLOPT_POSTFIELDS,$data);

        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($curl);

        curl_close($curl);

        return json_decode($result, true);

    }

    //curl get获取接口数据

    public function getResult($url){

        $curl = curl_init();

        curl_setopt($curl,CURLOPT_URL,$url); //请求地址；

        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1); //返回值；

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($curl); //执行；

        curl_close($curl); //关闭URL请求

        return json_decode($result);

    }



    //创建access_token文件,存放过期时间

    public function createFile($filename, $content){

        $fp = fopen($filename, "w+");

        fwrite($fp, "" . $content);

        fclose($fp);

    }



    public function getContents($url){

        $result = file_get_contents($url);

        return json_decode($result);

    }

    public function receiveImage($object)

    {

        $content = array("MediaId"=>$object->MediaId);

        $result = $this->transmitImage($object, $content);

        $abc = json_encode((string)$object->MediaId);

        $this->operationSql($abc);

        return $result;

    }

    public function receiveLocation($object)

    {

        $content = "你发送的是位置，纬度为：".$object->Location_X."；经度为：".$object->Location_Y."；缩放级别为：".$object->Scale."；位置为：".$object->Label;

        $result = $this->transmitText($object, $content);

        return $result;

    }

    public function receiveVoice($object)

    {

        if (isset($object->Recognition) && !empty($object->Recognition)){

          $content = "你刚才说的是：".$object->Recognition;

          $result = $this->transmitText($object, $content);

        }else{

          $content = array("MediaId"=>$object->MediaId);

          $result = $this->transmitVoice($object, $content);

        }

        return $result;

    }

    public function receiveVideo($object)

    {

        $content = array("MediaId"=>$object->MediaId, "ThumbMediaId"=>$object->ThumbMediaId, "Title"=>"", "Description"=>"");

        $result = $this->transmitVideo($object, $content);

        return $result;

    }

    public function receiveLink($object)

    {

        $content = "你发送的是链接，标题为：".$object->Title."；内容为：".$object->Description."；链接地址为：".$object->Url;

        $result = $this->transmitText($object, $content);

        return $result;

    }

    public function transmitText($object, $content)

    {

        if(isset($content) && !empty($content)){

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

    public function transmitImage($object, $imageArray)

    {

        $tpl = "<xml>

            <ToUserName>< ![CDATA[toUser] ]></ToUserName>

            <FromUserName>< ![CDATA[fromUser] ]></FromUserName>

            <CreateTime>12345678</CreateTime>

            <MsgType>< ![CDATA[image] ]></MsgType>

            <Image><MediaId>< ![CDATA[media_id] ]></MediaId></Image>

            </xml>";

        $itemTpl = "<Image>

            <MediaId><![CDATA[%s]]></MediaId>

            </Image>";

        $item_str = sprintf($itemTpl, $imageArray['MediaId']);

        $textTpl = "<xml>

            <ToUserName><![CDATA[%s]]></ToUserName>

            <FromUserName><![CDATA[%s]]></FromUserName>

            <CreateTime>%s</CreateTime>

            <MsgType><![CDATA[image]]></MsgType>

            $item_str

            </xml>";

        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time());

        return $result;

    }

    public function transmitVoice($object, $voiceArray)

    {

        $itemTpl = "<Voice>

            <MediaId><![CDATA[%s]]></MediaId>

            </Voice>";

        $item_str = sprintf($itemTpl, $voiceArray['MediaId']);

        $textTpl = "<xml>

            <ToUserName><![CDATA[%s]]></ToUserName>

            <FromUserName><![CDATA[%s]]></FromUserName>

            <CreateTime>%s</CreateTime>

            <MsgType><![CDATA[voice]]></MsgType>

            $item_str

            </xml>";

        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time());

        return $result;

    }

    public function transmitVideo($object, $videoArray)

    {

        $itemTpl = "<Video>

            <MediaId><![CDATA[%s]]></MediaId>

            <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>

            <Title><![CDATA[%s]]></Title>

            <Description><![CDATA[%s]]></Description>

            </Video>";

        $item_str = sprintf($itemTpl, $videoArray['MediaId'], $videoArray['ThumbMediaId'], $videoArray['Title'], $videoArray['Description']);

        $textTpl = "<xml>

            <ToUserName><![CDATA[%s]]></ToUserName>

            <FromUserName><![CDATA[%s]]></FromUserName>

            <CreateTime>%s</CreateTime>

            <MsgType><![CDATA[video]]></MsgType>

            $item_str

            </xml>";

        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time());

        return $result;

    }

    public function transmitNews($object, $newsArray)

    {

        if(!is_array($newsArray)){

          return;

        }

        $itemTpl = "    <item>

            <Title><![CDATA[%s]]></Title>

            <Description><![CDATA[%s]]></Description>

            <PicUrl><![CDATA[%s]]></PicUrl>

            <Url><![CDATA[%s]]></Url>

            </item>";

        $item_str = "";

        foreach ($newsArray as $item){

          $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);

        }

        $newsTpl = "<xml>

            <ToUserName><![CDATA[%s]]></ToUserName>

            <FromUserName><![CDATA[%s]]></FromUserName>

            <CreateTime>%s</CreateTime>

            <MsgType><![CDATA[news]]></MsgType>

            <Content><![CDATA[]]></Content>

            <ArticleCount>%s</ArticleCount>

            <Articles>

            $item_str</Articles>

            </xml>";

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));

        return $result;

    }

    public function transmitMusic($object, $musicArray)

    {

        $itemTpl = "<Music>

            <Title><![CDATA[%s]]></Title>

            <Description><![CDATA[%s]]></Description>

            <MusicUrl><![CDATA[%s]]></MusicUrl>

            <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>

            </Music>";

        $item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);

        $textTpl = "<xml>

            <ToUserName><![CDATA[%s]]></ToUserName>

            <FromUserName><![CDATA[%s]]></FromUserName>

            <CreateTime>%s</CreateTime>

            <MsgType><![CDATA[music]]></MsgType>

            $item_str

            </xml>";

        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time());

        return $result;

    }

    public function logger($log_content)

    {

        if(isset($_SERVER['HTTP_APPNAME'])){   //SAE

          sae_set_display_errors(false);

          sae_debug($log_content);

          sae_set_display_errors(true);

        }else if($_SERVER['REMOTE_ADDR'] != "127.0.0.1"){ //LOCAL

          $max_size = 10000;

          $log_filename = "log.xml";

          if(file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)){unlink($log_filename);}

          file_put_contents($log_filename, date('H:i:s')." ".$log_content."\r\n", FILE_APPEND);

        }

    }

}
