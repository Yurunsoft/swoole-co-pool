<?php

namespace Yurun\Swoole\CoPool;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\WaitGroup;

class CoBatchIterator
{
    /**
     * 任务回调列表.
     *
     * @var iterable<callable>
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

    const UNKNOWN = -1;
    const SUCCESS = 0;
    const TIMEOUT = 1;
    const BREAK = 2;

    public function __construct(iterable $taskCallables, ?float $timeout = -1, ?int $limit = -1)
    {
        $this->taskCallables = $taskCallables;
        $this->timeout = $timeout;
        $this->limit = $limit;
    }

    /**
     * 协程批量执行，并获取执行结果.
     *
     * @param iterable<callable> $taskCallables 任务回调列表
     * @param float|null         $timeout       超时时间，为 -1 则不限时
     * @param int|null           $limit         限制并发协程数量
     */
    public static function __exec(iterable $taskCallables, ?float $timeout = -1, int $limit = -1): \Generator
    {
        if (\is_array($taskCallables))
        {
            $taskCallables = new \ArrayIterator($taskCallables);
        }
        $channel = new Channel(1);
        $wg = new WaitGroup();
        $running = true;
        $taskTotal = 0;
        $sendTotal = 0;
        if (-1 === $limit)
        {
            foreach ($taskCallables as $key => $callable)
            {
                ++$taskTotal;
                $wg->add();
                Coroutine::create(function () use ($key, $callable, $channel, $wg, &$running) {
                    if ($running)
                    {
                        $channel->push([
                            'key'       => $key,
                            'result'    => $callable(),
                        ]);
                    }
                    $wg->done();
                });
            }
        }
        else
        {
            for ($i = 0; $i < $limit; ++$i)
            {
                $wg->add();
                Coroutine::create(function () use ($channel, &$taskCallables, $wg, &$running, &$taskTotal) {
                    while ($running && $taskCallables->valid())
                    {
                        ++$taskTotal;
                        $callable = $taskCallables->current();
                        $key = $taskCallables->key();
                        $taskCallables->next();
                        $channel->push([
                            'key'       => $key,
                            'result'    => $callable(),
                        ]);
                    }
                    $wg->done();
                });
            }
        }
        $isTimeout = false;
        Coroutine::create(function () use ($wg, $channel, $timeout, &$running, &$isTimeout) {
            $isTimeout = !$wg->wait($timeout ?? -1);
            $running = false;
            $channel->close();
        });
        while (true)
        {
            $result = $channel->pop(-1);
            if (false === $result)
            {
                break; // 超时 or 被关闭
            }
            ++$sendTotal;
            $continue = yield $result['key'] => $result['result'];
            if (false === $continue)
            {
                $channel->close();
                break;
            }
        }
        $running = false;
        if (isset($continue) && false === $continue)
        {
            return self::BREAK;
        }
        elseif ($isTimeout)
        {
            return self::TIMEOUT;
        }
        elseif ($taskTotal === $sendTotal)
        {
            return self::SUCCESS;
        }
        else
        {
            return self::UNKNOWN;
        }
    }

    /**
     * 执行并获取执行结果.
     *
     * @param float|null $timeout 超时时间，为 -1 则不限时
     * @param int|null   $limit   限制并发协程数量，为 -1 则不限制
     */
    public function exec(?float $timeout = null, ?int $limit = null): \Generator
    {
        if (null === $timeout)
        {
            $timeout = $this->timeout ?? -1;
        }
        if (null === $limit)
        {
            $limit = $this->limit ?? -1;
        }

        return static::__exec($this->taskCallables, $timeout, $limit);
    }
}
