<?php

namespace PHPSocketIO\Http;

use PHPSocketIO\Connection;
use PHPSocketIO\Event;
use PHPSocketIO\Protocol\Builder as ProtocolBuilder;

class HttpWebSocket extends HttpPolling
{

    const MIN_WS_VERSION = 13;
    const MAGIC_GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    public function __construct(Connection $connection, $sessionInited)
    {
        $this->connection = $connection;
        $this->request = $connection->getRequest();
        $this->upgradeProtocol();
        //$this->initEvent();
        //$this->connection->setTimeout($this->defuleTimeout, function(){$this->onTimeout();});
        return;
    }

    protected function upgradeProtocol()
    {
        $request = $this->connection->getRequest();
        $key = $request->headers->get('Sec-WebSocket-Key');
        if(
                $request->headers->get('Upgrade') != 'websocket' ||
                !$this->checkProtocolVrsion($request) ||
                !$this->checkSecKey($key)
          ){
            $this->connection->write( new Response('bad protocol', 400), true);
        }
        $acceptKey = $this->generateAcceptKey($key);
        $response = new ResponseWebSocket(null, 101);
        $response->headers->set('Connection', 'Upgrade');
        $response->headers->set('Sec-WebSocket-Accept', $acceptKey);
        $response->headers->set('Upgrade', 'websocket');
        $response->headers->remove('Content-Length');
        $response->headers->remove('Cache-Control');
        $response->headers->remove('Date');
        $response->setContent($this->generateResponseData(ProtocolBuilder::Connect()));
        $this->connection->write($response);
    }

    protected function generateAcceptKey($key)
    {
        return base64_encode(sha1($key.static::MAGIC_GUID, true));
    }

    protected function checkSecKey($key)
    {
        return strlen(base64_decode($key)) == 16;
    }

    protected function checkProtocolVrsion(Request $request)
    {
        return $request->headers->get('Sec-WebSocket-Version', 0) >= static::MIN_WS_VERSION;
    }

    protected function generateResponseData($content)
    {
        echo "output:$content\n";
        return pack('CC', 0x81, strlen($content)).$content;
    }

    protected function parseClientEmitData()
    {

    }

    protected function setResponseHeaders($response)
    {

    }
}
