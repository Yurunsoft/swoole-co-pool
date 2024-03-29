<?php

namespace Yurun\Swoole\Coroutine;

use Yurun\Swoole\CoPool\CoBatch;
use Yurun\Swoole\CoPool\CoBatchIterator;

/**
 * 协程批量执行，并获取执行结果.
 *
 * @param array      $taskCallables 任务回调列表
 * @param float|null $timeout       超时时间，为 -1 则不限时
 * @param int|null   $limit         限制并发协程数量，为 -1 则不限制
 */
function batch(array $taskCallables, ?float $timeout = -1, ?int $limit = -1, bool $throwException = false, ?array &$throws = null): array
{
    $result = CoBatch::__exec($taskCallables, $timeout, $limit, $throws);
    if ($throwException && $throws)
    {
        throw reset($throws);
    }

    return $result;
}

/**
 * 协程批量执行，并获取执行结果.
 *
 * @param array      $taskCallables 任务回调列表
 * @param float|null $timeout       超时时间，为 -1 则不限时
 * @param int|null   $limit         限制并发协程数量，为 -1 则不限制
 */
function batchIterator(iterable $taskCallables, ?float $timeout = -1, ?int $limit = -1): \Generator
{
    return CoBatchIterator::__exec($taskCallables, $timeout, $limit);
}

/**
 * 创建一个协程A，挂起当前协程等待A协程执行完毕，并返回A协程的返回值
 *
 * @param callable   $callable 任务回调列表
 * @param float|null $timeout  超时时间，为 -1 则不限时
 */
function goWait(callable $callable, ?float $timeout = -1, bool $throwException = false)
{
    $result = CoBatch::__exec([$callable], $timeout, -1, $throws)[0] ?? null;
    if ($throwException && $throws)
    {
        throw reset($throws);
    }

    return $result;
}
