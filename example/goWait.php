<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use function Yurun\Swoole\Coroutine\goWait;

go(function () {
    $result = goWait(function () {
        \Swoole\Coroutine::sleep(1);

        return 'wait result';
    });
    echo $result; // wait result
});
