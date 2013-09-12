<?php
namespace PHPSocketIO\Http\WebSocket;

use PHPSocketIO\Request\Request;
use PHPSocketIO\Response\ResponseWebSocket;

/**
 * Description of WebSocket
 *
 * @author ricky
 */
class WebSocket
{
    const MIN_WS_VERSION = 13;
    const MAGIC_GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    protected $messageQueue;

    public function __construct()
    {
        $this->messageQueue = new MessageQueue();
    }

    public function onMessage($data)
    {
        $this->messageQueue->enqueue($data);
        return Frame::parse($this->messageQueue);
    }

    /**
     * @return Frame
     */
    public function getFrame()
    {
        return Frame::parse($messageQueue);
    }

    public function getHandshakeReponse($request)
    {
        $key = $request->headers->get('Sec-WebSocket-Key');
        if(
                $request->headers->get('Upgrade') != 'websocket' ||
                !$this->checkProtocolVrsion($request) ||
                !$this->checkSecKey($key)
          ){
            $this->connection->write( new Response('bad protocol', 400), true);
        }
        $acceptKey = $this->generateAcceptKey($key);
        $response = new ResponseWebSocket();
        $response->headers->set('Sec-WebSocket-Accept', $acceptKey);
        return $response;
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

    //put your code here
}

