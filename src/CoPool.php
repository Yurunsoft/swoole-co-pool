<?php
namespace Yurun\Swoole\CoPool;

class CoPool
{
    /**
     * 工作协程数量
     *
     * @var int
     */
    private $coCount;

    /**
     * 队列最大长度
     *
     * @var int
     */
    private $queueLength;

    /**
     * 任务队列
     *
     * @var \Swoole\Coroutine\Channel
     */
    private $taskQueue;

    /**
     * 任务类
     *
     * @var string
     */
    private $taskClass;

    /**
     * 任务参数类名
     *
     * @var string
     */
    private $taskParamClass;

    /**
     * 是否正在运行
     *
     * @var boolean
     */
    private $running = false;

    /**
     * 构造方法
     *
     * @param int $coCount 工作协程数量
     * @param int $queueLength 队列最大长度
     * @param string $taskClass 任务类
     * @param string $taskParamClass 任务参数类名
     */
    public function __construct($coCount, $queueLength, $taskClass, $taskParamClass = TaskParam::class)
    {
        $this->coCount = $coCount;
        $this->taskClass = $taskClass;
        $this->taskParamClass = $taskParamClass;
    }

    /**
     * 运行协程池
     *
     * @return void
     */
    public function run()
    {
        if($this->taskQueue)
        {
            $this->taskQueue->close();
        }
        $this->taskQueue = new \Swoole\Coroutine\Channel($this->queueLength);
        $this->running = true;
        for($i = 0; $i < $this->coCount; ++$i)
        {
            go(function() use($i){
                $this->task($i);
            });
        }
    }

    /**
     * 停止协程池
     * 
     * 不会中断正在执行的任务
     * 
     * 等待当前任务全部执行完后，才算全部停止
     *
     * @return void
     */
    public function stop()
    {
        $this->taskQueue->close();
        $this->taskQueue = null;
        $this->running = false;
    }

    /**
     * 增加任务
     *
     * @param mixed $data
     * @param callable|null $callback
     * @return void
     */
    public function addTask($data, $callback = null)
    {
        $this->taskQueue->push([
            'data'      =>  $data,
            'callback'  =>  $callback,
        ]);
    }

    /**
     * 任务监听
     *
     * @param int $index
     * @return void
     */
    protected function task($index)
    {
        $taskObject = new $this->taskClass;
        do {
            $task = $this->taskQueue->pop();
            $param = new $this->taskParamClass($index, $task['data']);
            $result = $taskObject->run($param);
            if($task['callback'])
            {
                $task['callback']($param, $result);
            }
        } while($this->running);
    }

}