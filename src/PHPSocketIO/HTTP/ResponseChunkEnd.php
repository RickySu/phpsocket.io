<?php

namespace PHPSocketIO\HTTP;

class ResponseChunkEnd implements ResponseInterface
{

    public function __toString()
    {
        return "0\r\n\r\n";
    }

}