<?php

namespace Yurun\Swoole\CoPool\Test;

use function array_intersect_key;
use function iterator_to_array;
use function krsort;
use function ksort;
use function mt_rand;
use Swoole\Coroutine;
use function usleep;
use Yurun\Swoole\CoPool\CoBatchIterator;
use function Yurun\Swoole\Coroutine\batchIterator;

class CoBatchIteratorTest extends BaseTest
{
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
            $iter = $batch->exec();
            $results = iterator_to_array($iter);
            $this->assertEquals(CoBatchIterator::SUCCESS, $iter->getReturn());
            ksort($results);
            $this->assertEquals([
                'imi',
                'a' => 'niu',
                'b' => 'bi',
            ], $results);
        });
        $this->go(function () {
            $iter = batchIterator([
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
            $results = iterator_to_array($iter);
            $this->assertEquals(CoBatchIterator::SUCCESS, $iter->getReturn());
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
            $iter = $batch->exec($timeout);
            $results = iterator_to_array($iter);
            $this->assertEquals(CoBatchIterator::TIMEOUT, $iter->getReturn());
            ksort($results);
            $this->assertEquals([
                'imi',
            ], $results);
        });
        $this->go(function () {
            $timeout = 1;
            $iter = batchIterator([
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
            $results = iterator_to_array($iter);
            $this->assertEquals(CoBatchIterator::TIMEOUT, $iter->getReturn());
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
            $results = iterator_to_array($iter = $batch->exec($timeout, $limit));
            $this->assertEquals(CoBatchIterator::SUCCESS, $iter->getReturn());
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
            $iter = batchIterator([
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
            $results = iterator_to_array($iter);
            $this->assertEquals(CoBatchIterator::SUCCESS, $iter->getReturn());
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

    public function testBatchNoTimeout()
    {
        $batch = new CoBatchIterator([
            function () {
                Coroutine::sleep(0.5);

                return 'imi';
            },
            'a' => function () {
                Coroutine::sleep(1);

                return 'niu';
            },
            'b' => function () {
                Coroutine::sleep(1.5);

                return 'bi';
            },
        ]);
        $iter = $batch->exec(3);
        $results = iterator_to_array($iter);
        $this->assertEquals(CoBatchIterator::SUCCESS, $iter->getReturn());
        ksort($results);
        $this->assertEquals([
            'imi',
            'a' => 'niu',
            'b' => 'bi',
        ], $results);
    }

    public function testBatchEx()
    {
        $rawList = [];
        $fn = function ($size = 100) use (&$rawList) {
            while ($size--)
            {
                $random = mt_rand(1000, 10000);
                $rawList[$size] = $random;
                yield $size => function () use ($random) {
                    usleep($random);

                    return $random;
                };
            }
        };

        $batch = new CoBatchIterator($fn(), -1, 8);
        $iter = $batch->exec();

        $result = [];
        foreach ($iter as $key => $value)
        {
            $result[$key] = $value;
        }
        $this->assertEquals(CoBatchIterator::SUCCESS, $iter->getReturn());
        krsort($result);
        $this->assertEquals($rawList, $result);
    }

    public function testBatchExNoLimit()
    {
        $rawList = [];
        $fn = function ($size = 100) use (&$rawList) {
            while ($size--)
            {
                $random = mt_rand(1000, 10000);
                $rawList[$size] = $random;
                yield $size => function () use ($random) {
                    usleep($random);

                    return $random;
                };
            }
        };

        $batch = new CoBatchIterator($fn(), -1, -1);
        $iter = $batch->exec();

        $result = [];
        foreach ($iter as $key => $value)
        {
            $result[$key] = $value;
        }
        $this->assertEquals(CoBatchIterator::SUCCESS, $iter->getReturn());
        krsort($result);
        $this->assertEquals($rawList, $result);
    }

    private function generateTestData(?array &$rawList): callable
    {
        $rawList = [];

        return function ($size = 100) use (&$rawList) {
            while ($size--)
            {
                $random = mt_rand(1000, 10000);
                $rawList[$size] = $random;
                yield $size => function () use ($random) {
                    usleep($random);

                    return $random;
                };
            }
        };
    }

    public function testBatchExNoLimitBreak()
    {
        $fn = $this->generateTestData($rawList);

        $batch = new CoBatchIterator($fn(), -1, -1);
        $iter = $batch->exec();

        $result = [];
        foreach ($iter as $key => $value)
        {
            $result[$key] = $value;
            if (50 === $key)
            {
                break;
            }
        }
        $iter->send(false);

        krsort($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(CoBatchIterator::BREAK, $iter->getReturn());
        $this->assertEquals(array_intersect_key($rawList, $result), $result);
    }

    public function testBatchExLimitBreak()
    {
        $fn = $this->generateTestData($rawList);

        $batch = new CoBatchIterator($fn(), -1, 8);
        $iter = $batch->exec();

        $result = [];
        foreach ($iter as $key => $value)
        {
            $result[$key] = $value;
            if (50 === $key)
            {
                break;
            }
        }
        $iter->send(false);

        krsort($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(CoBatchIterator::BREAK, $iter->getReturn());
        $this->assertEquals(array_intersect_key($rawList, $result), $result);
    }

    public function testBatchExBreak2()
    {
        $fn = $this->generateTestData($rawList);

        $batch = new CoBatchIterator($fn(), -1, 8);
        $iter = $batch->exec();

        $result = [];
        while ($iter->valid())
        {
            $result[$iter->key()] = $iter->current();
            if (50 === $iter->key())
            {
                $iter->send(false);
                break;
            }
            $iter->next();
        }

        krsort($result);
        $this->assertNotEmpty($result);
        $this->assertEquals(CoBatchIterator::BREAK, $iter->getReturn());
        $this->assertEquals(array_intersect_key($rawList, $result), $result);
    }
}
