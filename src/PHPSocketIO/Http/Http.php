<?php
namespace PHPSocketIO\Http;

use PHPSocketIO\Connection;
use PHPSocketIO\Event;

class Http implements ProtocolProcessorInterface
{

    public function __construct(Connection $connection)
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->addListener('request', function(Event\RequestEvent $requestEvent){
            $this->onRequest($requestEvent);
        }, $connection);
        new Parser\RequestParser($connection);
    }

    public function onRequest(Event\RequestEvent $requestEvent)
    {
        $handshakeResult = Handshake::initialize($requestEvent->getConnection(), $requestEvent->getRequest());
        if($handshakeResult === Handshake::PROTOCOL_HTMLFILE){
            return;
        }
        $requestEvent->stopPropagation();
        if($handshakeResult instanceof Response){
            $requestEvent->getConnection()->write($handshakeResult, true);
            return;
        }
    }

}
