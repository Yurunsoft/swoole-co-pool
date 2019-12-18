<?php
namespace Imi\Grpc\Test;

use Swoole\Event;
use PHPUnit\Framework\TestCase;
use Yurun\Swoole\CoPool\CoBatch;

class CoBatchTest extends TestCase
{
    public function testBatch()
    {
        go(function(){
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
        Event::wait();
    }

}
