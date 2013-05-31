<?php

namespace PHPSocketIO\Adapter\HTTP;

class ResponseChunkEnd
{
    public function getOutput()
    {
        return "0\r\n\r\n";
    }

    public function __toString()
    {
        return $this->getOutput();
    }
}