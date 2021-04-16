<?php

namespace Yurun\Swoole\CoPool;

use Swoole\Coroutine\Channel;

/**
 * 通道容器.
 */
class ChannelContainer
{
    /**
     * 通道集合.
     *
     * @var \Swoole\Coroutine\Channel[]
     */
    private $channels = [];

    public function push(string $id, $data, float $timeout = -1): bool
    {
        return $this->getChannel($id)->push($data, $timeout);
    }

    public function pop(string $id, float $timeout = -1)
    {
        return $this->getChannel($id)->pop($timeout);
    }

    /**
     * 从通道中读取数据，并且销毁该通道.
     *
     * @return mixed
     */
    public function finallyPop(string $id, float $timeout = -1)
    {
        $result = $this->getChannel($id)->pop($timeout);
        $this->removeChannel($id);

        return $result;
    }

    public function stats(string $id): array
    {
        if ($this->hasChannel($id))
        {
            return $this->getChannel($id)->stats();
        }
        else
        {
            throw new \RuntimeException(sprintf('Channel %s does not exists', $id));
        }
    }

    public function close(string $id): bool
    {
        if ($this->hasChannel($id))
        {
            return $this->getChannel($id)->close();
        }
        else
        {
            throw new \RuntimeException(sprintf('Channel %s does not exists', $id));
        }
    }

    public function length(string $id): int
    {
        if ($this->hasChannel($id))
        {
            return $this->getChannel($id)->length();
        }
        else
        {
            throw new \RuntimeException(sprintf('Channel %s does not exists', $id));
        }
    }

    public function isEmpty(string $id): bool
    {
        if ($this->hasChannel($id))
        {
            return $this->getChannel($id)->isEmpty();
        }
        else
        {
            throw new \RuntimeException(sprintf('Channel %s does not exists', $id));
        }
    }

    public function isFull(string $id): bool
    {
        if ($this->hasChannel($id))
        {
            return $this->getChannel($id)->isFull();
        }
        else
        {
            throw new \RuntimeException(sprintf('Channel %s does not exists', $id));
        }
    }

    /**
     * 获取通道.
     */
    public function getChannel(string $id): Channel
    {
        if (isset($this->channels[$id]))
        {
            return $this->channels[$id];
        }
        else
        {
            return $this->channels[$id] = new Channel(1);
        }
    }

    /**
     * 通道是否存在.
     */
    public function hasChannel(string $id): bool
    {
        return isset($this->channels[$id]);
    }

    /**
     * 移除通道.
     *
     * @return void
     */
    public function removeChannel(string $id)
    {
        if ($this->hasChannel($id))
        {
            $this->channels[$id]->close();
            unset($this->channels[$id]);
        }
    }
}
