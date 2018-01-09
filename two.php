<?php
/**
 * Created by PhpStorm.
 * User: wwj
 * Date: 2018/1/4
 * Time: 22:27
 */
//引入函数文件
require_once('include/weather/weather.php');
define("TOKEN","jiang");
$wechatObj = new wechatCallbackapiTest();
if (!isset($_GET['echostr'])){
    $wechatObj->responseMsg();
}else{
    $wechatObj->valid();
}
class wechatCallbackapiTest
{
    public function valid()
    {
        $echoStr = $_GET['echostr'];
        if ($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token,$timestamp.$nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature){
            return true;
        }else{
            return false;
        }
    }
    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr,'SimpleXMLElement',LIBXML_NOCDATA);
            //trim:清空两端的空格
            $RX_TYPE = trim($postObj->MsgType);
            //用户发送的消息类型判断
            switch ($RX_TYPE){
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text": //文本消息
                    $result = $this->receiveText($postObj);
                    break;
                case "image": //图片消息
                    $result = $this->receiveImage($postObj);
                    break;
                case "voice": //语音消息
                    $result = $this->receiveVoice($postObj);
                    break;
                case "video": //视频消息
                    $result = $this->receiveVideo($postObj);
                    break;
                case "location": //位置消息
                    $result = $this->receiveLocation($postObj);
                    break;
                case "link": //链接消息
                    $result = $this->receiveLink($postObj);
                    break;
                default:
                    $result = "UnKnow Msg Type:".$RX_TYPE;
                    break;
            }
            echo $result;
        }else{
            echo "";
            exit;
        }
    }
    //关注与取消关注：
    private function receiveEvent($object)
    {
        $content = "";
        switch ($object->Event)
        {
            case "subscribe":
                $content = "欢迎吴皖江,目前提供：翻译功能，天气预报!例如：翻译中国，深圳天气";
                break;
            case "unsubscribe":
                $content = "取消关注";
                break;
        }
        $result = $this->transmitText($object, $content);
        return $result;
    }

    //接收文本：
    private function receiveText($object)
    {
        //测试机
        $appid = "wxf13a3c4057c6e847";
        $appsecret = "786079bce2fe253146ef2dd5dc70fb13";
        $keyword = $object->Content;
        //翻译关键字
        $str_trans = mb_substr($keyword,0,2,"UTF-8");
        $str_valid = mb_substr($keyword,0,-2,"UTF-8");
        //天气关键字：
        $weather_key = mb_substr($keyword,-2,2,"UTF-8");
        $city_key = mb_substr($keyword,0,-2,"UTF-8");
        if($weather_key == '天气' && !empty($city_key) && $str_trans != '翻译'){
            $content = getWeatherInfo($city_key);
            $result = $this->transmitNews($object,$content);
            return $result;
        } elseif($str_trans == '翻译' && !empty($str_valid)){
            $word = mb_substr($keyword,2,202,"UTF-8");
            //调用有道词典
            $content = $this->youdaoApi($word);
        } elseif ($keyword == '看看'){
            $content = $this->getToken($appid,$appsecret);
            $result = $this->transmitText($object,$content);
            return $result;
        }elseif ($keyword == '菜单'){
            $content = $this->changeMenu($appid,$appsecret);
            $result = $this->transmitText($object,$content);
            return $result;
        }elseif ($keyword == '授权'){
            $openid = $object->FromUserName;
            $token = $this->getToken($appid,$appsecret);
            $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$token&openid=$openid&lang=zh_CN";
            $content = $this->https_request($url);
            $result = $this->transmitText($object,$content);
            return $result;
        }elseif ($keyword == '关注'){
            $redirect_uri = urlencode('http://1.wuwj2018.applinzi.com/index');
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxf13a3c4057c6e847&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect";
            header('Location:'.$url);
            $code = $_GET["code"];//预定义的 $_GET 变量用于收集来自 method="get" 的表单中的值。
            if (isset($_GET['code'])){//判断code是否存在
                $userinfo = getUserInfo($code);
                $xinxi = $userinfo['nickname'];//获取nickname对应的值,即用户名X
            }else{
                $userinfo =  "NO CODE";
            }
            $content = $userinfo;
            $result = $this->transmitText($object,$content);
            return $result;

        }else{
            $content = "你发送的文本，内容为：".$object->Content;
        }
        $result = $this->transmitText($object,$content);
        return $result;
    }
    //接收图片：
    private function receiveImage($object)
    {
        $content = "你发送的图片，地址为：".$object->PicUrl;
        $result = $this->transmitText($object,$content);
        return $result;
    }
    //接收语音：
    private function receiveVoice($object)
    {
        $content = "你发送的语音，媒体ID为：".$object->MediaId;
        $result = $this->transmitText($object,$content);
        return $result;
    }
    //接收视频：
    private function receiveVideo($object)
    {
        $content = "你发送的视频，媒体ID为：".$object->MediaId;
        $result = $this->transmitText($object,$content);
        return $result;
    }
    //接收位置：
    private function receiveLocation($object)
    {
        $content = "你发送的是位置,纬度为：".$object->Location_X.";经度为：".$object->Location_Y.";缩放级别为:".$object->Scale.";位置为：".$object->Label;
        $result = $this->transmitText($object,$content);
        return $result;
    }
    //接收链接：
    private function receiveLink($object)
    {
        $content = "你发送的链接，标题为：".$object->Title.";内容为：".$object->Description.";链接地址为：".$object->Url;
        $result = $this->transmitText($object,$content);
        return $result;
    }
    //回复文本消息：
    private function transmitText($object,$content)
    {
        $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>    
                        <MsgType><![CDATA[text]]></MsgType>    
                        <Content><![CDATA[%s]]></Content>    
                   </xml>";
        $result = sprintf($textTpl,$object->FromUserName,$object->ToUserName,time(),$content);
        return $result;
    }
    //回复图文消息：
    private function transmitNews($object, $arr_item)
    {
        if(!is_array($arr_item))
            return;
        $itemTpl = "<item>
                        <Title><![CDATA[%s]]></Title>
                        <Description><![CDATA[%s]]></Description>
                        <PicUrl><![CDATA[%s]]></PicUrl>
                        <Url><![CDATA[%s]]></Url>
                     </item>";
        $item_str = "";
        foreach ($arr_item as $item)
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        $newsTpl = "<xml>
                              <ToUserName><![CDATA[%s]]></ToUserName>
                              <FromUserName><![CDATA[%s]]></FromUserName>
                              <CreateTime>%s</CreateTime>
                              <MsgType><![CDATA[news]]></MsgType>
                              <Content><![CDATA[]]></Content>
                              <ArticleCount>%s</ArticleCount>
                              <Articles>$item_str</Articles>
                           </xml>";
        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($arr_item));
        return $result;
    }
    //有道接口
    public function youdaoApi($word)
    {
        $keyfrom = "zhuojin";  	//申请APIKEY时所填表的网站名称的内容
        $apikey = "304804921";    //从有道申请的APIKEY
        $url_youdao = 'http://fanyi.youdao.com/fanyiapi.do?keyfrom='.$keyfrom.'&key='.$apikey.'&type=data&doctype=json&version=1.1&q='.$word;
        $jsonStyle = file_get_contents($url_youdao);
        $result = json_decode($jsonStyle,true);
        $errorCode = $result['errorCode'];
        $trans = '';
        if(isset($errorCode)){
            switch ($errorCode){
                case 0:
                    $trans = $result['translation']['0'];
                    break;
                case 20:
                    $trans = '要翻译的文本过长';
                    break;
                case 30:
                    $trans = '无法进行有效的翻译';
                    break;
                case 40:
                    $trans = '不支持的语言类型';
                    break;
                case 50:
                    $trans = '无效的key';
                    break;
                default:
                    $trans = '出现异常';
                    break;
            }
        }
        return $trans;
    }
    public function getToken($appid,$appsecret)
    {

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        $jsoninfo = json_decode($output, true);
        $access_token = $jsoninfo["access_token"];
        return $access_token;
    }

    public function https_request($url,$data = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    public function changeMenu($appid,$appsecret)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $output = $this->https_request($url);
        $jsoninfo = json_decode($output, true);
        $access_token = $jsoninfo["access_token"];
        $jsonmenu = '{
      "button":[
      {
            "name":"天气预报",
           "sub_button":[
            {
               "type":"click",
               "name":"北京天气",
               "key":"天气北京"
            },
            {
               "type":"click",
               "name":"上海天气",
               "key":"天气上海"
            },
            {
               "type":"click",
               "name":"广州天气",
               "key":"天气广州"
            },
            {
               "type":"click",
               "name":"深圳天气",
               "key":"天气深圳"
            },
            {
                "type":"view",
                "name":"本地天气",
                "url":"http://m.hao123.com/a/tianqi"
            }]
      

       },
       {
           "name":"吴皖江",
           "sub_button":[
            {
               "type":"click",
               "name":"公司简介",
               "key":"company"
            },
            {
               "type":"click",
               "name":"趣味游戏",
               "key":"游戏"
            },
            {
                "type":"click",
                "name":"讲个笑话",
                "key":"笑话"
            }]
       

       }]
 }';
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
        $result = $this->https_request($url, $jsonmenu);
        return $result;
    }
    public function getUserInfo($code)
    {
        //测试机
        $appid = "wxf13a3c4057c6e847";
        $appsecret = "786079bce2fe253146ef2dd5dc70fb13";

        //Get access_token
        $access_token_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecret&code=$code&grant_type=authorization_code";
        $access_token_json = https_request($access_token_url);//自定义函数
        $access_token_array = json_decode($access_token_json,true);//对 JSON 格式的字符串进行解码，转换为 PHP 变量，自带函数
        //获取access_token
        $access_token = $access_token_array['access_token'];//获取access_token对应的值
        //获取openid
        $openid = $access_token_array['openid'];//获取openid对应的值

        //Get user info
        $userinfo_url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid";
        $userinfo_json = $this->https_request($userinfo_url);
        $userinfo_array = json_decode($userinfo_json,ture);
        return $userinfo_array;
    }
}
