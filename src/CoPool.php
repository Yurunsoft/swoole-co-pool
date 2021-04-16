<?php

namespace Yurun\Swoole\CoPool;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class CoPool
{
    /**
     * 工作协程数量.
     *
     * @var int
     */
    private $coCount;

    /**
     * 队列最大长度.
     *
     * @var int
     */
    private $queueLength;

    /**
     * 任务队列.
     *
     * @var \Swoole\Coroutine\Channel
     */
    private $taskQueue;

    /**
     * 是否正在运行.
     *
     * @var bool
     */
    private $running = false;

    /**
     * 任务类.
     *
     * @var string
     */
    public $taskClass;

    /**
     * 任务参数类名.
     *
     * @var string
     */
    public $taskParamClass;

    /**
     * 创建协程的函数.
     *
     * 有些框架自定义了新建协程的方法，用于控制上下文生命周期，所以加了这个属性用于兼容
     *
     * @var callable
     */
    public $createCoCallable = 'go';

    /**
     * 等待的通道.
     *
     * @var \Swoole\Coroutine\Channel
     */
    private $waitChannel;

    /**
     * 分组通道列表.
     *
     * @var \Swoole\Coroutine\Channel[]
     */
    private $groupChannels = [];

    /**
     * 分组工作状态
     *
     * @var array
     */
    private $groupWorkingStatus = [];

    /**
     * 构造方法.
     *
     * @param int    $coCount        工作协程数量
     * @param int    $queueLength    队列最大长度
     * @param string $taskClass      任务类
     * @param string $taskParamClass 任务参数类名
     */
    public function __construct($coCount, $queueLength, $taskClass, $taskParamClass = TaskParam::class)
    {
        $this->coCount = $coCount;
        $this->queueLength = $queueLength;
        $this->taskClass = $taskClass;
        $this->taskParamClass = $taskParamClass;
    }

    /**
     * 运行协程池.
     *
     * @return void
     */
    public function run()
    {
        if ($this->taskQueue)
        {
            $this->taskQueue->close();
        }
        $this->taskQueue = new Channel($this->queueLength);
        $this->waitChannel = new Channel(1);
        $this->running = true;
        for ($i = 0; $i < $this->coCount; ++$i)
        {
            Coroutine::create(function () use ($i) {
                $this->task($i);
            });
        }
    }

    /**
     * 停止协程池.
     *
     * 不会中断正在执行的任务
     *
     * 等待当前任务全部执行完后，才算全部停止
     *
     * @return void
     */
    public function stop()
    {
        $this->running = false;
        $this->taskQueue->close();
        $this->taskQueue = null;
        $this->waitChannel->push(1);
    }

    /**
     * 等待协程池停止.
     */
    public function wait(float $timeout = -1): bool
    {
        return (bool) $this->waitChannel->pop($timeout);
    }

    /**
     * 增加任务，并挂起协程等待返回任务执行结果.
     *
     * @param mixed $data
     * @param mixed $group
     *
     * @return mixed
     */
    public function addTask($data, $group = null)
    {
        $channel = new Channel(1);
        try
        {
            if ($this->taskQueue->push([
                'data'      =>  $data,
                'channel'   =>  $channel,
                'group' => $group,
            ]))
            {
                $result = $channel->pop();
                if (false === $result)
                {
                    return false;
                }
                else
                {
                    return $result['result'];
                }
            }
            else
            {
                throw new \RuntimeException(sprintf('AddTask failed! Channel errCode = %s', $this->taskQueue->errCode));
            }
        }
        catch (\Throwable $th)
        {
            throw $th;
        }
        finally
        {
            $channel->close();
        }
    }

    /**
     * 增加任务，异步回调.
     *
     * 执行完成后新建一个协程调用 $callback，为 null 不执行回调
     *
     * @param mixed    $data
     * @param callable $callback
     * @param mixed    $group
     *
     * @return bool
     */
    public function addTaskAsync($data, $callback = null, $group = null)
    {
        return $this->taskQueue->push([
            'data'      => $data,
            'callback'  => $callback,
            'group'     => $group,
        ]);
    }

    /**
     * 任务监听.
     *
     * @param int $index
     *
     * @return void
     */
    protected function task($index)
    {
        $taskObject = new $this->taskClass();
        do
        {
            $task = $this->taskQueue->pop();
            if (false !== $task)
            {
                if ($group = ($task['group'] ?? null))
                {
                    if ($this->groupWorkingStatus[$group] ?? false)
                    {
                        if (isset($this->groupChannels[$group]))
                        {
                            $groupChannel = $this->groupChannels[$group];
                        }
                        else
                        {
                            $groupChannel = $this->groupChannels[$group] = new Channel($this->queueLength);
                        }
                        $groupChannel->push($task);
                        continue;
                    }
                    $this->groupWorkingStatus[$group] = true;
                }
                try
                {
                    $param = new $this->taskParamClass($index, $task['data']);
                    $result = $taskObject->run($param);
                }
                catch (\Throwable $th)
                {
                    throw $th;
                }
                finally
                {
                    if ($group)
                    {
                        $this->groupWorkingStatus[$group] = false;
                        if (isset($this->groupChannels[$group]))
                        {
                            $groupChannel = $this->groupChannels[$group];
                            $tmpTask = $groupChannel->pop(0.001);
                            if (false !== $tmpTask)
                            {
                                $this->taskQueue->push($tmpTask);
                            }
                            if ($groupChannel->isEmpty())
                            {
                                unset($this->groupChannels[$group]);
                            }
                            unset($this->groupWorkingStatus[$group]);
                        }
                    }
                    if (!isset($result))
                    {
                        $result = null;
                    }
                    if (isset($task['channel']))
                    {
                        $task['channel']->push([
                            'param'     => $param,
                            'result'    => $result,
                        ]);
                    }
                    elseif (isset($task['callback']))
                    {
                        ($this->createCoCallable)(function () use ($task, $param, $result) {
                            $task['callback']($param, $result);
                        });
                    }
                }
            }
        } while ($this->running);
    }

    /**
     * 检测是否正在运行.
     *
     * @return bool
     */
    public function isRunning()
    {
        return $this->running;
    }

    /**
     * 获取队列中待执行任务长度.
     *
     * @return int
     */
    public function getQueueLength()
    {
        return $this->taskQueue->length();
    }
}
