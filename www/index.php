<?php
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/msg.php";
date_default_timezone_set('Asia/Shanghai');
ini_set('display_errors', TRUE);
define("TOKEN", "MhxzKhl");//自己定义的token 就是个通信的私钥
require_once('log/Logger.php');


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
    public $m_usrname;

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

    public function dft() {
        echo "雨宝我爱你哦";
        exit;
    }

    public function dy($keyword)
    {
        $db = (new MongoDB\Client)->scal_db;
        if(!preg_match('|^\d+$|', $keyword))
        {
            return '订阅ID号错误';
        }
        if (empty($this->m_usrname))
        {
            return '用户名错误';
        }
        $users_collection = $db->users;
        $items_collection = $db->items;
        $reglist = $db->reglist;
        $c = $items_collection->findOne(['RecordID' => intval($keyword)]);
        //$c = $items_collection->find(['RecordID' => intval(6562)]);
        if (empty($c) || !isset($c['SockQty']))
        if (empty($c))
        {
            return sprintf('订阅失败：未找到该商品 ID=%d', $keyword);
        }
        if ($c['SockQty'] > 0)
        {
            return sprintf('订阅失败：商品%s库存量为%d，请直接前往川航商城购买',
                $c['ProductName'], $c['SockQty']);
        }
        $productName = $c['ProductName'];
        $c = $users_collection->count(['user_name' => $this->m_usrname]);
        $insertOneResult = $reglist->insertOne([
                    'RecordID' => $c['RecordID'],
                    'user_name' => $this->m_usrname
                    ]);
        if ($insertOneResult->getInsertedCount() != 1)
        {
            return '订阅失败：内部错误';
        }
        if ($c == 0)
        {
            return sprintf('订阅%s成功!请输入您的手机号，我们将在商品到货后提醒您', $productName);
        }
        else
        {
            return sprintf('订阅%s成功!', $productName);
        }
        return sprintf('收到订阅请求用户名%s ID号%d', $this->m_usrname, $keyword);

    }

    public function cx($keyword)
    {

        $collection = (new MongoDB\Client)->scal_db->items;
        $c = $collection->find(['ProductName' => ['$regex' => preg_quote($keyword), '$options'=>'i']]);
        //$c = $collection->find(['ProductName' => $keyword]);
        //$this->log()->trace(var_export($c->toArray(), true));
        $ret = '';
        foreach ($c as $item)
        {
            $ret .=  sprintf("产品名:%s 库存量:%d ID号:%d\n", 
                        $item['ProductName'], $item['SockQty'],
                        $item['RecordID']);
        }
        return $ret;
    }


    public function getRandStr()
    {
        $ret = [];
        for ($i=0; $i<6; $i++)
        {
            $ret[] = rand(0, 9);
        }
        return implode($ret);
    }

    public function sendCode($num)
    {
        $auth = new Auth();
        $auth_ret = $auth->SendSmsCode($num);
        if (empty($auth_ret) || !isset($auth_ret['code']) ||
            $auth_ret['code'] != 200 || 
            !isset($auth_ret['obj']))
        {
            return FALSE;
        }
        return $auth_ret['obj'];
    }

    public function codeCheck($num)
    {
        $auth = new Auth();
        $db = (new MongoDB\Client)->scal_db;
        $users = $db->users;
        $c = $users->findOne(['user_name' => $this->m_usrname]);
        if (empty($c))
        {
            return '请发送您的手机号，获取验证码';
        }
        //if ($c['reg_code'] == $num)
        if ($auth->CheckSmsYzm($c['mobile'], $num))
        {
            $users->updateOne(
                ['user_name' => $this->m_usrname],
                ['$set' => ['is_valid' => TRUE]]
                );
            return sprintf('绑定手机号%d成功', $c['mobile']);
        }
        else
        {
            return sprintf('验证码错误,绑定手机号失败');
        }
    }

    public function regMob($num)
    {
        $nowtime = time();
        $db = (new MongoDB\Client)->scal_db;
        $users = $db->users;
        $c = $users->findOne(['user_name' => $this->m_usrname]);
        $sndtimes = 0;
        if (!empty($c))
        {
            if ($c['mobile'] == $num && $c['is_valid'] == TRUE)
            {
                return '您已使用该手机号注册过,不需再次注册';
            }
            if ($nowtime - $c['send_code_time'] < 60)
            {
                return '请于60秒后再次获取验证码';
            }
            $sndtimes = $c['send_times'];
            if ($sndtimes > 9)
            {
                return '获取验证码次数过多';
            }
        }
        $sndtimes++;
        $users->deleteMany(['user_name' => $this->m_usrname]);
        $code = $this->sendCode($num);
        if ($code === FALSE)
        {
            return '发送验证码失败，请重新输入手机号';
        }
        $users->insertOne([
                        'user_name' => $this->m_usrname,
                        'mobile' => $num,
                        'is_valid' => FALSE,
                        'reg_time' => time(),
                        'reg_code' => $code,
                        'send_code_time' => time(),
                        'send_times' => $sndtimes
                        ]);

        return sprintf('已发送验证码到%d,请收到验证码后将验证码发送给我', $num);
    }

    public function responseMsg()
    {
        //$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = file_get_contents("php://input");
        //$this->log()->trace($postStr);
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->m_usrname = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
            $msgType = $postObj->MsgType;
            if ($msgType != "text")
            {
                dft();
            }
            $contentStr = '你好啊雨宝';
            if (preg_match("/(cx|查询)\s*(.*)/i", $keyword, $m))
            {
                $contentStr = '查询' . $this->cx($m[2]);
            }
            else if (preg_match("/(dy|订阅)\s*(.*)/i", $keyword, $m))
            {
                $contentStr = '订阅' . $m[2] . $this->dy($m[2]);
            }
            else if (preg_match("/^1(3|4|5|7|8)\d{9}$/", $keyword, $m))
            {
                $contentStr = '注册手机号' . $this->regMob($m[0]);
            }
            else if(preg_match('/^\d{4}$/', $keyword, $m))
            {
                $contentStr = '验证码' . $this->codeCheck($m[0]);
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
                $resultStr = sprintf($textTpl, $this->m_usrname, $toUsername, $time, $msgType, $contentStr);
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
