<?php

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/ali_msg.php";
require_once('log/Logger.php');

date_default_timezone_set('Asia/Shanghai');
ini_set('display_errors', TRUE);
set_time_limit(0);

Logger::configure('conf.xml');

$runner = new ForeverMsssager;
$runner->run();


class ForeverMsssager
{

    public $m_log;
    function __construct()
    {
        $this->m_log = Logger::getLogger('myLogger');
    }

    public function log()
    {
        return $this->m_log;
    }

    public function __call($name, $arg)
    {
        if (method_exists($this->m_log, $name))
        {
            call_user_func_array(array($this->m_log, $name), $arg);
        }
    }

    public function run()
    {
        $db = (new MongoDB\Client)->scal_db;
        $users = $db->users;
        $items = $db->items;
        $reglist = $db->reglist;
        $auth = new Auth();

        while (true)
        {
            $alreadyInfo = [];
            $regs = $reglist->find([]);
            foreach ($regs as $reg)
            {
                //'$this->trace("reg =" . var_export($reg, true));
                $this->trace("reg =" . $reg['_id']);
                $this->trace("process usr {$reg['user_name']} rid {$reg['RecordID']}\n");
                $id = $reg['RecordID'];
                $user_name = $reg['user_name'];
                $user = $users->findOne(['user_name'=> $user_name]);
                if (empty($user) || empty($user['is_valid']))
                {
                    $this->trace("conintue, user {$reg['user_name']} not valid\n");
                    continue;
                }
                $item = $items->findOne(['RecordID' => $id]);
                if (empty($item) || $item['SockQty'] == 0)
                {
                    $this->trace("conintue, product {$item['ProductName']} qty is zero\n");
                    continue;
                }
                $auth->SendProductInfo($user['mobile'], $item['ServiceClass'], $item['SockQty']);
                $reglist->deleteOne(['_id' => $reg['_id']]);
                exit;
            }
            sleep(5);
        }
    }
}
