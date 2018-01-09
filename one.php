<?php
/**
 * Created by PhpStorm.
 * User: Wwj
 * Date: 2018/1/3
 * Time: 20:20
 */
header('Content-type:text');
define("TOKEN","jiang");
$wechatObj = new wechatCallbackapiTest();
if(!isset($_GET['echostr'])){
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
        $tmpArr = array($token,$timestamp,$nonce);
        //sort:本函数结束时数组单元将被从最低到最高重新安排 a-->z
        sort($tmpArr); //这个得单独拿出来
        //implode:把数组元素组合为一个字符串：如果没有产生，将直接拼接
        $tmpStr = implode( $tmpArr );
        //shal:一种加密
        $tmpStr = sha1( $tmpStr );
        if ( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    public function responseMsg()
    {
        $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
        if (!empty($postStr)){
            //simplexml_load_string() 函数把 XML 字符串载入对象中。
            $postObj = simplexml_load_string($postStr,'SimpleXMLElement',LIBXML_NOCDATA);
            $formUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
            $textTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                        </xml>";
            if(!empty($keyword)){
                $msgType = "text";
                $str_trans = mb_substr($keyword,0,2,"UTF-8");
                $str_valid = mb_substr($keyword,0,-2,"UTF-8");
                if($str_trans == '翻译' && !empty($str_valid)){
                    $word = mb_substr($keyword,2,202,"UTF-8");
                    //调用有道词典
                    $contentStr = $this->youdaoDic($word);

                }
            }

            if ($keyword == "你好" || $keyword == "")
            {
                $msgType = "text";
                $content = date("Y-m-d H:i:s",time());
                $result = sprintf($textTpl,$formUsername,$toUsername,$time,$msgType,$content);
                echo $result;
            }
        }else{
            echo "";
            exit;
        }
    }
}