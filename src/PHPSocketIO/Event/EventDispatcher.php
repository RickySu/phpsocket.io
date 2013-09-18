<?php

namespace PHPSocketIO\Event;

use Symfony\Component\EventDispatcher\Event;

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
        if (self::$dispatcher === null) {
            self::$dispatcher = new static();
        }
        return self::$dispatcher;
    }

    protected function brocast(Event $event)
    {
        $eventName = $event->getName();
        $listeners = [];
        foreach ($this->events[$eventName] as &$eventGroup) {
            foreach ($eventGroup as $listenerArray) {
                list($listener, $priority) = $listenerArray;
                $listeners[$priority][] = $listener;
            }
        }
        krsort($listeners);
        $processCount = 0;
        foreach($listeners as $listener){
            foreach($listener as $callback){
                if ($event && $event->isPropagationStopped()) {
                    return $processCount;
                }
                $callback($event);
                $processCount++;
            }
        }
        return $processCount;
    }

    public function dispatch($eventName, Event $event = null, $group = null)
    {
        if (!isset($this->events[$eventName])) {
            return 0;
        }

        if (!$event) {
            $event = new Event();
        }

        $event->setName($eventName);

        if ($group === null) {
            return $this->brocast($event);
        }

        if (!isset($this->groupEvents[$group][$eventName])) {
            return 0;
        }

        $listeners=[];
        foreach ($this->groupEvents[$group][$eventName] as $uniqueKey) {
            if(isset($this->events[$eventName]) && isset($this->events[$eventName][$uniqueKey])){
                foreach($this->events[$eventName][$uniqueKey] as $listenerArray){
                    list($listener, $priority) = $listenerArray;
                    $listeners[$priority][] = $listener;
                }
            }
        }
        krsort($listeners);
        $processCount = 0;
        foreach($listeners as $listener){
            foreach($listener as $callback){
                if ($event && $event->isPropagationStopped()) {
                    return $processCount;
                }
                $callback($event);
                $processCount++;
            }
        }
        return $processCount;
    }

    public function addListener($eventName, $listener, $groups = null, $priority = 0)
    {
        $uniqueKey = sha1(microtime().rand().rand(), true);
        if(!is_array($groups)){
            $groups = [$groups];
        }
        $groups = array_unique($groups);
        foreach($groups as $group){
            if (!isset($this->events[$eventName][$group])) {
                $this->events[$eventName][$group] = array();
            }
            $this->groupEvents[$group][$eventName][] = $uniqueKey;
        }

        $this->events[$eventName][$uniqueKey][] = array($listener, $priority);

        return true;
    }

    public function removeGroupListener($group)
    {
        if (!isset($this->groupEvents[$group])) {
            return;
        }
        foreach ($this->groupEvents[$group] as $eventName => $tmp) {
            $this->removeListener($eventName, $group);
        }
        unset($this->groupEvents[$group]);
    }

    public function removeListener($eventName, $group)
    {
        if (!isset($this->events[$eventName])) {
            return;
        }
        foreach($this->groupEvents[$group][$eventName] as $uniqueKey){
            unset($this->events[$eventName][$uniqueKey]);
        }
        unset($this->groupEvents[$group][$eventName]);
        if(count($this->groupEvents[$group]) == 0){
            unset($this->groupEvents[$group]);
        }
    }

}