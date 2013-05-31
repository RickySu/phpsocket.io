<?php

namespace PHPSocketIO\Adapter;

use PHPSocketIO\Connection;

class HttpJsonpPolling extends HttpPolling
{

    public function onTimeout() {
        $this->writeContent("okok");
    }

}
