<?php

namespace PHPSocketIO\Http;

use PHPSocketIO\ConnectionInterface;
use PHPSocketIO\Request\Request;
use PHPSocketIO\Protocol\Builder as ProtocolBuilder;
use PHPSocketIO\Event;
use PHPSocketIO\Response\ResponseWebSocketFrame;
use PHPSocketIO\Protocol\Handshake;

class HttpWebSocket
{

    protected $heartbeatTimeout = 30;
    protected $websocket;
    /**
     *
     * @var ConnecyionInterface
     */
    protected $connection;

    public function setRequest(Request $request)
    {
        $this->request = $request;
        return true;
    }

    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        return true;
    }

    /**
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     *
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    public function __construct(Request $request, $sessionInited)
    {
        $this->setRequest($request);
        $this->setConnection($request->getConnection());
        $this->websocket = new WebSocket\WebSocket();
        if (!($handshakeResponse = $this->websocket->getHandshakeReponse($request))) {
            $this->getConnection()->write(new Response('bad protocol', 400), true);
            return;
        }
        $this->getConnection()->write($handshakeResponse);
        $this->sendData(ProtocolBuilder::Connect());
        $this->initEvent();
        $this->setHeartbeatTimeout();
    }

    protected function setHeartbeatTimeout()
    {
        $connection = $this->getConnection();
        $connection->clearTimeout();
        $connection->setTimeout($this->heartbeatTimeout, function(){
            $this->sendData(ProtocolBuilder::Heartbeat());
        });
    }

    protected function sendData($data)
    {
        if(!($data instanceof WebSocket\Frame)){
            $data = WebSocket\Frame::generate($data);
        }
        $this->getConnection()->write(new ResponseWebSocketFrame($data), $data->isClosed());
        $this->setHeartbeatTimeout();
    }

    protected function initEvent()
    {
        $connection = $this->getConnection();
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->addListener("socket.receive", function(Event\MessageEvent $messageEvent) {
                    $message = $messageEvent->getMessage();
                    $frame = $this->websocket->onMessage($message);
                    if(!($frame instanceof WebSocket\Frame)){
                        return;
                    }
                    Handshake::processProtocol($frame->getData(), $this->getConnection());
                }, $connection);

        $dispatcher->addListener("server.emit", function(Event\MessageEvent $messageEvent) {
                    $message = $messageEvent->getMessage();
                    $this->sendData(ProtocolBuilder::Event(array(
                                'name' => $message['event'],
                                'args' => array($message['message']),
                    )));
                }, $connection);
    }

}
