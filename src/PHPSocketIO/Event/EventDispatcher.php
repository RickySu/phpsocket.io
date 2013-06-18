<?php
namespace PHPSocketIO\Event;

use Symfony\Component\EventDispatcher\EventDispatcher as BaseEventDispatcher;
use Symfony\Component\EventDispatcher\Event;

class EventDispatcher
{
    protected $events = array();
    static protected $dispatcher = null;

    /**
     *
     * @return EventDispatcher
     */
    public static function getDispatcher()
    {
        if(static::$dispatcher === null){
            static::$dispatcher = new static();
        }
        return static::$dispatcher;
    }

    public function dispatch($eventName, Event $event = null) {
        foreach($this->events[$eventName] as $listener){
            if($listener) $listener($event);
        }
    }

    public function addListener($eventName, $listener, $group) {
        $this->events[$eventName][$group] = $listener;
    }

    public function removeListener($eventName, $group) {
        unset($this->events[$eventName][$group]);
    }
}