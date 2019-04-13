<?php
namespace Yurun\Swoole\CoPool\Interfaces;

interface ICoTask
{
    /**
     * 执行任务
     *
     * @param ITaskParam $param
     * @return void
     */
    public function run(ITaskParam $param);

}