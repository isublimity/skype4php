<?php
$config=include_once 'test1.config.php';
include_once 'skype4php.php';




//

$skype=new skype4php($config['username'],$config['password']);

$skype->login();
//



