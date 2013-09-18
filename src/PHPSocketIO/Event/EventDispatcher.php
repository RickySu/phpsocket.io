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
        foreach ($this->events[$eventName] as &$eventGroup) {
            foreach ($eventGroup as &$listener) {
                if ($event && $event->isPropagationStopped()) {
                    return;
                }
                $listener($event);
            }
        }
    }

    public function dispatch($eventName, Event $event = null, $uniqueId = null)
    {
        if (!isset($this->events[$eventName])) {
            return;
        }

        if (!$event) {
            $event = new Event();
        }

        $event->setName($eventName);

        if ($uniqueId === null) {
            $this->brocast($event);
            return;
        }

        if (!isset($this->events[$eventName][$uniqueId])) {
            return;
        }
        foreach ($this->events[$eventName][$uniqueId] as &$listener) {
            if ($event && $event->isPropagationStopped()) {
                return;
            }
            $listener($event);
        }
    }

    public function addListener($eventName, $listener, $uniqueId = null, $highPriority = false)
    {
        if (!isset($this->events[$eventName][$uniqueId])) {
            $this->events[$eventName][$uniqueId] = array();
        }

        if ($highPriority) {
            array_unshift($this->events[$eventName][$uniqueId], $listener);
        } else {
            array_push($this->events[$eventName][$uniqueId], $listener);
        }
        $this->groupEvents[$uniqueId][$eventName] = true;
        return true;
    }

    public function removeGroupListener($uniqueId)
    {
        if (!isset($this->groupEvents[$uniqueId])) {
            return;
        }
        foreach ($this->groupEvents[$uniqueId] as $eventName => $tmp) {
            $this->removeListener($eventName, $uniqueId);
        }
        unset($this->groupEvents[$uniqueId]);
    }

    protected function removeListener($eventName, $uniqueId = null, $listener = null)
    {
        if (!isset($this->events[$eventName])) {
            return;
        }

        if ($listener !== null) {
            if (false !== ($key = array_search($listener, $this->events[$eventName][$uniqueId], true))) {
                unset($this->events[$eventName][$uniqueId][$key]);
            }
            return;
        }
        unset($this->events[$eventName][$uniqueId]);
    }

}