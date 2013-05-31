<?php

namespace PHPSocketIO\Adapter;

use PHPSocketIO\Connection;

class HttpXHRPolling extends HttpPolling
{

    public function onTimeout() {
        $this->writeContent('8::');
    }

}
