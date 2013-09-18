<?php
namespace PHPSocketIO\Event;

use Symfony\Component\EventDispatcher\Event;

class myEventDispatch extends EventDispatcher
{
    static public function reset()
    {
        static::$dispatcher = null;
    }
}
/**
 * Description of EventDispatcherTest
 *
 * @author ricky
 */
class EventDispatcherTest extends \PHPUnit_Framework_TestCase
{

    public function test_getDispatcher()
    {
        $this->assertTrue(EventDispatcher::getDispatcher() instanceof EventDispatcher);
    }

    public function test_addListener()
    {
        myEventDispatch::reset();
        $dispatcher = myEventDispatch::getDispatcher();
        $this->assertTrue($dispatcher->addListener("testevent", function(){}));
    }

    public function test_dispatch_event_receive()
    {
        myEventDispatch::reset();
        $dispatcher = myEventDispatch::getDispatcher();
        $event = new Event();

        $eventTestResultEvent = [];

        $dispatcher->addListener('testevent', function(Event $recvEvent) use($event, &$eventTestResultEvent){
            $eventTestResultEvent[] = $recvEvent;
        });

        $dispatcher->addListener('testevent', function(Event $recvEvent) use($event, &$eventTestResultEvent){
            $eventTestResultEvent[] = $recvEvent;
        });

        $dispatcher->dispatch("testevent", $event);
        $this->assertEquals("testevent", $event->getName());
        $this->assertEquals($event, $eventTestResultEvent[0]);
        $this->assertEquals($event, $eventTestResultEvent[1]);
    }

    public function test_dispatch_with_uniqueid()
    {
        myEventDispatch::reset();
        $dispatcher = myEventDispatch::getDispatcher();
        $event = new Event();
        $event->triggeredEvent = [];
        $uniqueId1 = md5(rand().microtime());

        $uniqueId2 = md5(rand().microtime());

        $dispatcher->addListener('testevent', function(Event $event) {
            $event->triggeredEvent[] = 1;
        }, $uniqueId1);

        $dispatcher->addListener('testevent', function(Event $event) {
            $event->triggeredEvent[] = 2;
        }, $uniqueId2);

        $dispatcher->addListener('testevent', function(Event $event) {
            $event->triggeredEvent[] = 1;
        }, $uniqueId1);


        $dispatcher->dispatch("testevent", $event, $uniqueId1);

        $this->assertEquals(array(1, 1), $event->triggeredEvent);
    }

    public function test_dispatch_with_priority()
    {
        myEventDispatch::reset();
        $dispatcher = myEventDispatch::getDispatcher();
        $event = new Event();
        $event->triggeredEvent = [];

        $dispatcher->addListener('testevent', function(Event $event) {
            $event->triggeredEvent[] = 1;
        });

        $dispatcher->addListener('testevent', function(Event $event) {
            $event->triggeredEvent[] = 2;
        }, null, true);

        $dispatcher->addListener('testevent', function(Event $event) {
            $event->triggeredEvent[] = 3;
        });

        $dispatcher->addListener('testevent', function(Event $event) {
            $event->triggeredEvent[] = 4;
        });

        $dispatcher->dispatch('testevent', $event);

        $this->assertEquals(array(2, 1, 3, 4), $event->triggeredEvent);
    }

    public function test_dispatch_with_stop_propagation()
    {
        myEventDispatch::reset();
        $dispatcher = myEventDispatch::getDispatcher();

        $event = new Event();
        $event->triggeredEvent = [];
        $uniqueId1 = md5(rand().microtime());

        $dispatcher->addListener('testevent', function(Event $event) {
            $event->triggeredEvent[] = 1;
            $event->stopPropagation();
        }, $uniqueId1);

        $dispatcher->addListener('testevent', function(Event $event) {
            $event->triggeredEvent[] = 2;
        }, $uniqueId1);

        $dispatcher->dispatch('testevent', $event, $uniqueId1);
        $this->assertEquals(array(1), $event->triggeredEvent);

        $event = new Event();
        $event->triggeredEvent = [];
        $dispatcher->dispatch('testevent', $event);
        $this->assertEquals(array(1), $event->triggeredEvent);

    }

    public function test_removeGroupListener()
    {
        myEventDispatch::reset();
        $dispatcher = myEventDispatch::getDispatcher();
        $event = new Event();
        $event->triggeredEvent = [];
        $uniqueId1 = md5(rand().microtime());

        $uniqueId2 = md5(rand().microtime());

        $dispatcher->addListener('testevent', function(Event $event) {
            $event->triggeredEvent[] = 1;
        }, $uniqueId1);

        $dispatcher->addListener('testevent', function(Event $event) {
            $event->triggeredEvent[] = 2;
        }, $uniqueId2);

        $dispatcher->addListener('testevent', function(Event $event) {
            $event->triggeredEvent[] = 1;
        }, $uniqueId1);

        $dispatcher->addListener('testevent', function(Event $event) {
            $event->triggeredEvent[] = 2;
        }, $uniqueId2);

        $dispatcher->removeGroupListener($uniqueId1);
        $dispatcher->dispatch('testevent', $event);
        $this->assertEquals(array(2, 2), $event->triggeredEvent);

    }

}
