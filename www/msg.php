<?php

require_once('log/Logger.php');
Logger::configure('conf.xml');

//$auth  = new Auth();
//$auth->SendSmsCode('18080401561');
//$auth->CheckSmsYzm('18080401561', '5052');
class Auth
{
    const APP_KEY = '7489ed4a9d00c923b4ff0440980e3e2b';
    const APP_SECRET = '8ef17bb74f46';

    public function log()
    {
        return  Logger::getLogger('myLogger');

    }

    public function SendSmsCode($mobile = ""){
        $appKey = self::APP_KEY;
        $appSecret = self::APP_SECRET;
        $nonce = '100';
        $curTime = time();
        $checkSum = sha1($appSecret . $nonce . $curTime);
        $data  = array(
                'mobile'=> $mobile,
                );
        $data = http_build_query($data);
        $opts = array (
                'http' => array(
                    'method' => 'POST',
                    'header' => array(
                        'Content-Type:application/x-www-form-urlencoded;charset=utf-8',
                        "AppKey:$appKey",
                        "Nonce:$nonce",
                        "CurTime:$curTime",
                        "CheckSum:$checkSum"
                        ),
                    'content' =>  $data
                    ),
                );
        $context = stream_context_create($opts);
        $html = file_get_contents("https://api.netease.im/sms/sendcode.action", false, $context);
        $this->log()->trace("send reg code to $mobile return" . $html);
        return json_decode($html, true);
    }

    public function CheckSmsYzm($mobile = "",$Code=""){
        $appKey = self::APP_KEY;
        $appSecret = self::APP_SECRET;
        $nonce = '100';
        $curTime = time();
        $checkSum = sha1($appSecret . $nonce . $curTime);
        $data  = array(
                'mobile'=> $mobile,
                'code' => $Code,
                );
        $data = http_build_query($data);
        $opts = array (
                'http' => array(
                    'method' => 'POST',
                    'header' => array(
                        'Content-Type:application/x-www-form-urlencoded;charset=utf-8',
                        "AppKey:$appKey",
                        "Nonce:$nonce",
                        "CurTime:$curTime",
                        "CheckSum:$checkSum"
                        ),
                    'content' =>  $data
                    ),
                );
        $context = stream_context_create($opts);
        $html = file_get_contents("https://api.netease.im/sms/verifycode.action", false, $context);
        $this->log()->trace("valid mobile $mobile code $Code return " . $html);
        $json_ret =  json_decode($html, true);
        if (empty($json_ret) || !isset($json_ret['code'])  || $json_ret['code'] != 200)
        {
            return FALSE;
        }
        return TRUE;
    }
}
