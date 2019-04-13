<?php
namespace Yurun\Swoole\CoPool\Interfaces;

interface ITaskParam
{
    public function __construct($index, $data);

    /**
     * 获取数据
     *
     * @return mixed
     */
    public function getData();

    /**
     * 获取当前协程在协程池中的顺序，从0开始编号
     *
     * @return int
     */
    public function getCoIndex();
}