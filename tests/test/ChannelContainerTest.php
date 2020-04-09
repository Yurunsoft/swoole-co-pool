<?php
namespace Yurun\Swoole\CoPool\Test;

use Yurun\Swoole\CoPool\ChannelContainer;

class ChannelContainerTest extends BaseTest
{
    public function testPushAndPop()
    {
        $this->go(function(){
            $channelContainer = new ChannelContainer;

            $id = 'abc';
            $data = [
                'time'  =>  time(),
            ];
            $channelContainer->push($id, $data);
            $this->assertTrue($channelContainer->hasChannel($id));
            $this->assertEquals($data, $channelContainer->pop($id));
            $this->assertTrue($channelContainer->hasChannel($id));

            go(function() use($id, $data, $channelContainer){
                \Swoole\Coroutine::sleep(1);
                $channelContainer->push($id, $data);
            });
            $this->assertEquals($data, $channelContainer->pop($id));

            go(function() use($id, $data, $channelContainer){
                \Swoole\Coroutine::sleep(1);
                $channelContainer->push($id, $data);
            });
            $this->assertFalse($channelContainer->pop($id, 0.001));
        });
    }

    public function testPushAndFinallyPop()
    {
        $this->go(function(){
            $channelContainer = new ChannelContainer;

            $id = 'abc';
            $data = [
                'time'  =>  time(),
            ];
            $channelContainer->push($id, $data);
            $this->assertTrue($channelContainer->hasChannel($id));
            $this->assertEquals($data, $channelContainer->finallyPop($id));
            $this->assertFalse($channelContainer->hasChannel($id));

            go(function() use($id, $data, $channelContainer){
                \Swoole\Coroutine::sleep(1);
                $channelContainer->push($id, $data);
            });
            $this->assertEquals($data, $channelContainer->finallyPop($id));

            go(function() use($id, $data, $channelContainer){
                \Swoole\Coroutine::sleep(1);
                $channelContainer->push($id, $data);
            });
            $this->assertFalse($channelContainer->finallyPop($id, 0.001));
        });
    }

}
