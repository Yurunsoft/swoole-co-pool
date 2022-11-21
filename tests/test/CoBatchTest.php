<?php

namespace Yurun\Swoole\CoPool\Test;

use Swoole\Coroutine;
use Yurun\Swoole\CoPool\CoBatch;
use function Yurun\Swoole\Coroutine\batch;
use function Yurun\Swoole\Coroutine\goWait;

class CoBatchTest extends BaseTest
{
    public function testBatch()
    {
        $this->go(function () {
            $batch = new CoBatch([
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
            $results = $batch->exec();
            $this->assertEquals([
                'imi',
                'a' => 'niu',
                'b' => 'bi',
            ], $results);
        });
        $this->go(function () {
            $results = batch([
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
            $batch = new CoBatch([
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
            $results = $batch->exec($timeout);
            $this->assertEquals([
                'imi',
                'a' => null,
                'b' => null,
            ], $results);
        });
        $this->go(function () {
            $timeout = 1;
            $results = batch([
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
            $this->assertEquals([
                'imi',
                'a' => null,
                'b' => null,
            ], $results);
        });
    }

    public function testBatchLimit()
    {
        $this->go(function () {
            $batch = new CoBatch([
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
            $results = $batch->exec($timeout, $limit);
            $useTime = round(microtime(true) - $time, 2);
            $this->assertGreaterThanOrEqual(3, $useTime);
            $this->assertLessThan(4, $useTime);
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
            $results = batch([
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
            $useTime = round(microtime(true) - $time, 2);
            $this->assertGreaterThanOrEqual(3, $useTime);
            $this->assertLessThan(4, $useTime);
            $this->assertEquals([
                'a',
                'b',
                'c',
                'd',
                'test' => 'e',
            ], $results);
        });
    }

    public function testGoWait()
    {
        $this->go(function () {
            $result = goWait(function () {
                Coroutine::sleep(1);

                return 'wait result';
            });
            $this->assertEquals('wait result', $result);

            $this->expectExceptionMessage('gg');
            goWait(function () {
                throw new \RuntimeException('gg');
            }, -1, true);
        });
    }

    public function testException()
    {
        $this->go(function () {
            $batch = new CoBatch([
                function () {
                    return 'imi';
                },
                'a' => function () {
                    throw new \RuntimeException('gg');
                },
                'b' => function () {
                    return 'bi';
                },
            ]);
            $results = $batch->exec(null, null, $throws);
            $this->assertEquals([
                'imi',
                'a' => null,
                'b' => 'bi',
            ], $results);
            $this->assertTrue(isset($throws['a']));
            $this->assertInstanceOf(\RuntimeException::class, $throws['a']);
        });
        $this->go(function () {
            $results = batch([
                function () {
                    return 'imi';
                },
                'a' => function () {
                    throw new \RuntimeException('gg');
                },
                'b' => function () {
                    return 'bi';
                },
            ], -1, -1, $throws);
            $this->assertEquals([
                'imi',
                'a' => null,
                'b' => 'bi',
            ], $results);
            $this->assertTrue(isset($throws['a']));
            $this->assertInstanceOf(\RuntimeException::class, $throws['a']);
        });
    }
}
