<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Yurun\Swoole\CoPool\CoPool;
use Yurun\Swoole\CoPool\Interfaces\ICoTask;
use Yurun\Swoole\CoPool\Interfaces\ITaskParam;

Swoole\Runtime::enableCoroutine();

go(function(){
    $coCount = 10; // 同时工作协程数，可以改小改大，看一下执行速度
    $queueLength = 1024; // 队列长度
    $pool = new CoPool($coCount, $queueLength,
        // 定义任务匿名类，当然你也可以定义成普通类，传入完整类名
        new class implements ICoTask
        {
            /**
             * 执行任务
             *
             * @param ITaskParam $param
             * @return mixed
             */
            public function run(ITaskParam $param)
            {
                usleep(mt_rand(10, 100) * 1000); // 模拟I/O耗时挂起
                echo $param->getData(), PHP_EOL;
                return $param->getData(); // 返回的数据会传入结束回调中
            }

        }
    );
    // 运行协程池
    $pool->run();

    // 开始往协程池里推任务
    $count = 0;
    for($i = 1; $i <= 100; ++$i)
    {
        go(function() use($i, $pool, &$count){
            for($j = 0; $j < 100; ++$j)
            {
                $pool->addTask(++$count
                // 结束回调为非必须的
                , function(ITaskParam $param, $data){
                    echo 'finish:' . $data, PHP_EOL;
                }); // 我推
            }
        });
    }
});