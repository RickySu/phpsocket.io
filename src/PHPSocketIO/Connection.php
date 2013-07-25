<?php
namespace PHPSocketIO;

class Connection
{
    const READ_BUFFER_SIZE = 1024;

    protected $baseEvent;
    protected $eventBufferEvent;

    protected $request;

    protected $namespace;

    protected $timeoutEvent;

    protected $shutdownAfterSend = false;

    protected $onReceiveCallbacks=[];
    protected $onWriteBufferEmptyCallbacks=[];

    protected $eventBufferEventGCCallback;

    protected $remote;

    /**
     *
     * @var Adapter\ProtocolProcessorInterface
     */
    protected $protocolProcessor;

    public function __construct(\EventBase $baseEvent, \EventHttpRequest $request, $namespace, $eventBufferEventGCCallback)
    {
        $this->baseEvent = $baseEvent;
        $this->namespace = $namespace;
        $this->eventBufferEventGCCallback = $eventBufferEventGCCallback;
        $this->request = $request;
    }

    public function parseHTTP()
    {
        new Http\Http($this);
    }

    public function sendResponse(Http\Response $response)
    {
        $buffer = $this->request->getOutputBuffer();
        $buffer->add($response->getContent());
        $this->request->sendReply($response->getStatusCode(), $response->getStatusCode());
    }

    protected function getEventBufferEvent()
    {
        if(!$this->eventBufferEvent){
            $this->eventBufferEvent = $this->request->getEventBufferEvent();
            $this->eventBufferEvent->setCallbacks(function(){
            }, function(){
                if($this->shutdownAfterSend){
                    $this->request->sendReplyEnd();
                    call_user_func($this->eventBufferEventGCCallback, $this->eventBufferEvent);
                }
            }, function(){
            });
            $this->eventBufferEvent->enable(\Event::READ | \Event::WRITE);
        }
        return $this->eventBufferEvent;
    }
    public function write(Http\Response $response, $shutdownAfterSend = false)
    {
        $this->shutdownAfterSend = $shutdownAfterSend;
        $this->getEventBufferEvent()->write($response);
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getRemote()
    {
        if(!$this->remote){
            $this->request->getEventHttpConnection()->getPeer($address, $port);
            $this->remote = array($address, $port);
        }
        return $this->remote;
    }

    public function getNamespace(){
        return $this->namespace;
    }

    public function free()
    {
        if ($this->eventBufferEvent) {
            call_user_func($this->eventBufferEventGCCallback, $this->eventBufferEvent);
        }
        $this->clearTimeout();
        $this->unregisterEvent();
        $this->baseEvent = null;
        $this->eventBufferEvent = null;
        $this->request = null;
        $this->onReceiveCallbacks = null;
        $this->onWriteBufferEmptyCallbacks = null;
        $this->eventBufferEventGCCallback = null;

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
