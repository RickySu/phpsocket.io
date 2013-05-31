<?php

namespace PHPSocketIO\Adapter\HTTP;

class ResponseChunkStart extends Response
{

    public function __construct() {
        parent::__construct();
        $this->setRawHeader('Transfer-Encoding', 'chunked');
        $this->setRawHeader('Connection', 'Keep-Alive');
        $this->setRawHeader('Date', 'Fri, 31 May 2013 06:45:35 GMT');
    }

    public function setContent($content)
    {
        $this->content = sprintf("%x\r\n", strlen($content), $content);
    }

}