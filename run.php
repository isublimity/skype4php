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
    $chart=$skype->getChats();

    echo "send message\n";
    foreach ($chart as $name=>$tmp)
    $skype->sendMessage($name,'<pre>'."\nPre code :php bot: \n".date('Y-m-d H:i:s')."\n\n".'</pre>');
}

