<?php
namespace PHPSocketIO;

class Connection
{
    const READ_BUFFER_SIZE = 1024;

    protected $address;
    protected $baseEvent;
    protected $socket;
    protected $eventBufferEvent;
    protected $shutdownAfterSend = false;

    protected $namespace;

    protected $timeoutEvent;

    protected $onReceiveCallbacks=[];
    protected $onWriteBufferEmptyCallbacks=[];

    /**
     *
     * @var Adapter\ProtocolProcessorInterface
     */
    protected $protocolProcessor;

    public function __construct(\EventBase $baseEvent, $socket, $address, $namespace)
    {
        $this->baseEvent = $baseEvent;
        $this->socket = $socket;
        $this->address = implode(':', $address);
        $this->namespace = $namespace;
        $this->prepareEvent();
        new Http\Http($this);
    }

    public function getNamespace(){
        return $this->namespace;
    }

    public function prepareProcessor()
    {
    }

    protected function prepareEvent()
    {
        $this->eventBufferEvent = new \EventBufferEvent(
            $this->baseEvent,
            $this->socket,
            \EventBufferEvent::OPT_CLOSE_ON_FREE,
            function($eventBufferEvent, $arg){
                $this->onReadCallback($eventBufferEvent, $arg);
            }, function($eventBufferEvent, $arg){
                $this->onWriteBufferEmptyCallback($eventBufferEvent, $arg);
            }, function($eventBufferEvent, $events, $ctx){
                $this->onEventCallback($eventBufferEvent, $events, $ctx);
            });
        $this->eventBufferEvent->setWatermark(\Event::WRITE, 0, 0);
        $this->eventBufferEvent->enable(\Event::READ | \Event::WRITE);
    }

    protected function onEventCallback(\EventBufferEvent $eventBufferEvent, $events, $ctx)
    {
        if ($events & (\EventBufferEvent::EOF | \EventBufferEvent::ERROR)) {
            $this->free();
        }
    }

    protected function onReadCallback(\EventBufferEvent $eventBufferEvent, $arg)
    {
        $receiveMessage = $eventBufferEvent->input->read(self::READ_BUFFER_SIZE);
        $event = new Event\ReceiveEvent($this, $receiveMessage);
        foreach($this->onReceiveCallbacks as $callback){
            $callback($event);
            if($event->isPropagationStopped()){
                return;
            }
        }
    }

    public function write($response, $shutdownAfterSend = false)
    {
        $this->eventBufferEvent->write($response);
        $this->shutdownAfterSend = $shutdownAfterSend;
    }

    protected function onWriteBufferEmptyCallback(\EventBufferEvent $eventBufferEvent, $arg)
    {
        if ($this->shutdownAfterSend) {
            $this->free();
            return;
        }
        foreach($this->onWriteBufferEmptyCallbacks as $callback){
            $callback();
        }
    }

    public function free()
    {
        if (!$this->eventBufferEvent) {
            return;
        }
        $this->eventBufferEvent->free();
        $this->eventBufferEvent = null;
        $this->baseEvent = null;
        $this->socket = null;
        $this->clearTimeout();
        $this->onReceiveCallbacks = null;
        $this->onWriteBufferEmptyCallbacks = null;
        $this->unregisterEvent();
    }

    public function emit($event, $data)
    {
        Event\Dispatcher::brocastToClient($event, $data);
    }

    public function setTimeout($timer, $callback)
    {
         $this->timeoutEvent = new \Event($this->baseEvent, -1, \Event::TIMEOUT, function($fd, $what, $event) use($callback){
             $callback();
             $this->clearTimeout();
         });
         $this->timeoutEvent->data = $this->timeoutEvent;
         $this->timeoutEvent->addTimer($timer);
    }

    public function clearTimeout()
    {
        if($this->timeoutEvent === null){
            return;
        }
        $this->timeoutEvent->data = null;
        $this->timeoutEvent->free();
        $this->timeoutEvent = null;
    }

    public function __destruct()
    {
        $this->free();
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function on($eventName, $callback)
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->addListener($eventName, $callback, $this);
    }

    public function onRecieve($callback)
    {
        $this->onReceiveCallbacks[]=$callback;
    }

    public function onWriteBufferEmpty($callback)
    {
        $this->onWriteBufferEmptyCallbacks[]=$callback;
    }

    protected function unregisterEvent()
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->removeGroupListener($this);
    }

}
