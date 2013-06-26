<?php

namespace PHPSocketIO\Http;

use PHPSocketIO\Connection;

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

    protected $defuleTimeout = 6;

    public function __construct(Connection $connection, Request $request)
    {
        $this->connection = $connection;
        $this->request = $request;
        $this->enterPollingMode();
    }

    protected function enterPollingMode()
    {
        $response = new ResponseChunkStart();
        $response->headers->set('Access-Control-Allow-Origin', $this->request->headers->get('Origin'));
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $this->connection->write($response);
        $this->connection->setTimeout($this->defuleTimeout, array($this, 'onTimeout'));
    }


    abstract public function onTimeout();

    protected function writeContent($content)
    {
        $this->connection->clearTimeout();
        $this->connection->write(new ResponseChunk($content));
        $this->connection->write(new ResponseChunkEnd(), true);
    }

}
