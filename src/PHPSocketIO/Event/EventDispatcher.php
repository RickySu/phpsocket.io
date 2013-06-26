<?php
namespace PHPSocketIO\Event;

use Symfony\Component\EventDispatcher\Event;
use PHPSocketIO\Connection;

class EventDispatcher
{
    protected $events = array();
    protected $groupEvents = array();
    static protected $dispatcher = null;

    /**
     *
     * @return EventDispatcher
     */
    public static function getDispatcher()
    {
        if(self::$dispatcher === null){
            self::$dispatcher = new static();
        }
        return self::$dispatcher;
    }

    protected function brocast($eventName, Event $event = null) {
        foreach($this->events[$eventName] as &$eventGroup){
            foreach($eventGroup as $event){
                $listener($event);
                if($event && $event->isPropagationStopped()){
                    return;
                }
            }
        }
    }

    public function dispatch($eventName, Event $event = null, Connection $connection = null) {
        if($connection === null)
        {
            $this->brocast($eventName, $event);
            return;
        }
        if(!isset($this->events[$eventName][$connection->getAddress()])){
            return;
        }

        foreach($this->events[$eventName][$connection->getAddress()] as &$listener){
            $listener($event);
            if($event && $event->isPropagationStopped()){
                return;
            }
        }

    }

    public function addListener($eventName, $listener, Connection $connection) {
        $this->events[$eventName][$connection->getAddress()][] = $listener;
        $this->groupEvents[$connection->getAddress()][$eventName]=true;
    }

    public function removeGroupListener(Connection $connection)
    {
        if(!isset($this->groupEvents[$connection->getAddress()])){
            return;
        }
        foreach($this->groupEvents[$connection->getAddress()] as $eventName => $tmp){
            $this->removeListener($eventName, $connection);
        }
        unset($this->groupEvents[$connection->getAddress()]);
    }

    public function removeListener($eventName, Connection $connection, $listener = null) {
        if(!isset($this->events[$eventName])){
            return;
        }

        if($listener !== null){
            if(false !== ($key = array_search($listener, $this->events[$eventName][$connection->getAddress()], true))){
                unset($this->events[$eventName][$connection->getAddress()][$key]);
            }
            return;
        }
        unset($this->events[$eventName][$connection->getAddress()]);
    }
}