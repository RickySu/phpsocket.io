<?php

namespace PHPSocketIO\Adapter;

use PHPSocketIO\Connection;

class Http implements ProtocolProcessorInterface
{
    protected $rawHeader='';

    /**
     *
     * @var HTTP\Request
     */
    protected $header;

    /**
     *
     * @var PHPSocketIO\Connection
     */
    protected $connection;

    protected function parseHeader($rawHeader)
    {
        return new HTTP\Request($rawHeader);
    }

    public function init()
    {

    }
    
    protected function appendRawHeader($rawHeader)
    {
        $this->rawHeader .=  $rawHeader;
        return strpos($this->rawHeader, "\r\n\r\n");
    }

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function onReceive($reveiceMessage)
    {
        if ($this->header === null) {
            $headerEndPosition = $this->appendRawHeader($reveiceMessage);
            if ($headerEndPosition!==false) {
                $this->header = $this->parseHeader(substr($this->rawHeader, 0, $headerEndPosition));
                $this->rawHeader = null;
                $this->connection->trigger(Connection::EVENT_HTTP_REQUEST, $this->connection, $this->header);
            }
            return;
        }
    }

    public function onWriteBufferEmpty()
    {

    }

    public function getHeader()
    {
        return $this->header;
    }

    public function setHeader(HTTP\Request $header)
    {
        $this->header = $header;
    }

    public function __destruct()
    {
        $this->header = null;
        $this->connection = null;
    }

    /**
     *
     * @return Connection
     */
    public function getConnection() {
        return $this->connection;
    }
}
