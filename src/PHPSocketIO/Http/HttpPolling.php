<?php

namespace PHPSocketIO\Http;

use PHPSocketIO\ConnectionInterface;
use PHPSocketIO\Event;
use PHPSocketIO\Protocol\Builder as ProtocolBuilder;
use PHPSocketIO\Response\ResponseInterface;
use PHPSocketIO\Response\Response;
use PHPSocketIO\Response\ResponseChunk;
use PHPSocketIO\Response\ResponseChunkStart;
use PHPSocketIO\Response\ResponseChunkEnd;
use PHPSocketIO\Request\Request;
use PHPSocketIO\Protocol\Handshake;

abstract class HttpPolling
{

    /**
     *
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     *
     * @var Request
     */
    protected $request;

    protected $defuleTimeout = 50;

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
        if (!$sessionInited) {
            $this->init();

            return;
        }
        if ($request->isMethod('POST')) {
            $this->processPOSTMethod();

            return;
        }
        $this->enterPollingMode();
        $this->initEvent();
        $this->getConnection()->setTimeout($this->defuleTimeout, function(){$this->onTimeout();});

        return;
    }

    protected function processPOSTMethod()
    {
        Handshake::processProtocol($this->parseClientEmitData(), $this->getConnection());
        $response = $this->setResponseHeaders(new Response('1'));
        $this->getConnection()->write($response);
    }

    abstract protected function parseClientEmitData();

    protected function initEvent()
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->addListener("connect", function() {
            $endpoint = $this->getRequest()->getSession()->get('endpoint');
            $this->writeChunkEnd(ProtocolBuilder::Connect($endpoint));
        },array(
            $this->connection->getSessionId(),
        ));
        $dispatcher->addListener("server.emit", function(Event\MessageEvent $messageEvent){
            $message = $messageEvent->getMessage();
            if($this->connection->isConnectionClose()){
                $this->connection->queuePendingEmitEvent("server.emit", $message);
                return;
            }
            $endpoint = $this->getRequest()->getSession()->get('endpoint', $messageEvent->getEndpoint());
            $this->writeChunkEnd(ProtocolBuilder::Event(array(
                'name' => $message['event'],
                'args' => array($message['message']),
            ), $endpoint));
        },array(
            $this->connection->getSessionId(),
            $this->getRequest()->getSession()->get('endpoint'),
        ));
    }

    protected function init()
    {
        $response = $this->setResponseHeaders(
            new Response($this->generateResponseData(ProtocolBuilder::Connect()))
        );
        $this->getConnection()->write($response, true);
    }

    protected function enterPollingMode()
    {
        $response = $this->setResponseHeaders(new ResponseChunkStart());
        $this->getConnection()->write($response);
    }

    abstract protected function generateResponseData($content);

    abstract protected function setResponseHeaders(ResponseInterface $response);

    protected function onTimeout()
    {
        $this->writeChunkEnd(ProtocolBuilder::Noop());
    }

    protected function writeChunkEnd($content)
    {
        $content = $this->generateResponseData($content);
        $this->getConnection()->clearTimeout();
        $this->getConnection()->write(new ResponseChunk($content));
        $this->getConnection()->write(new ResponseChunkEnd(), true);
    }

}
