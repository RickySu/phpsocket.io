<?php
namespace PHPSocketIO;

class Connection
{
    const READ_BUFFER_SIZE = 1024;

    protected $baseEvent;
    protected $eventBufferEvent = null;

    protected $request;
    protected $eventHTTPRequest;
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

    public function __construct(\EventBase $baseEvent, $namespace, $eventBufferEventGCCallback)
    {
        $this->baseEvent = $baseEvent;
        $this->namespace = $namespace;
        $this->eventBufferEventGCCallback = $eventBufferEventGCCallback;
    }

    public function parseHTTP(\EventHttpRequest $eventHTTPRequest)
    {
        $this->eventHTTPRequest = $eventHTTPRequest;
        $this->request = Http\Http::init($this, $eventHTTPRequest);
        Http\Http::handleRequest($this, $this->request);
    }

    public function sendResponse(Http\Response $response)
    {
        $buffer = $this->eventHTTPRequest->getOutputBuffer();
        $buffer->add($response->getContent());
        $this->eventHTTPRequest->sendReply($response->getStatusCode(), $response->getStatusCode());
        $this->eventHTTPRequest->free();
    }

    protected function getEventBufferEvent()
    {
        if(!$this->eventBufferEvent){
            $this->eventBufferEvent = $this->eventHTTPRequest->getEventBufferEvent();
            $this->eventBufferEvent->setCallbacks(function(){
            }, function(){
                if($this->shutdownAfterSend){
                    $this->free();
                }
            }, function(){
            });
            $this->eventBufferEvent->enable(\Event::READ | \Event::WRITE);
        }
        return $this->eventBufferEvent;
    }
    public function write(Http\ResponseInterface $response, $shutdownAfterSend = false)
    {
        $this->shutdownAfterSend = $shutdownAfterSend;
        $this->getEventBufferEvent()->write($response);
    }

    /**
     *
     * @return Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function getRemote()
    {
        if(!$this->remote){
            $this->eventHTTPRequest->getEventHttpConnection()->getPeer($address, $port);
            $this->remote = array($address, $port);
        }
        return $this->remote;
    }

    public function getNamespace(){
        return $this->namespace;
    }

    public function free()
    {
        if(!$this->baseEvent){
            return;
        }
        $this->clearTimeout();
        if($this->request->getSession()){
           $this->request->getSession()->save();
        }
        if($this->eventBufferEvent){
            call_user_func($this->eventBufferEventGCCallback, $this->eventBufferEvent, $this->eventHTTPRequest);
        }
        $this->baseEvent = null;
        $this->eventBufferEvent = null;
        $this->request = null;
        $this->eventHTTPRequest = null;
        $this->onReceiveCallbacks = null;
        $this->onWriteBufferEmptyCallbacks = null;
        $this->eventBufferEventGCCallback = null;
        $this->onReceiveCallbacks = null;
        $this->unregisterEvent();
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
        $dispatcher->addListener("client.$eventName", $callback, $this);
    }

    public function emit($eventName, $message)
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->dispatch(
            "server.emit",
            new Event\MessageEvent(array(
                'event' => $eventName,
                'message' => $message,
            )),
            $this
        );
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
