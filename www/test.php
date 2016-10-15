<?php
require_once(__DIR__. "/ali_msg.php");
$auth = new Auth();
//$auth->SendSmsCode("18080401561", "9867");
$auth->SendProductInfo("18080401561", "最小版kindle", "9867");
