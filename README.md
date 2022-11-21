# swoole-co-pool

[![Latest Version](https://img.shields.io/packagist/v/yurunsoft/swoole-co-pool.svg)](https://packagist.org/packages/yurunsoft/swoole-co-pool)
[![License](https://img.shields.io/github/license/Yurunsoft/swoole-co-pool.svg)](https://github.com/Yurunsoft/swoole-co-pool/blob/master/LICENSE)

## 介绍

本项目为 Swoole 协程工作池，它封装了一些实用的 Swoole 协程操作，使用起来十分一把梭。

* 协程工作池

用于需要大量协程任务的场景，它可以限定你的同时工作协程数量，并且减少协程频繁创建销毁的损耗。

* 协程批量执行器

用于同时执行多个协程，并且能够获取到他们所有的返回值。

宇润PHP全家桶群：17916227 [![点击加群](https://pub.idqqimg.com/wpa/images/group.png "点击加群")](https://jq.qq.com/?_wv=1027&k=5wXf4Zq)，如有问题会有人解答和修复。

程序员日常划水群：74401592 [![点击加群](https://pub.idqqimg.com/wpa/images/group.png "点击加群")](https://shang.qq.com/wpa/qunwpa?idkey=e2e6b49e9a648aae5285b3aba155d59107bb66fde02e229e078bd7359cac8ac3)。

## 安装

在您的composer.json中加入配置：

```json
{
    "require": {
        "yurunsoft/swoole-co-pool": "^1.3.0"
    }
}
```

然后执行`composer update`命令。

## 使用

### 协程工作池

```php
use Yurun\Swoole\CoPool\CoPool;
use Yurun\Swoole\CoPool\Interfaces\ICoTask;
use Yurun\Swoole\CoPool\Interfaces\ITaskParam;

$coCount = 10; // 同时工作协程数
$queueLength = 1024; // 队列长度
$pool = new CoPool($coCount, $queueLength,
    // 定义任务匿名类，当然你也可以定义成普通类，传入完整类名
    new class implements ICoTask
    {
        /**
         * 执行任务
         *
         * @param ITaskParam $param
         * @return mixed
         */
        public function run(ITaskParam $param)
        {
            // 执行任务
            return true; // 返回任务执行结果，非必须
        }

    }
);
$pool->run();

$data = 1; // 可以传递任何参数

// 增加任务，并挂起协程等待返回任务执行结果
$result = $pool->addTask($data);

// 增加任务，异步回调
$result = $pool->addTaskAsync($data, function(ITaskParam $param, $data){
    // 异步回调
});

// 增加分组任务，并挂起协程等待返回任务执行结果
$result = $pool->addTask($data, '分组名称');

// 增加分组任务，异步回调
$result = $pool->addTaskAsync($data, function(ITaskParam $param, $data){
    // 异步回调
}, '分组名称');

$pool->wait(); // 等待协程池停止，不限时，true/false
$pool->wait(60); // 等待协程池停止，限时60秒，如果为-1则不限时，true/false
```

### 批量执行协程

每个方法都在单独的协程中被执行，然后可以统一获取到结果。

```php
$batch = new CoBatch([
    function(){
        return 'imi';
    },
    'a' =>  function(){
        return 'niu';
    },
    'b' =>  function(){
        return 'bi';
    },
]);
$results = $batch->exec();
// $timeout = -1; // 支持超时
// $limit = -1; // 限制同时工作协程数量
// $results = $batch->exec($timeout, $limit);
// $results = $batch->exec($timeout, $limit, $throws); // 捕获异常
var_dump($results);
// $results 值为:
// [
//     'imi',
//     'a' =>  'niu',
//     'b' =>  'bi',
// ]
// $throws 值为异常对象数组，成员键名和传入数组中的一致。没有异常则为空数组。
```

快捷函数：

```php
use function Yurun\Swoole\Coroutine\batch;
batch([
    function(){
        return 'imi';
    },
    'a' =>  function(){
        return 'niu';
    },
    'b' =>  function(){
        return 'bi';
    },
]);
// batch($callables, $timeout, $limit);
// batch($callables, $timeout, $limit, $throws); // 捕获异常
// $throws 值为异常对象数组，成员键名和传入数组中的一致。没有异常则为空数组。
```

### 批量执行协程(迭代器模式)

支持通过数组、迭代器批量执行，并返回结果（不保证原始顺序）。  
> 1. 适合对大型结果集、不确定长度结果集进行协程并发计算并取得计算结果。  
> 2. 相对于普通批量执行（CoBatch）可以更好地控制内存使用量。

```php
use Yurun\Swoole\CoPool\CoBatchIterator;

$input = function ($size = 100) {
    while ($size--)
    {
        $random = mt_rand(100, 900);
        yield $size => function () use ($random) {
            // 模拟IO任务
            usleep($random * 10);

            return $random;
        };
    }
};

$timeout = -1; // 支持超时
$limit = 8;    // 限制同时工作协程数量
$batch = new CoBatchIterator($input(), $timeout, $limit);
$iter = $batch->exec();

foreach ($iter as $key => $value)
{
    if ($value > 500)
    {
        // 获得符合要求的结果，执行相应的代码逻辑并中断循环
        // 业务代码...
        
        // 可以发送`false`到迭代器中止仍在运行的协程快速回收资源
        $iter->send(false);
        break;
    }
}

// 可以查看批量执行是以什么方式退出的
var_dump($result = $iter->getReturn());
// $result === CoBatchIterator::SUCCESS; // 全部任务完成
// $result === CoBatchIterator::BREAK;   // 执行被主动中断
// $result === CoBatchIterator::TIMEOUT; // 任务超时
// $result === CoBatchIterator::UNKNOWN; // 未知原因（一般情况不会发生）
```

快捷函数：

```php
use function Yurun\Swoole\Coroutine\batchIterator;
batchIterator([
    function(){
        return 'imi';
    },
    'a' =>  function(){
        return 'niu';
    },
    'b' =>  function(){
        return 'bi';
    },
]);
// batchIterator($callables, $timeout, $limit);
```

### 执行单个协程并等待返回值

```php
use function Yurun\Swoole\Coroutine\goWait;
$result = goWait(function(){
    \Swoole\Coroutine::sleep(1);
    return 'wait result';
});
echo $result; // wait result

// 最大执行时间 0.5 秒，超过时间返回 null，但任务不会中断
$result = goWait(function(){
    \Swoole\Coroutine::sleep(1);
    return 'wait result';
}, 0.5);

// 捕获异常并在当前上下文抛出
try {
    $result = goWait(function(){
        throw new \RuntimeException('gg');
    }, -1, true); // 第 3 个参数传 true
} catch(\Throwable $th) {
    var_dump($th);
}
```

### 通道容器

```php
use Yurun\Swoole\CoPool\ChannelContainer;

go(function(){
    $channelContainer = new ChannelContainer;

    $id = 'abc';
    $data = [
        'time'  =>  time(),
    ];

    go(function() use($id, $data, $channelContainer){
        echo 'Wait 3 seconds...', PHP_EOL;
        \Swoole\Coroutine::sleep(3);
        $channelContainer->push($id, $data);
    });
    var_dump($channelContainer->pop($id));

});
```

## 代码示例

详见 `example` 目录

## 捐赠

<img src="https://raw.githubusercontent.com/Yurunsoft/swoole-co-pool/master/res/pay.png"/>

开源不求盈利，多少都是心意，生活不易，随缘随缘……
