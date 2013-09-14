<?php
namespace PHPSocketIO;

class Connection implements ConnectionInterface
{
    const MAX_INPUT = 4000000;   // 4MB

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
        $this->setEventHTTPRequest($eventHTTPRequest);
        $headers = $eventHTTPRequest->getInputHeaders();
        $server = array(
            'REQUEST_URI' => $eventHTTPRequest->getUri(),
        );
        list($server['REMOTE_ADDR'], $server['REMOTE_PORT']) = $this->getRemote();
        $server['REQUEST_METHOD'] = array_search($eventHTTPRequest->getCommand(), array(
            'GET' => \EventHttpRequest::CMD_GET ,
            'POST' => \EventHttpRequest::CMD_POST ,
            'HEAD' => \EventHttpRequest::CMD_HEAD ,
            'PUT' => \EventHttpRequest::CMD_PUT ,
            'DELETE' => \EventHttpRequest::CMD_DELETE ,
            'OPTIONS' => \EventHttpRequest::CMD_OPTIONS ,
            'TRACE ' => \EventHttpRequest::CMD_TRACE ,
            'CONNECT ' => \EventHttpRequest::CMD_CONNECT ,
            'PATCH ' => \EventHttpRequest::CMD_PATCH ,
        ));
        $this->request = Http\Http::parseRequest(
                $server,
                $headers,
                $eventHTTPRequest->getOutputBuffer()->read(static::MAX_INPUT));
        Http\Http::handleRequest($this, $this->request);
    }

    public function setEventHTTPRequest(\eventHTTPRequest $eventHTTPRequest)
    {
        $this->eventHTTPRequest = $eventHTTPRequest;
    }

    public function sendResponse(Response\Response $response)
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
                $data  = $this->eventBufferEvent->read(4096);
                file_put_contents('/home/ricky/packet', $data);
                $dispatcher = Event\EventDispatcher::getDispatcher();
                $messageEvent = new Event\MessageEvent($data, $this);
                $dispatcher->dispatch("socket.receive", $messageEvent, $this);
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

    public function write(Response\ResponseInterface $response, $shutdownAfterSend = false)
    {
        $this->shutdownAfterSend = $shutdownAfterSend;
        $this->getEventBufferEvent()->write($response);
    }

    /**
     *
     * @return Request\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function getRemote()
    {
        if(!$this->remote && $this->eventHTTPRequest){
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
        if($this->request && $this->request->getSession()){
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
        return $this;
    }

    public function emit($eventName, $message)
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->dispatch(
            "server.emit",
            new Event\MessageEvent(array(
                'event' => $eventName,
                'message' => $message,
            ), $this),
            $this
        );
    }

    public function onRecieve($callback)
    {
        $this->onReceiveCallbacks[]=$callback;
        return $this;
    }

    public function onWriteBufferEmpty($callback)
    {
        $this->onWriteBufferEmptyCallbacks[]=$callback;
        return $this;
    }

    protected function unregisterEvent()
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->removeGroupListener($this);
    }

}
