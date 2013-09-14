<?php
namespace PHPSocketIO\Http\WebSocket;

use PHPSocketIO\Http\WebSocket\MessageQueue;

class myMessageQueue extends MessageQueue
{
    public function __get($name)
    {
        $property = str_replace('_','', $name);
        return $this->$property;
    }
    public function __set($name, $value)
    {
        $property = str_replace('_','', $name);
        $this->$property = $value;
    }
}

/**
 * Description of Message
 *
 * @author ricky
 */
class MessageQueueTest extends \PHPUnit_Framework_TestCase
{

    protected $messageQueue;

    protected function setUp()
    {
        $this->messageQueue = new myMessageQueue();
    }

    public function test_add()
    {
        $token = md5(microtime().rand());
        $this->messageQueue->add($token);
        $this->messageQueue->add($token);
        $this->assertEquals($token.$token, $this->messageQueue->_data);
    }

    public function test_clear()
    {
        $token = md5(microtime().rand());
        $this->messageQueue->add($token);
        $this->messageQueue->clear();
        $this->assertEmpty($this->messageQueue->_data);
    }

    public function test_getAll()
    {
        $token = md5(microtime().rand());
        $this->messageQueue->clear();
        $this->messageQueue->add($token);
        $this->assertEquals($token, $this->messageQueue->getAll());
    }

    public function test__toString()
    {
        $token = md5(microtime().rand());
        $this->messageQueue->clear();
        $this->messageQueue->add($token);
        $this->assertEquals($this->messageQueue, $this->messageQueue->getAll());
    }

    public function test_shift()
    {
        $token = md5(microtime().rand());
        $this->messageQueue->clear();
        $this->messageQueue->add($token);
        $part = $this->messageQueue->shift(4);
        $this->assertEquals(substr($token, 0, 4), $part);
        $this->assertEquals(substr($token, 4), $this->messageQueue->getAll());
    }

}

