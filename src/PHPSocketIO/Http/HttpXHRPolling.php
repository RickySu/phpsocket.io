<?php

namespace PHPSocketIO\Http;

use PHPSocketIO\Protocol\Builder as ProtocolBuilder;

class HttpXHRPolling extends HttpPolling
{

    protected function init()
    {
        $response = new Response('io.j[0]("1::")');
        $response->headers->set('X-XSS-Protection', 0);
        $this->connection->write($response);
    }
    protected function enterPollingMode()
    {
        $response = new ResponseChunkStart();
        $response->headers->set('X-XSS-Protection', 0);
        $this->connection->write($response);
    }

    protected function writeContent($content)
    {
        parent::writeContent('io.j[0]('.  json_encode($content).');');
    }

    public function onTimeout() {
        $this->writeContent(ProtocolBuilder::Noop());
    }

}
