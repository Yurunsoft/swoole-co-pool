<?php
namespace Yurun\Swoole\Coroutine;

use Yurun\Swoole\CoPool\CoBatch;

/**
 * 协程批量执行，并获取执行结果
 * 
 * @param array $taskCallables 任务回调列表
 * @param float|null $timeout 超时时间，为 -1 则不限时
 * @param int|null $limit 限制并发协程数量，为 -1 则不限制
 * @return array
 */
function batch(array $taskCallables, ?float $timeout = -1, ?int $limit = -1): array
{
    $coBatch = new CoBatch($taskCallables);
    return $coBatch->exec($timeout, $limit);
}
