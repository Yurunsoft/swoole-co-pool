<?php
namespace Yurun\Swoole\CoPool\Test;

use Yurun\Swoole\CoPool\CoBatch;

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
    }

}
