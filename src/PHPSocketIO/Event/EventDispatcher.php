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
            foreach($eventGroup as &$listener){
                $listener($event);
                if($event && $event->isPropagationStopped()){
                    return;
                }
            }
        }
    }

    public function dispatch($eventName, Event $event = null, Connection $connection = null) {
        if(!isset($this->events[$eventName])){
            return;
        }

        if(!$event){
            $event = new Event();
        }

        $event->setName($eventName);

        if($connection === null)
        {
            $this->brocast($eventName, $event);
            return;
        }

        list($address, $port) = $connection->getRemote();
        if(!isset($this->events[$eventName]["$address:$port"])){
            return;
        }
        foreach($this->events[$eventName]["$address:$port"] as &$listener){
            $listener($event);
            if($event && $event->isPropagationStopped()){
                return;
            }
        }
    }

    public function addListener($eventName, $listener, Connection $connection = null, $highPriority = false)
    {
        if($connection){
            list($address, $port) = $connection->getRemote();
        }
        else{
            $address = $port = '';
        }
        if(!isset($this->events[$eventName]["$address:$port"])){
            $this->events[$eventName]["$address:$port"]=array();
        }

        if($highPriority){
            array_unshift($this->events[$eventName]["$address:$port"], $listener);
        }
        else{
            array_push($this->events[$eventName]["$address:$port"], $listener);
        }
        $this->groupEvents["$address:$port"][$eventName]=true;
    }

    public function removeGroupListener(Connection $connection)
    {
        list($address, $port) = $connection->getRemote();
        if(!isset($this->groupEvents["$address:$port"])){
            return;
        }
        foreach($this->groupEvents["$address:$port"] as $eventName => $tmp){
            $this->removeListener($eventName, $connection);
        }
        unset($this->groupEvents["$address:$port"]);
    }

    public function removeListener($eventName, Connection $connection = null, $listener = null) {
        if(!isset($this->events[$eventName])){
            return;
        }

        if($connection){
            list($address, $port) = $connection->getRemote();
        }
        else{
            $address = $port = '';
        }

        if($listener !== null){
            if(false !== ($key = array_search($listener, $this->events[$eventName]["$address:$port"], true))){
                unset($this->events[$eventName]["$address:$port"][$key]);
            }
            return;
        }
        unset($this->events[$eventName]["$address:$port"]);
    }
}