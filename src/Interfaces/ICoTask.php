<?php
namespace Yurun\Swoole\CoPool\Interfaces;

interface ICoTask
{
    /**
     * 执行任务
     *
     * @param \Yurun\Swoole\CoPool\Interfaces\ITaskParam $param
     * @return mixed
     */
    public function run(ITaskParam $param);

}