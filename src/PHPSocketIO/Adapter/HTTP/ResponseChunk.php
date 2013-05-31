<?php

namespace PHPSocketIO\Adapter\HTTP;

class ResponseChunk
{
    protected $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function getOutput()
    {
        return sprintf("%x\r\n%s\r\n", strlen($this->content), $this->content);
    }

    public function __toString()
    {
        return $this->getOutput();
    }
}