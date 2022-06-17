<?php

namespace Yurun\Swoole\CoPool\Test;

use Swoole\Coroutine;
use Yurun\Swoole\CoPool\CoBatch;
use Yurun\Swoole\CoPool\CoBatchIterator;
use function iterator_to_array;
use function ksort;
use function sort;
use function var_dump;
use function Yurun\Swoole\Coroutine\batch;
use function Yurun\Swoole\Coroutine\batchIterator;
use function Yurun\Swoole\Coroutine\goWait;

class CoBatchIteratorTest extends BaseTest
{
    private function arrSort ()
    {

    }

    public function testBatch()
    {
        $this->go(function () {
            $batch = new CoBatchIterator([
                function () {
                    return 'imi';
                },
                'a' => function () {
                    return 'niu';
                },
                'b' => function () {
                    return 'bi';
                },
            ]);
            $results = iterator_to_array($batch->exec());
            ksort($results);
            $this->assertEquals([
                'imi',
                'a' => 'niu',
                'b' => 'bi',
            ], $results);
        });
        $this->go(function () {
            $results = batchIterator([
                function () {
                    return 'imi';
                },
                'a' => function () {
                    return 'niu';
                },
                'b' => function () {
                    return 'bi';
                },
            ]);
            $results = iterator_to_array($results);
            ksort($results);
            $this->assertEquals([
                'imi',
                'a' => 'niu',
                'b' => 'bi',
            ], $results);
        });
    }

    public function testBatchTimeout()
    {
        $this->go(function () {
            $batch = new CoBatchIterator([
                function () {
                    Coroutine::sleep(0.5);

                    return 'imi';
                },
                'a' => function () {
                    Coroutine::sleep(2);

                    return 'niu';
                },
                'b' => function () {
                    Coroutine::sleep(3);

                    return 'bi';
                },
            ]);
            $timeout = 1;
            $results = iterator_to_array($batch->exec($timeout));
            ksort($results);
            $this->assertEquals([
                'imi',
            ], $results);
        });
        $this->go(function () {
            $timeout = 1;
            $results = batchIterator([
                function () {
                    Coroutine::sleep(0.5);

                    return 'imi';
                },
                'a' => function () {
                    Coroutine::sleep(2);

                    return 'niu';
                },
                'b' => function () {
                    Coroutine::sleep(3);

                    return 'bi';
                },
            ], $timeout);
            $results = iterator_to_array($results);
            ksort($results);
            $this->assertEquals([
                'imi',
            ], $results);
        });
    }

    public function testBatchLimit()
    {
        $this->go(function () {
            $batch = new CoBatchIterator([
                function () {
                    Coroutine::sleep(1);

                    return 'a';
                },
                function () {
                    Coroutine::sleep(1);

                    return 'b';
                },
                function () {
                    Coroutine::sleep(1);

                    return 'c';
                },
                function () {
                    Coroutine::sleep(1);

                    return 'd';
                },
                'test'  => function () {
                    Coroutine::sleep(1);

                    return 'e';
                },
            ]);
            $timeout = -1;
            $limit = 2;
            $time = microtime(true);
            $results = iterator_to_array($batch->exec($timeout, $limit));
            $useTime = round(microtime(true) - $time, 2);
            $this->assertGreaterThanOrEqual(3, $useTime);
            $this->assertLessThan(4, $useTime);
            ksort($results);
            $this->assertEquals([
                'a',
                'b',
                'c',
                'd',
                'test' => 'e',
            ], $results);
        });
        $this->go(function () {
            $timeout = -1;
            $limit = 2;
            $time = microtime(true);
            $results = batchIterator([
                function () {
                    Coroutine::sleep(1);

                    return 'a';
                },
                function () {
                    Coroutine::sleep(1);

                    return 'b';
                },
                function () {
                    Coroutine::sleep(1);

                    return 'c';
                },
                function () {
                    Coroutine::sleep(1);

                    return 'd';
                },
                'test'  => function () {
                    Coroutine::sleep(1);

                    return 'e';
                },
            ], $timeout, $limit);
            $results = iterator_to_array($results);
            $useTime = round(microtime(true) - $time, 2);
            $this->assertGreaterThanOrEqual(3, $useTime);
            $this->assertLessThan(4, $useTime);
            ksort($results);
            $this->assertEquals([
                'a',
                'b',
                'c',
                'd',
                'test' => 'e',
            ], $results);
        });
    }
}
