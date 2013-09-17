<?php
namespace PHPSocketIO;

class SocketIOSocket
{
    protected $endpoint;

    public function __construct($endpoint = null)
    {
        $this->endpoint = $endpoint;
    }

    public function on($eventName, $callback)
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->addListener("client.$eventName.{$this->endpoint}", $callback);
        return $this;
    }

    public function emit($eventName, $message)
    {
        $messageEvent = new Event\MessageEvent();
        $messageEvent->setMessage(array(
                'event' => $eventName,
                'message' => $message,
            ));
        $messageEvent->setEndpoint($this->endpoint);
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->dispatch("server.emit", $messageEvent);
        return $this;
    }

}
