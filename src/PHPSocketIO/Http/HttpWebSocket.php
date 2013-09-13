<?php

namespace PHPSocketIO\Http;

use PHPSocketIO\Connection;
use PHPSocketIO\Protocol\Builder as ProtocolBuilder;
use PHPSocketIO\Event;
use PHPSocketIO\Response\ResponseWebSocketFrame;
use PHPSocketIO\Protocol\Handshake;

class HttpWebSocket
{

    protected $heartbeatTimeout = 30;
    protected $websocket;
    protected $connection;

    public function __construct(Connection $connection, $sessionInited)
    {
        $this->connection = $connection;
        $this->websocket = new WebSocket\WebSocket();
        if (!($handshakeResponse = $this->websocket->getHandshakeReponse($connection->getRequest()))) {
            $this->connection->write(new Response('bad protocol', 400), true);
            return;
        }
        $this->connection->write($handshakeResponse);
        $this->sendData(ProtocolBuilder::Connect());
        $this->initEvent();
        $this->setHeartbeatTimeout();
    }

    protected function setHeartbeatTimeout()
    {
        $this->connection->clearTimeout();
        $this->connection->setTimeout($this->heartbeatTimeout, function(){
            $this->sendData(ProtocolBuilder::Heartbeat());
        });
    }

    protected function sendData($data)
    {
        $this->connection->write(new ResponseWebSocketFrame(new WebSocket\Frame($data)));
        $this->setHeartbeatTimeout();
    }

    protected function initEvent()
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->addListener("socket.receive", function(Event\MessageEvent $messageEvent) {
                    $message = $messageEvent->getMessage();
                    if(!($frame = $this->websocket->onMessage($message))){
                        return;
                    }
                    Handshake::processProtocol($frame->getData(), $this->connection);
                }, $this->connection);

        $dispatcher->addListener("server.emit", function(Event\MessageEvent $messageEvent) {
                    $message = $messageEvent->getMessage();
                    $this->sendData(ProtocolBuilder::Event(array(
                                'name' => $message['event'],
                                'args' => array($message['message']),
                    )));
                }, $this->connection);
    }

}
