<?php

namespace Yurun\Swoole\CoPool;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Yurun\Swoole\CoPool\Exception\TimeoutException;

/**
 * 协程批量执行器.
 */
class CoBatch
{
    /**
     * 任务回调列表.
     *
     * @var callable[]
     */
    private $taskCallables;

    /**
     * 超时时间，为 -1 则不限时.
     *
     * @var float|null
     */
    private $timeout;

    /**
     * 限制并发协程数量，为 -1 则不限制.
     *
     * @var int|null
     */
    private $limit;

    public function __construct(array $taskCallables, ?float $timeout = -1, ?int $limit = -1)
    {
        $this->taskCallables = $taskCallables;
        $this->timeout = $timeout;
        $this->limit = $limit;
    }

    /**
     * 协程批量执行，并获取执行结果.
     *
     * @param array      $taskCallables 任务回调列表
     * @param float|null $timeout       超时时间，为 -1 则不限时
     * @param int|null   $limit         限制并发协程数量，为 -1 则不限制
     */
    public static function __exec(array $taskCallables, ?float $timeout = -1, ?int $limit = -1, ?array &$throws = null): array
    {
        $channel = new Channel(1);
        $taskCount = \count($taskCallables);
        $count = 0;
        $results = [];
        $running = true;
        $throws = [];
        if (-1 === $limit)
        {
            foreach ($taskCallables as $key => $callable)
            {
                Coroutine::create(function () use ($key, $callable, $channel, &$running) {
                    try
                    {
                        $result = $callable();
                        if ($running)
                        {
                            $channel->push([
                                'key'       => $key,
                                'result'    => $result,
                            ]);
                        }
                    }
                    catch (\Throwable $th)
                    {
                        if ($running)
                        {
                            $channel->push([
                                'key'   => $key,
                                'throw' => $th,
                            ]);
                        }
                    }
                });
            }
        }
        else
        {
            reset($taskCallables);
            for ($i = 0; $i < $limit; ++$i)
            {
                Coroutine::create(function () use ($channel, &$running, &$taskCallables) {
                    while ($running && $callable = current($taskCallables))
                    {
                        $key = key($taskCallables);
                        next($taskCallables);
                        try
                        {
                            $result = $callable();
                            if ($running)
                            {
                                $channel->push([
                                    'key'       => $key,
                                    'result'    => $result,
                                ]);
                            }
                        }
                        catch (\Throwable $th)
                        {
                            if ($running)
                            {
                                $channel->push([
                                    'key'   => $key,
                                    'throw' => $th,
                                ]);
                            }
                        }
                    }
                });
            }
        }
        $leftTimeout = (-1.0 === $timeout ? null : $timeout);
        $timeoutException = null;
        while ($count < $taskCount)
        {
            $beginTime = microtime(true);
            $result = $channel->pop(null === $leftTimeout ? -1 : $leftTimeout);
            $endTime = microtime(true);
            if (false === $result)
            {
                $timeoutException = new TimeoutException();
                break; // 超时
            }
            if (null !== $leftTimeout)
            {
                $leftTimeout -= ($endTime - $beginTime);
                if ($leftTimeout <= 0)
                {
                    $timeoutException = new TimeoutException();
                    break; // 剩余超时时间不足
                }
            }
            ++$count;
            if (isset($result['throw']))
            {
                $throws[$result['key']] = $result['throw'];
            }
            else
            {
                $results[$result['key']] = $result['result'];
            }
        }
        $running = false;
        $tmpResults = $results;
        $results = [];
        $tmpTaskCallables = $taskCallables;
        foreach ($tmpTaskCallables as $key => $callable)
        {
            if (\array_key_exists($key, $tmpResults))
            {
                $results[$key] = $tmpResults[$key];
            }
            else
            {
                $results[$key] = null;
                if ($timeoutException)
                {
                    $throws[$key] = $timeoutException;
                }
            }
        }

        return $results;
    }

    /**
     * 执行并获取执行结果.
     *
     * @param float|null $timeout 超时时间，为 -1 则不限时
     * @param int|null   $limit   限制并发协程数量，为 -1 则不限制
     */
    public function exec(?float $timeout = null, ?int $limit = null, ?array &$throws = null): array
    {
        if (null === $timeout)
        {
            $timeout = $this->timeout ?? -1;
        }
        if (null === $limit)
        {
            $limit = $this->limit ?? -1;
        }

        return static::__exec($this->taskCallables, $timeout, $limit, $throws);
    }
}
