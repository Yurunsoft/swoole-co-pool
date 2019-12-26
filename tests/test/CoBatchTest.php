<?php
namespace Yurun\Swoole\CoPool\Test;

use Yurun\Swoole\CoPool\CoBatch;
use Swoole\Coroutine;

use function Yurun\Swoole\Coroutine\batch;

class CoBatchTest extends BaseTest
{
    public function testBatch()
    {
        $this->go(function(){
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
            $this->assertEquals([
                'imi',
                'a' =>  'niu',
                'b' =>  'bi',
            ], $results);
        });
        $this->go(function(){
            $results = batch([
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
            $this->assertEquals([
                'imi',
                'a' =>  'niu',
                'b' =>  'bi',
            ], $results);
        });
    }

    public function testBatchTimeout()
    {
        $this->go(function(){
            $batch = new CoBatch([
                function(){
                    Coroutine::sleep(0.5);
                    return 'imi';
                },
                'a' =>  function(){
                    Coroutine::sleep(2);
                    return 'niu';
                },
                'b' =>  function(){
                    Coroutine::sleep(3);
                    return 'bi';
                },
            ]);
            $timeout = 1;
            $results = $batch->exec($timeout);
            $this->assertEquals([
                'imi',
                'a' =>  null,
                'b' =>  null,
            ], $results);
        });
        $this->go(function(){
            $timeout = 1;
            $results = batch([
                function(){
                    Coroutine::sleep(0.5);
                    return 'imi';
                },
                'a' =>  function(){
                    Coroutine::sleep(2);
                    return 'niu';
                },
                'b' =>  function(){
                    Coroutine::sleep(3);
                    return 'bi';
                },
            ], $timeout);
            $this->assertEquals([
                'imi',
                'a' =>  null,
                'b' =>  null,
            ], $results);
        });
    }

    public function testBatchLimit()
    {
        $this->go(function(){
            $batch = new CoBatch([
                function(){
                    Coroutine::sleep(1);
                    return 'a';
                },
                function(){
                    Coroutine::sleep(1);
                    return 'b';
                },
                function(){
                    Coroutine::sleep(1);
                    return 'c';
                },
                function(){
                    Coroutine::sleep(1);
                    return 'd';
                },
                'test'  =>  function(){
                    Coroutine::sleep(1);
                    return 'e';
                },
            ]);
            $timeout = -1;
            $limit = 2;
            $time = microtime(true);
            $results = $batch->exec($timeout, $limit);
            $useTime = round(microtime(true) - $time, 2);
            $this->assertGreaterThanOrEqual(3, $useTime);
            $this->assertLessThan(4, $useTime);
            $this->assertEquals([
                'a',
                'b',
                'c',
                'd',
                'test' =>  'e',
            ], $results);
        });
        $this->go(function(){
            $timeout = -1;
            $limit = 2;
            $time = microtime(true);
            $results = batch([
                function(){
                    Coroutine::sleep(1);
                    return 'a';
                },
                function(){
                    Coroutine::sleep(1);
                    return 'b';
                },
                function(){
                    Coroutine::sleep(1);
                    return 'c';
                },
                function(){
                    Coroutine::sleep(1);
                    return 'd';
                },
                'test'  =>  function(){
                    Coroutine::sleep(1);
                    return 'e';
                },
            ], $timeout, $limit);
            $useTime = round(microtime(true) - $time, 2);
            $this->assertGreaterThanOrEqual(3, $useTime);
            $this->assertLessThan(4, $useTime);
            $this->assertEquals([
                'a',
                'b',
                'c',
                'd',
                'test' =>  'e',
            ], $results);
        });
    }

}
