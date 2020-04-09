<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Swoole\CoPool\ChannelContainer;

go(function(){
    $channelContainer = new ChannelContainer;

    $id = 'abc';
    $data = [
        'time'  =>  time(),
    ];

    go(function() use($id, $data, $channelContainer){
        echo 'Wait 3 seconds...', PHP_EOL;
        \Swoole\Coroutine::sleep(3);
        $channelContainer->push($id, $data);
    });
    var_dump($channelContainer->pop($id));

});
