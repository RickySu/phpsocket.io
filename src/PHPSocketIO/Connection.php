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

    protected $registedEvent = array();

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
        $this->setProtocolProcessor(new Http\Http($this));
    }

    public function getNamespace(){
        return $this->namespace;
    }

    public function setProtocolProcessor(Http\ProtocolProcessorInterface $processor)
    {
        if($this->protocolProcessor !== null){
            $processor->setHeader($this->protocolProcessor->getHeader());
            $processor->init();
        }
        $this->protocolProcessor = $processor;
    }

    protected function prepareEvent()
    {
        $this->eventBufferEvent = new \EventBufferEvent(
            $this->baseEvent,
            $this->socket,
            \EventBufferEvent::OPT_CLOSE_ON_FREE,
            function($eventBufferEvent, $arg){
                $this->onReceive($eventBufferEvent, $arg);
            }, function($eventBufferEvent, $arg){
                $this->onWriteBufferEmpty($eventBufferEvent, $arg);
            }, function($eventBufferEvent, $events, $ctx){
                $this->onEvent($eventBufferEvent, $events, $ctx);
            });
        $this->eventBufferEvent->setWatermark(\Event::WRITE, 0, 0);
        $this->eventBufferEvent->enable(\Event::READ | \Event::WRITE);
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

    public function write($response, $shutdownAfterSend = false)
    {
        $this->eventBufferEvent->write($response);
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
        $this->protocolProcessor->free();
        $this->protocolProcessor = null;
        $this->clearTimeout();
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
        $this->shutdown();
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function on($eventName, $callback)
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->addListener($eventName, $callback, $this->address);
        $this->registedEvent[] = $eventName;
    }

    public function onRequest($requestCallback)
    {
        $this->protocolProcessor->onRequest($requestCallback);
    }

    protected function unregisterEvent()
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        foreach($this->registedEvent as $eventName){
            $dispatcher->removeListener($eventName, $this->address);
        }
    }

}
