<?php
namespace Yurun\Swoole\Coroutine;

use Yurun\Swoole\CoPool\CoBatch;

/**
 * 协程批量执行，并获取执行结果
 * 
 * @param array $taskCallables 任务回调列表
 * @param float|null $timeout 超时时间，为 null 则不限时
 */
function batch(array $taskCallables, ?float $timeout = null)
{
    $coBatch = new CoBatch($taskCallables);
    return $coBatch->exec($timeout);
}
