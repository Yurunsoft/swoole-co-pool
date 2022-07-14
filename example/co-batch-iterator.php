<?php

use Yurun\Swoole\CoPool\CoBatchIterator;

require dirname(__DIR__) . '/vendor/autoload.php';

$input = function ($size = 100) {
    while ($size--)
    {
        $random = mt_rand(100, 900);
        yield $size => function () use ($random) {
            // 模拟IO任务
            usleep($random * 10);

            return $random;
        };
    }
};

$timeout = -1; // 支持超时
$limit = 8;    // 限制同时工作协程数量
$batch = new CoBatchIterator($input(), $timeout, $limit);
$iter = $batch->exec();

foreach ($iter as $key => $value)
{
    if ($value > 500)
    {
        // 获得符合要求的结果，执行相应的代码逻辑并中断循环
        // 业务代码...

        // 可以发送`false`到迭代器中止仍在运行的协程快速回收资源
        $iter->send(false);
        break;
    }
}

// 可以查看批量执行是以什么方式退出的
var_dump($iter->getReturn());
// CoBatchIterator::SUCCESS; // 全部任务完成
// CoBatchIterator::BREAK;   // 执行被主动中断
// CoBatchIterator::TIMEOUT; // 任务超时
// CoBatchIterator::UNKNOWN; // 未知原因（一般情况不会发生）