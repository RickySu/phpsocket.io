<?php

namespace PHPSocketIO\Http;

use PHPSocketIO\Connection;
use PHPSocketIO\Event;

abstract class HttpPolling
{

    /**
     *
     * @var Connection
     */
    protected $connection;

    /**
     *
     * @var Request
     */
    protected $request;

    protected $defuleTimeout = 10;

    public function __construct(Connection $connection, $sessionInited)
    {
        $this->connection = $connection;
        $this->request = $connection->getRequest();
        if(!$sessionInited){
            $this->init();
            return;
        }
        $this->enterPollingMode();
        $this->initEvent();
        $this->connection->setTimeout($this->defuleTimeout, array($this, 'onTimeout'));
    }

    protected function initEvent()
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->addListener("server.emit", function(Event\MessageEvent $messageEvent){
            $message = $messageEvent->getMessage();
            $this->writeContent("5:::".json_encode(array(
                'name' => $message['event'],
                'args' => array($message['message']),
            )));
        }, $this->connection);
    }

    abstract protected function init();

    abstract protected function enterPollingMode();

    abstract public function onTimeout();

    protected function writeContent($content)
    {
        $this->connection->clearTimeout();
        $this->connection->write(new ResponseChunk($content));
        $this->connection->write(new ResponseChunkEnd(), true);
    }

}
