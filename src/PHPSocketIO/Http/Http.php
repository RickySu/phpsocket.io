<?php
namespace PHPSocketIO\Http;

use PHPSocketIO\Connection;
use PHPSocketIO\Event;

class Http implements ProtocolProcessorInterface
{

    protected $requestParser;
    protected $requestCb;

    public function __construct(Connection $connection)
    {
        $this->requestParser = new Parser\RequestParser(function(Request $request) use($connection){
            unset($this->requestParser);
            call_user_func($this->requestCb, new Event\RequestEvent($connection, $request));
        });
    }

    public function onRequest($requestCb)
    {
        $this->requestCb = $requestCb;
    }

    public function onReceive($reveiceMessage)
    {
        $this->requestParser->onReceive($reveiceMessage);
    }

    public function onWriteBufferEmpty()
    {

    }

    public function free()
    {
        $this->requestParser = null;
        $this->requestCb = null;
    }
    public function __destruct()
    {
        $this->free();
    }

}
