<?php

namespace PHPSocketIO\Adapter;

use PHPSocketIO\Connection;

abstract class HttpPolling extends Http
{
    static $send = false;

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
    }

    public function init()
    {
        $this->enterPollingMode();
    }

    protected function enterPollingMode()
    {
        $response = new HTTP\ResponseChunkStart();
        $response->setRawHeader('Access-Control-Allow-Origin', $this->header->getOrigin())
                 ->setRawHeader('Access-Control-Allow-Credentials', 'true');
        $this->connection->write($response);
        $this->connection->setTimeout(3, array($this, 'onTimeout'));
    }


    abstract public function onTimeout();

    protected function writeContent($content)
    {
        $this->connection->clearTimeout();
        $this->connection->write(new HTTP\ResponseChunk($content));
        $this->connection->write(new HTTP\ResponseChunkEnd(), true);
    }

    public function onReceive($reveiceMessage)
    {

    }

    public function onWriteBufferEmpty()
    {

    }

}
