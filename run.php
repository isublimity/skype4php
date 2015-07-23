<?php
$config=include_once 'test1.config.php';
include_once 'skype4php.php';




//

$skype=new skype4php($config['username'],$config['password'],'/tmp/');

$skype->login();
//

if ($skype->ping())
{
    echo "Ping ok!\n";
    $skype->getChats();
}

