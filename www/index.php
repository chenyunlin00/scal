<?php
date_default_timezone_set('Asia/Shanghai');
ini_set('display_errors', TRUE);
define("TOKEN", "MhxzKhl");//自己定义的token 就是个通信的私钥
include('log/Logger.php');


Logger::configure('conf.xml');

// Fetch a logger, it will inherit settings from the root logger
$log = Logger::getLogger('myLogger');

// Start logging
//$log->trace("My first message.");   // 

$wechatObj = new wechatCallbackapiTest();
//$wechatObj->valid();
$wechatObj->responseMsg();
class wechatCallbackapiTest
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }

    public function log()
    {
        return  Logger::getLogger('myLogger');

    }

    public function dft()
    {
        echo "雨宝我爱你哦";
        exit;
    }
    public function responseMsg()
    {
        //$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = file_get_contents("php://input");
        $this->log()->trace($postStr);
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
            $msgType = $postObj->MsgType;
            if ($msgType != "text")
            {
                dft();
            }
            $contentStr = '你好啊雨宝';
            if (preg_match("|cx\s*(.*)|i", $keyword, $m))
            {
                $contentStr = '查询' . $m[1];
            }
                
                
        
            $textTpl = "<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[%s]]></MsgType>
                <Content><![CDATA[%s]]></Content>
                <FuncFlag>0<FuncFlag>
                </xml>";
            if(!empty( $keyword ))
            {
                $msgType = "text";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }else{
                echo '你好啊雨宝';
            }
        }else {
            echo '你好啊宝';
            exit;
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token =TOKEN;
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
};
