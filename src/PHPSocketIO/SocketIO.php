<?php
namespace PHPSocketIO;

class SocketIO
{
    protected $baseEvent;
    protected $eventListener;
    protected $listenHost;
    protected $listenPort;

    protected $onConnectCallback;

    protected $namespace = 'socket.io';

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
        $this->onConnectCallback = $callback;
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
        $this->registGC();
    }

    protected function registGC()
    {
        $timeoutEvent = new \Event($this->baseEvent, -1, \Event::TIMEOUT|\Event::PERSIST, function($fd, $what, $event){
            gc_collect_cycles();
        });
        $timeoutEvent->data = $timeoutEvent;
        $timeoutEvent->addTimer(60);
    }

    protected function doAccept(\EventListener $eventListener, $socket, $address, $ctx)
    {
        try{
            $connection = new Connection($this->baseEvent, $socket, $address, $this->namespace);
            call_user_func($this->onConnectCallback, $connection);
        }
        catch(\Exception $e){
            $connection->write(new HTTP\Response($e->getMessage(), $e->getCode()), true);
        }
    }

    public function __destruct()
    {
        $this->baseEvent = null;
        $this->eventListener = null;
        $this->onConnectCallback = null;
    }
}
