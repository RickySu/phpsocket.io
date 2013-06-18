<?php

namespace PHPSocketIO\Http;

use PHPSocketIO\Connection;
use PHPSocketIO\Protocol\Builder as ProtocolBuilder;

class HttpXHRPolling extends HttpPolling
{

    public function onTimeout() {
        $this->writeContent(ProtocolBuilder::Noop());
    }

}
