<?php

namespace PHPSocketIO\Http;

use PHPSocketIO\Connection;
use PHPSocketIO\Protocol\Builder as ProtocolBuilder;
use PHPSocketIO\Event;
use PHPSocketIO\Response\ResponseWebSocketFrame;
use PHPSocketIO\Protocol\Handshake;

class HttpWebSocket
{

    protected $websocket;

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
        //$this->connection->setTimeout($this->defuleTimeout, function(){$this->onTimeout();});
    }

    protected function sendData($data)
    {
        $this->connection->write(new ResponseWebSocketFrame(new WebSocket\Frame($data)));
    }

    protected function initEvent()
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->addListener("socket.receive", function(Event\MessageEvent $messageEvent) {
                    $message = $messageEvent->getMessage();
                    $frame = $this->websocket->onMessage($message);
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
