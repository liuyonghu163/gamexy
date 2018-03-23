<?php

define("TOKEN", "Niuxingyu2017");

$wechatObj = new wechatCallbackapiTest();

if(isset($_GET['echostr'])){

    $wechatObj->valid();

}else{

    $wechatObj->responseMsg();

}





class wechatCallbackapiTest

{



    private $appId     = 'wx6a422240c30416ce';

    private $appSecret = '547b40ab9eb75c1c86b34521be425f97';

    private $dbhost    = '47.104.105.93';

    private $dbname    = 'chessgame';

    private $dbuser    = 'root';

    private $dbpwd     = 'niuxingyu2017';

    private $md5str    = 'chess_';

    private $downUrl   = "http://xy.niuxingyu.com/download/";



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
       $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        //$postStr = file_get_contents("php://input");
        if (!empty($postStr)){

          //$this->logger("R ".$postStr);

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

         // $this->logger("T ".$result);

          echo $result;

        }else {

          echo "";

          exit;

        }

    }

    //获取用户信息以及UnionID 并存入数据库

    private function getUserInfo($openid, $unionid, $agent = 0){

        $accessToken = $this->operationFile();//获取access_token

        $getUserinfoUrl = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$accessToken."&openid=".$openid."&lang=zh_CN";

        $userInfoResult = $this->getResult($getUserinfoUrl);

        $unid = (string)$userInfoResult->unionid;

        $res = $this->selectMysqlNums($unid);//查询数据库里是否存在此用户

        if($res){

            $pid = $this->selectMysqlRows(trim($unionid));
            $pid = isset($pid) && !empty($pid) ? $pid : 0;
            $piduserinfo=$this->getPidname($pid);
            $url = $this->generateCode($userInfoResult->unionid);//生成带参数的二维码

            $userid = mt_rand(10000000, 99999999);

            $defaultpwd = '123456';

            $password = md5($this->md5str.$defaultpwd);
            if($userInfoResult->headimgurl){
                $headimgurl=$userInfoResult->headimgurl;
            }else{
                $headimgurl='http://xy.niuxingyu.com/uploads/headimg/headimg%20(0).jpg';
            }

            $userInfo = array(

                'pid'            => $pid,

                'uselessid'      => $userid,

                'unionid'        => $userInfoResult->unionid,

                'openid'         => $userInfoResult->openid,

                'nickname'       => $userInfoResult->nickname,

                'sex'            => $userInfoResult->sex,

                'headimgurl'     => $headimgurl,

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

                $resarr = array(

                    'code' => 1,

                    'id'   => $userid,

                    'pwd'  => $defaultpwd,
                    'pname'=>'星月棋牌',
                    'unid'=>$unid

                    );
                if($piduserinfo){
                             $resarr['pname']=$piduserinfo['nickname'];
                        }
                return $resarr;

            }else{

                return false;

            }

        }else{

            $con = mysql_connect($this->dbhost, $this->dbuser, $this->dbpwd);

            if (!$con){

              die('Could not connect: ' . mysql_error());

            }

            mysql_select_db($this->dbname, $con);

            $sql = "SELECT * FROM chess_user where unionid='".$unid."'";

            $result = mysql_query($sql);

            if($result){

                while($row = mysql_fetch_array($result)){
                    $userpid=$row;

                    $resarr = array(

                        'code' => 2,

                        'id'   => $row['uselessid'],

                        'pwd'  => '123456',
                         'pname'=>'星月棋牌',

                    );

                }  

            }else{

                $resarr = false;

            }

            mysql_close($con);
            $piduserinfo=$this->getPidname($userpid['pid']);
            if($piduserinfo){
                         $resarr['pname']=$piduserinfo['nickname'];
                    }
            return $resarr;

        }

    }



    //获取微信access_token

    private function getAccessToken(){

        $getAccessTokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appId."&secret=".$this->appSecret;

        $accessResult = $this->getResult($getAccessTokenUrl);

        return $accessResult->access_token;

    }



    //存储access_token到文件中

    private function operationFile(){

        $filename = __DIR__.'/admin/Application/Runtime/access_token.json';

        if(!file_exists($filename)){

            $accessToken = $this->getAccessToken();

            $time = time()+7200;

            $content = array(

                'access_token' => $accessToken,

                'expires'      => $time

            );

            $content = json_encode($content);

            $this->createFile($filename, $content);

            return $accessToken;

        }else{

            $filecontent = file_get_contents($filename, true);

            $filecontent = json_decode($filecontent, true);

            if(time() > $filecontent['expires']){

                $accessToken = $this->getAccessToken();

                $time = time()+7200;

                $content = array(

                    'access_token' => $accessToken,

                    'expires'      => $time

                );

                $content = json_encode($content);

                $this->createFile($filename, $content);

                return $accessToken;

            }else{

                return $filecontent['access_token'];

            }

        }

    }

    //查询条数

    private function selectMysqlNums($unionid){

        $con = mysql_connect($this->dbhost, $this->dbuser, $this->dbpwd);

        if (!$con){

            die('Could not connect: ' . mysql_error());

        }

        mysql_select_db($this->dbname, $con);

        $sql = "select * from chess_user where unionid='".$unionid."'";

        $result = mysql_query($sql);

        $num = mysql_num_rows($result);

        mysql_close($con);     

        if($num){

            return false;

        }else{

            return true;

        }   

    }

    //查询数据库

    private function selectMysqlRows($unionid){

        $con = mysql_connect($this->dbhost, $this->dbuser, $this->dbpwd);

        if (!$con){

            die('Could not connect: ' . mysql_error());

        }

        mysql_select_db($this->dbname, $con);

        $sql = "select * from chess_user where unionid='".$unionid."'";

        $result = mysql_query($sql);

        $data = '';

        while($row = mysql_fetch_array($result)){

            $data = $row['id'];

        }

        mysql_close($con);

        return $data;

    }

    //查询游戏ID

    private function selectUselessid($id){

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
      //查询游戏ID

    private function getPidname($pid){

        $con = mysql_connect($this->dbhost, $this->dbuser, $this->dbpwd);

        if (!$con){

            die('Could not connect: ' . mysql_error());

        }

        mysql_select_db($this->dbname, $con);

        $res = mysql_query("select * from chess_user where id=".$pid);

        if($res){

            while($row = mysql_fetch_array($res)){

                $uselessid = $row;

            }

            mysql_close($con);

            return $uselessid;

        }else{

            return false;

        }

    }

    //执行存储数据库

    private function operationMysql($data){

        $info = ''; $fild = '';

        foreach($data as $key => $val){

            $fild .= '`'.$key.'`,';

            $info .= "'".$val."',";

        }

        $fild = substr($fild, 0, -1);

        $info = substr($info, 0, -1);

        $con = mysql_connect($this->dbhost, $this->dbuser, $this->dbpwd);

        if (!$con){

          die('Could not connect: ' . mysql_error());

        }

        mysql_select_db($this->dbname, $con);

        $sql = "INSERT INTO chess_user (".$fild.") VALUES (".$info.")";

        $result = mysql_query($sql);

        $resid = mysql_insert_id();

        mysql_close($con);

        if($result){

            return $resid;

        }else{

            return false;

        }

    }

    //生成参数的二维码

    private function generateCode($unionid){

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
        $datatime=date('Y-m-d H:i:s',time());
        if(isset($unionid) && !empty($unionid)){
            $idarr = substr($unionid, 8);
            $id = $this->getUserInfo($openid, $idarr, 0);
            if(is_array($id)){   
                if($id['code'] == 1){
                    $content = "星月棋牌，欢迎您的加入！\n您的游戏ID为".$id['id']."\n您已经加入:".$id['pname']." 的阵容，无需重复加入！\n加入时间".$datatime."\n热门活动及游戏资讯请添加客服微信：xyqp1006\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a> ";
                }else{
                    $content = "星月棋牌，欢迎您的加入！\n您的游戏ID为".$id['id']."\n您已经加入:".$id['pname']." 的阵容，无需重复加入！\n加入时间".$datatime."\n热门活动及游戏资讯请添加客服微信：xyqp1006\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a> ";
                }             

            }else{
                $content = "星月棋牌，欢迎您的加入！,网络好像掉线了,请重新操作！";
            }   

        }else{
            $id = $this->getUserInfo($openid, '', 1);
            if(is_array($id)){
                if($id['code'] == 1){
                   
                    $content = "星月棋牌，欢迎您的加入！\n您的游戏ID为".$id['id']."\n您已经加入:".$id['pname']." 的阵容，无需重复加入！\n加入时间".$datatime."\n热门活动及游戏资讯请添加客服微信：xyqp1006\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a> ";
                }else{
                    $content = "星月棋牌，欢迎您的加入！\n您的游戏ID为".$id['id']."\n您已经加入:".$id['pname']." 的阵容，无需重复加入！\n加入时间".$datatime."\n热门活动及游戏资讯请添加客服微信：xyqp1006\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a> ";
                }
            }else{
                $content = "星月棋牌，欢迎您的加入！,网络好像掉线了,请重新操作。";
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

                    $content = "您已关注星月棋牌\n欢迎加入:".$id['pname']." 的战队\n游戏ID:".$id['id']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a> ";

                }else{

                    

                    $content = "您已关注星月棋牌\n欢迎加入:".$id['pname']." 的战队\n游戏ID:".$id['id']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a> ";

                }

            }else{

                $content = "您已关注星月棋牌\n欢迎加入:".$id['pname']." 的战队\n游戏ID:".$id['id']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a>";

            }

        }else{

            $id = $this->getUserInfo($openid, '', 1);

            if(is_array($id)){

                if($id['code'] == 1){

                    $content = "您已关注星月棋牌 \n欢迎加入:".$id['pname']." 的战队\n游戏ID:".$id['id']."\n初始密码:".$id['pwd']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a>";

                }else{

                    $content = "您已关注星月棋牌 \n欢迎加入:".$id['pname']." 的战队\n游戏ID:".$id['id']."\n<a href='".$this->downUrl."?".rand()."'>点击下载游戏</a> ";

                }

            }else{

                $content = "欢迎关注星月棋牌,网络好像掉线了,请重新操作。";

            }

            //$content = '您已关注牛星雨'."\n<a href='".$this->$downUrl."?".rand()."'>点击下载游戏</a> ";

        }
        return $content;

    }



    //接收文本消息

    private function receiveText($object)

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

    private function receiveEvent($object)

    {

        $content = "";

        switch ($object->Event)

        {

          case "subscribe":

            $content = $this->followHandle($object);

            break;

          case "unsubscribe":

            $content = "取消关注";

            break;

          case "SCAN":

            //$content = '您已关注世纪棋牌'; 

            $content = $this->scanningHandle($object); 

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

    private function checkSignature()

    {

        $signature = $_GET["signature"];

        $timestamp = $_GET["timestamp"];

        $nonce = $_GET["nonce"];

        $token = TOKEN;

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

    private function postResult($data, $url){

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

    private function getResult($url){

        $curl = curl_init();

        curl_setopt($curl,CURLOPT_URL,$url); //请求地址；

        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1); //返回值；

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($curl); //执行；

        curl_close($curl); //关闭URL请求

        return json_decode($result);

    }



    //创建access_token文件,存放过期时间

    private function createFile($filename, $content){

        $fp = fopen($filename, "w+");

        fwrite($fp, "" . $content);

        fclose($fp);

    }



    private function getContents($url){

        $result = file_get_contents($url);

        return json_decode($result);

    }

    private function receiveImage($object)

    {

        $content = array("MediaId"=>$object->MediaId);

        $result = $this->transmitImage($object, $content);

        $abc = json_encode((string)$object->MediaId);

        $this->operationSql($abc);

        return $result;

    }

    private function receiveLocation($object)

    {

        $content = "你发送的是位置，纬度为：".$object->Location_X."；经度为：".$object->Location_Y."；缩放级别为：".$object->Scale."；位置为：".$object->Label;

        $result = $this->transmitText($object, $content);

        return $result;

    }

    private function receiveVoice($object)

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

    private function receiveVideo($object)

    {

        $content = array("MediaId"=>$object->MediaId, "ThumbMediaId"=>$object->ThumbMediaId, "Title"=>"", "Description"=>"");

        $result = $this->transmitVideo($object, $content);

        return $result;

    }

    private function receiveLink($object)

    {

        $content = "你发送的是链接，标题为：".$object->Title."；内容为：".$object->Description."；链接地址为：".$object->Url;

        $result = $this->transmitText($object, $content);

        return $result;

    }

    private function transmitText($object, $content)

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

    private function transmitImage($object, $imageArray)

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

    private function transmitVoice($object, $voiceArray)

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

    private function transmitVideo($object, $videoArray)

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

    private function transmitNews($object, $newsArray)

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

    private function transmitMusic($object, $musicArray)

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

    private function logger($log_content)

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

?>