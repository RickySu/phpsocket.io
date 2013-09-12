<?php

namespace PHPSocketIO\Response;

class ResponseChunkEnd implements ResponseInterface
{

    public function __toString()
    {
        return "0\r\n\r\n";
    }

}