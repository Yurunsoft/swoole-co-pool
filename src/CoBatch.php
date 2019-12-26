<?php
namespace Yurun\Swoole\CoPool;

use Swoole\Coroutine\Channel;

/**
 * 协程批量执行器
 */
class CoBatch
{
    /**
     * 任务回调列表
     *
     * @var callable[]
     */
    private $taskCallables;

    /**
     * 超时时间，为 -1 则不限时
     *
     * @var float|null
     */
    private $timeout;

    /**
     * 限制并发协程数量，为 -1 则不限制
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
     * 执行并获取执行结果
     *
     * @param float|null $timeout 超时时间，为 -1 则不限时
     * @param int|null $limit 限制并发协程数量，为 -1 则不限制
     * @return array
     */
    public function exec(?float $timeout = null, ?int $limit = null): array
    {
        if(null === $timeout)
        {
            $timeout = $this->timeout ?? -1;
        }
        if(null === $limit)
        {
            $limit = $this->limit ?? -1;
        }
        $channel = new Channel(1);
        $taskCount = count($this->taskCallables);
        $count = 0;
        $results = [];
        $running = true;
        if(-1 === $limit)
        {
            foreach($this->taskCallables as $key => $callable)
            {
                $results[$key] = null;
                go(function() use($key, $callable, $channel){
                    $channel->push([
                        'key'       =>  $key,
                        'result'    =>  $callable(),
                    ]);
                });
            }
        }
        else
        {
            foreach($this->taskCallables as $key => $callable)
            {
                $results[$key] = null;
            }
            reset($this->taskCallables);
            for($i = 0; $i < $limit; ++$i)
            {
                go(function() use($channel, &$running){
                    while($running && $callable = current($this->taskCallables))
                    {
                        $key = key($this->taskCallables);
                        next($this->taskCallables);
                        $channel->push([
                            'key'       =>  $key,
                            'result'    =>  $callable(),
                        ]);
                    }
                });
            }
        }
        $leftTimeout = (-1.0 === $timeout ? null : $timeout);
        while($count < $taskCount)
        {
            $beginTime = microtime(true);
            $result = $channel->pop($leftTimeout);
            $endTime = microtime(true);
            if(false === $result)
            {
                break; // 超时
            }
            if(null !== $leftTimeout)
            {
                $leftTimeout -= ($endTime - $beginTime);
                if($leftTimeout <= 0)
                {
                    break; // 剩余超时时间不足
                }
            }
            ++$count;
            $results[$result['key']] = $result['result'];
        }
        $running = false;
        return $results;
    }

}
