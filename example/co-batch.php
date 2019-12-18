<?php

use Yurun\Swoole\CoPool\CoBatch;

require dirname(__DIR__) . '/vendor/autoload.php';

go(function(){
    $batch = new CoBatch([
        function(){
            return 'imi';
        },
        'a' =>  function(){
            return 'niu';
        },
        'b' =>  function(){
            return 'bi';
        },
    ]);
    $results = $batch->exec();
    var_dump($results);
});
