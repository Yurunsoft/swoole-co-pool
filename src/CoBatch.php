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

    public function __construct(array $taskCallables)
    {
        $this->taskCallables = $taskCallables;        
    }

    /**
     * 执行并获取执行结果
     *
     * @param float|null $timeout 超时时间，为 null 则不限时
     * @return array
     */
    public function exec(?float $timeout = null)
    {
        $channel = new Channel(1);
        $taskCount = count($this->taskCallables);
        $count = 0;
        $results = [];
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
        $leftTimeout = $timeout;
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
        return $results;
    }

}
