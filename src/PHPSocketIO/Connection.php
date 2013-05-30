<?php
namespace PHPSocketIO;

class Connection
{
    const READ_BUFFER_SIZE = 1024;
    const STOP_EVENT_PROPAGATE = 'STOP';
    const EVENT_HTTP_REQUEST= 'http.request';
    protected $address;
    protected $baseEvent;
    protected $socket;
    protected $eventBufferEvent;
    protected $shutdownAfterSend = false;

    protected $events;

    protected $namespace;

    /**
     *
     * @var Adapter\ProtocolProcessorInterface
     */
    protected $protocolProcessor;

    public function __construct(\EventBase $baseEvent, $socket, $address, $namespace)
    {
        $this->baseEvent = $baseEvent;
        $this->socket = $socket;
        $this->address = $address;
        $this->namespace = $namespace;
        $this->setProtocolProcessor(new Adapter\Http($this));
        $this->prepareEvent();
        $this->prepareHandshake();
    }

    public function getNamespace(){
        return $this->namespace;
    }
    protected function prepareHandshake()
    {
        $this->on(self::EVENT_HTTP_REQUEST, array(new Handshake(), 'onRequest'));
    }

    public function setProtocolProcessor(Adapter\ProtocolProcessorInterface $processor)
    {
        $this->protocolProcessor = $processor;
    }

    protected function prepareEvent()
    {
        $this->eventBufferEvent = new \EventBufferEvent($this->baseEvent, $this->socket, \EventBufferEvent::OPT_CLOSE_ON_FREE);
        $this->eventBufferEvent->setCallbacks(function($eventBufferEvent, $arg){
            $this->onReceive($eventBufferEvent, $arg);
        }, function($eventBufferEvent, $arg){
            $this->onWriteBufferEmpty($eventBufferEvent, $arg);
        }, function($eventBufferEvent, $events, $ctx){
            $this->onEvent($eventBufferEvent, $events, $ctx);
        }, null);
        $this->eventBufferEvent->enable(\Event::READ | \Event::WRITE);
        $this->eventBufferEvent->setWatermark(\Event::WRITE, 0, 0);
    }

    protected function onEvent(\EventBufferEvent $eventBufferEvent, $events, $ctx)
    {
        if ($events & (\EventBufferEvent::EOF | \EventBufferEvent::ERROR)) {
            $this->shutdown();
        }
    }

    protected function onReceive(\EventBufferEvent $eventBufferEvent, $arg)
    {
        $receiveMessage = $eventBufferEvent->input->read(self::READ_BUFFER_SIZE);
        $this->protocolProcessor->onReceive($receiveMessage);
    }

    public function write($message, $shutdownAfterSend = false)
    {
        $this->eventBufferEvent->write($message);
        $this->shutdownAfterSend = $shutdownAfterSend;
    }

    protected function onWriteBufferEmpty(\EventBufferEvent $eventBufferEvent, $arg)
    {
        if ($this->shutdownAfterSend) {
            $this->shutdown();
            return;
        }
        $this->protocolProcessor->onWriteBufferEmpty();
    }

    public function shutdown()
    {
        if (!$this->eventBufferEvent) {
            return;
        }
        $this->eventBufferEvent->free();
        $this->eventBufferEvent = null;
        $this->baseEvent = null;
        $this->socket = null;
        $this->protocolProcessor = null;
        $this->events = null;
    }

    public function on($event, $callback)
    {
        $this->events[$event][] = $callback;
    }

    public function trigger($event, Connection $connection, $data)
    {
        if(!isset($this->events[$event])){
            return;
        }

        foreach($this->events[$event] as $callback){
            if(call_user_func($callback, $connection, $data) === self::STOP_EVENT_PROPAGATE){
                break;
            }
        }
    }

    public function __destruct()
    {
        $this->shutdown();
    }

}
