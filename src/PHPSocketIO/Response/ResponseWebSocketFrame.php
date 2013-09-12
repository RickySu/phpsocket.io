<?php
namespace PHPSocketIO\Response;

use PHPSocketIO\Http\WebSocket\Frame;

/**
 * Description of Request
 *
 * @author ricky
 */
class ResponseWebSocketFrame implements ResponseInterface
{
    protected $frame;

    public function __construct(Frame $frame)
    {
        $this->frame = $frame;
    }

    public function __toString()
    {
        return $this->frame->encode();
    }
}
