<?php

namespace PHPSocketIO\Http;

use PHPSocketIO\Protocol\Builder as ProtocolBuilder;

class HttpJsonpPolling extends HttpPolling
{

    protected function writeContent($content)
    {
        parent::writeContent('io.j[0]('.  json_encode($content).');');
    }

    public function onTimeout() {
        $this->writeContent(ProtocolBuilder::Noop());
    }

}
