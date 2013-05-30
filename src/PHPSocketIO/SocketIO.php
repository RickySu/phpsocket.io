<?php
namespace PHPSocketIO;

class SocketIO
{
    protected $baseEvent;
    protected $eventListener;
    protected $listenHost;
    protected $listenPort;

    protected $onConnectCallbacks = [];

    public function __construct(\EventBase $baseEvent = null)
    {
        if ($baseEvent === null) {
            $baseEvent = new \EventBase();
        }
        $this->baseEvent = $baseEvent;
    }

    public function listen($host, $port = null)
    {
        if ($port === null) {
            $port = $host;
            $host = '0.0.0.0';
        }
        $this->listenHost = $host;
        $this->listenPort = $port;

        return $this;
    }

    public function dispatch()
    {
        $this->createEventListener();
        $this->baseEvent->dispatch();
    }

    public function onConnect($callback)
    {
        $this->onConnectCallbacks[] = $callback;

        return $this;
    }

    protected function createEventListener()
    {
        $this->eventListener = new \EventListener($this->baseEvent,
            function($eventListener, $socket, $address, $ctx){
                $this->doAccept($eventListener, $socket, $address, $ctx);
            },
            $this->baseEvent,
            \EventListener::OPT_CLOSE_ON_FREE | \EventListener::OPT_REUSEABLE,
            -1,
            "{$this->listenHost}:{$this->listenPort}"
        );
    }

    protected function doAccept(\EventListener $eventListener, $socket, $address, $ctx)
    {
        $connection = new Connection($this->baseEvent, $socket, $address);
        foreach ($this->onConnectCallbacks as $onConnectCallback) {
            call_user_func($onConnectCallback, $connection);
        }
    }

    public function __destruct()
    {
        $this->baseEvent = null;
        $this->eventListener = null;
        $this->onConnectCallbacks = null;
    }
}
