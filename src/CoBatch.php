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
     * @return array
     */
    public function exec()
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
        while($count < $taskCount)
        {
            $result = $channel->pop();
            if(false === $result)
            {
                break;
            }
            ++$count;
            $results[$result['key']] = $result['result'];
        }
        return $results;
    }

}
