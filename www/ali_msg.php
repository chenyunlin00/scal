<?php

require_once __DIR__ . "/tb_sdk/TopSdk.php";
require_once('log/Logger.php');
require_once(__DIR__ . '/passwd.php');

Logger::configure('conf.xml');

class Auth
{

    public function log()
    {

        return  Logger::getLogger('myLogger');
    }

    public function SendSmsCode($mobile = "", $code = "")
    {
        $c = new TopClient;
        $c->appkey = WORK_CONF::TB_KEY;
        $c->secretKey = WORK_CONF::TB_SEC;
        $req = new AlibabaAliqinFcSmsNumSendRequest;
        $req->setExtend("123456");
        $req->setSmsType("normal");
        $req->setSmsFreeSignName("飞友资讯");
        $req->setSmsParam("{\"code\":\"$code\"}");
        $req->setRecNum($mobile);
        $req->setSmsTemplateCode("SMS_18210056");
        $resp = $c->execute($req);
        $this->log()->trace("send code $code to $mobile return " . var_export($resp, true));
        if (isset($resp->result) && $resp->result->err_code == '0')
        {
            return TRUE;
        }
        return FALSE;
    }

    public function SendProductInfo($mobile, $productName, $productQty)
    {
        $c = new TopClient;
        $c->appkey = WORK_CONF::TB_KEY;
        $c->secretKey = WORK_CONF::TB_SEC;
        $req = new AlibabaAliqinFcSmsNumSendRequest;
        $req->setExtend("123456");
        $req->setSmsType("normal");
        $req->setSmsFreeSignName("飞友资讯");
        $req->setSmsParam("{\"product_name\":\"$productName\","
                . "\"product_qty\":\"$productQty\"}");
        $req->setRecNum($mobile);
        $req->setSmsTemplateCode("SMS_18310091");
        $resp = $c->execute($req);
        $this->log()->trace("send product $productName qty $productQty to $mobile return " . var_export($resp, true));
        if (isset($resp->result) && $resp->result->err_code == '0')
        {
            return TRUE;
        }
        return FALSE;
    }

};
