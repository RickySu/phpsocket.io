<?php

namespace PHPSocketIO\Response;

class ResponseChunk implements ResponseInterface
{
    protected $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function __toString()
    {
        return sprintf("%x\r\n%s\r\n", strlen($this->content), $this->content);
    }

}