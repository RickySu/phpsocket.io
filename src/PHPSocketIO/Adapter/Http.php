<?php

namespace PHPSocketIO\Adapter;

use PHPSocketIO\Connection;
use PHPSocketIO\HTTPHeader;

class Http implements ProtocolProcessorInterface
{
    protected $rawHeader='';
    protected $header;
    protected $connection;

    protected function parseHeader($rawHeader)
    {
        return new HTTPHeader\Request($rawHeader);
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
            echo $this->rawHeader;
            if ($headerEndPosition!==false) {
                $this->header = $this->parseHeader(substr($this->rawHeader, 0, $headerEndPosition));
                $this->rawHeader = null;
            }

            return;
        }
    }

    public function onWriteBufferEmpty()
    {
    }

    public function __destruct()
    {
        $this->connection = null;
    }
}
