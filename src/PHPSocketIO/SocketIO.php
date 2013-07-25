<?php
namespace PHPSocketIO;

class SocketIO
{
    protected $baseEvent;
    protected $eventHttp;
    protected $listenHost;
    protected $listenPort;

    protected $eventBufferEvents=array();

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

    protected function eventBufferEventGc()
    {
        foreach($this->eventBufferEvents as $eventBufferEvent){
            $eventBufferEvent->setCallbacks(null, null, null);
            $eventBufferEvent->free();
        }
        $this->eventBufferEvents=array();
    }

    public function dispatch()
    {
        $this->createEventListener();
        while(true){
            if($this->baseEvent->gotExit()){
                break;
            }
            $this->baseEvent->dispatch();
            $this->eventBufferEventGc();
        }
    }

    public function onConnect($callback)
    {
        $this->onConnectCallback = $callback;
        return $this;
    }

    protected function createEventListener()
    {
        $this->eventHttp = new \EventHttp($this->baseEvent);
        $this->eventHttp->bind($this->listenHost, $this->listenPort);
        $this->eventHttp->setDefaultCallback(function($request){
            $connection = new Connection($this->baseEvent, $request, $this->namespace, function(\EventBufferEvent $event){
                $this->eventBufferEvents[]=$event;
                $this->baseEvent->stop();
            });
            call_user_func($this->onConnectCallback, $connection);
            $connection->parseHTTP();
        });
    }

    public function __destruct()
    {
        $this->baseEvent = null;
        $this->eventListener = null;
        $this->onConnectCallback = null;
    }
}
