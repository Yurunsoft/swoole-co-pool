<?php
namespace Yurun\Swoole\CoPool;

use Yurun\Swoole\CoPool\Interfaces\ITaskParam;

class TaskParam implements ITaskParam
{
    /**
     * 当前协程在协程池中的顺序，从0开始编号
     *
     * @var int
     */
    private $index;

    /**
     * 数据
     *
     * @var mixed
     */
    private $data;

    public function __construct($index, $data)
    {
        $this->index = $index;
        $this->data = $data;
    }

    /**
     * 获取数据
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * 获取当前协程在协程池中的顺序，从0开始编号
     *
     * @return int
     */
    public function getCoIndex()
    {
        return $this->index;
    }

}