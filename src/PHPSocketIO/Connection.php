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

    /**
     *
     * @var Adapter\ProtocolProcessorInterface
     */
    protected $protocolProcessor;

    public function __construct(\EventBase $baseEvent, $socket, $address)
    {
        $this->baseEvent = $baseEvent;
        $this->socket = $socket;
        $this->address = $address;
        $this->setProtocolProcessor(new Adapter\Http($this));
        $this->prepareEvent();
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
/*        $message = "test".rand();
        $Header  = "HTTP/1.0 200 OK\r\n";
        $Header .= "Server: noname\r\n";
        $Header .= "Content-Length: ".strlen($message)."\r\n";
        $Header .= "Connection: close\r\n";
        $Header .= "Content-Type: text/plain\r\n\r\n";
        $Header .= $message;
        $eventBufferEvent->write($Header);
        $this->shutdownAfterSend = true;*/
        $this->protocolProcessor->onReceive($receiveMessage);
    }

    public function write($message, $shutdownAfterSend = false)
    {
        $this->eventBufferEvent->write($message);
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
    }

    public function __destruct()
    {
        $this->shutdown();
    }

}
