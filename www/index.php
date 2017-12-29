<?php
require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/ali_msg.php";
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
        //$this->log()->trace("username " . var_export($this->m_usrname, true));
        $regCount = $reglist->count(['user_name' => $this->m_usrname]);
        if ($regCount >= 5)
        {
            return '订阅失败:最多订阅5件商品信息，可输入清除订阅来删除所有订阅记录';
        }


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
        $sname = $c['ServiceClass'];
        $regCount = $reglist->count(['user_name' => $this->m_usrname, 'RecordID'=>intval($keyword)]);
        $userCount = $users_collection->count(['user_name' => $this->m_usrname]);
        if ($regCount > 0)
        {
            if ($userCount == 0)
            {
                return sprintf('订阅%s成功!请输入您的手机号，我们将在商品到货后提醒您', $productName);
            }
            else
            {
                return sprintf('订阅%s成功!', $productName);
            }
            return '订阅成功';
        }

        $insertOneResult = $reglist->insertOne([
                    'RecordID' => $c['RecordID'],
                    'user_name' => $this->m_usrname
                    ]);
        if ($insertOneResult->getInsertedCount() != 1)
        {
            return '订阅失败：内部错误';
        }
        if ($userCount == 0)
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
        $c = $collection->find(['ProductName' => ['$regex' => preg_quote($keyword), '$options'=>'i']], ['limit' => 10]);
        //$c = $collection->find(['ProductName' => $keyword]);
        //$this->log()->trace(var_export($c->toArray(), true));
        $ret = '';
        foreach ($c as $item)
        {
            $ret .=  sprintf("【产品名:%s 库存量:%d ID号:%d 需要里程:%d】\n", 
                        $item['ProductName'], $item['SockQty'],
                        $item['RecordID'], $item['RedeemMiles']);
        }
        if (empty($ret))
        {
            $ret = '失败，商城里没有此商品';
        }

        return $ret;
    }


    public function getRandStr()
    {
        $ret = [];
        for ($i=0; $i<4; $i++)
        {
            $ret[] = rand(0, 9);
        }
        return implode($ret);
    }

    public function sendCode($num)
    {
        $auth = new Auth();
        $code = $this->getRandStr();
        $auth_ret = $auth->SendSmsCode($num, $code);
        if ($auth_ret == TRUE)
        {
            return $code;
        }
        return FALSE;
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
        $checktimes = $c['check_code_times'];
        if ($checktimes > 10)
        {
            return sprintf("拒绝验证:验证错误次数太多");
        }
        if ($c['is_valid'] == TRUE)
        {
            return sprintf("您手机%s已绑定过，不需重复验证，若要解除绑定，请重新输入新手机号",
                $c['mobile']);
        }
        $checktimes++;
        if ($c['reg_code'] == $num)
        //if ($auth->CheckSmsYzm($c['mobile'], $num))
        {
            $users->updateOne(
                ['user_name' => $this->m_usrname],
                ['$set' => ['is_valid' => TRUE]]
                );
            return sprintf('绑定手机号%s成功', $c['mobile']);
        }
        else
        {
            $users->updateOne(
                ['user_name' => $this->m_usrname],
                ['$set' => ['check_code_times' => $checktimes]]
                );

            return sprintf('验证码错误,绑定手机号失败');
        }
    }

    public function clearReg()
    {
        $db = (new MongoDB\Client)->scal_db;
        $reglist = $db->reglist;
        $count = $reglist->count(['user_name' => $this->m_usrname]);
        $reglist->deleteMany(['user_name' => $this->m_usrname]);
        $this->log()->trace("clearReg user_name {$this->m_usrname} clear count {$count}");
        return "已清除{$count}条订阅信息";
    }

    public function myInfo()
    {
        $db = (new MongoDB\Client)->scal_db;
        $users = $db->users;
        $user = $users->findOne(['user_name' => $this->m_usrname]);
        if (empty($user))
        {
            return '未查到您的信息';
        }
        $ret = sprintf("是否注册成功：%s 绑定手机号 %s", 
            $user['is_valid']?'是':'否', $user['mobile']);
        return $ret;
    }

    public function listReg()
    {
        $db = (new MongoDB\Client)->scal_db;
        $reglist = $db->reglist;
        $items = $db->items;
        $regs = $reglist->find(['user_name' => $this->m_usrname]);
        $ret = "已订阅列表:";
        foreach ($regs as $reg)
        {
            $id = $reg['RecordID'];
            $item = $items->findOne(['RecordID' => $reg['RecordID']]);
            if (!empty($item))
            {
                $ret .= "\n产品名{$item['ProductName']}";
            }

        }
        return $ret;
    }

    public function regMob($num)
    {
        $nowtime = time();
        $db = (new MongoDB\Client)->scal_db;
        $users = $db->users;
        $c = $users->findOne(['user_name' => $this->m_usrname]);
        $sndtimes = 0;
        $checktimes = 0;
        if (!empty($c))
        {
            $checktimes = $c['check_code_times'];
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
                        'send_times' => $sndtimes,
                        'check_code_times' => $checktimes,
                        ]);

        return sprintf('已发送验证码到%s,请收到验证码后将验证码发送给我', $num);
    }
    public function dft() {
        return 
<<<EOF
欢迎访问飞友资讯，我们提供对川航积分商城的货品到货提醒服务。
请按下面的例子回复消息给我们，进行相关操作：
1 回复消息：查询华为, 可查询名称中包含华为两个字的产品信息
2 回复消息：订阅6562, 可订阅ID号为6562的产品，当该产品到货时，我们会通过短信通知您
3 回复消息：13812345678，绑定您的手机号为13812345678，我们将向该手机发送验证码
4 回复消息：4363, 这四位数是您收到的验证码
5 回复消息：清除订阅,取消您订阅的所有商品到货消息
6 回复消息：查看订阅，查看您已订阅的消息
7 回复消息：我的信息，查看您的绑定信息
EOF;
    }

    public function responseMsg()
    {
        //$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = file_get_contents("php://input");
        //$this->log()->trace($postStr);
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->m_usrname = (string)$postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
            $msgType = $postObj->MsgType;

            $contentStr = $this->dft();
            if ($msgType == "text")
            {
                if (preg_match("/(cx|查询)\s*(.*)/i", $keyword, $m))
                {
                    $contentStr = '查询' . $this->cx($m[2]);
                }
                else if (preg_match("/^(dy|订阅)\s*(.*)/i", $keyword, $m))
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
                else if (preg_match('/^清除订阅$/', $keyword))
                {
                    $contentStr = $this->clearReg();
                }
                else if (preg_match('/^查看订阅$/', $keyword))
                {
                    $contentStr = $this->listReg();
                }
                else if(preg_match('/^我的信息$/', $keyword))
                {
                    $contentStr = $this->myInfo();
                }
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
