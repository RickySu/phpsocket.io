<?php
namespace PHPSocketIO\Response;

use Symfony\Component\HttpFoundation;

/**
 * Description of Request
 *
 * @author ricky
 */
class ResponseWebSocket extends HttpFoundation\Response implements ResponseInterface
{
    public function __construct($content = '', $status = 101, $headers = array())
    {
        parent::__construct($content, $status, $headers);
        $this->headers->set('Connection', 'Upgrade');
        $this->headers->set('Upgrade', 'websocket');
        $this->headers->remove('Content-Length');
        $this->headers->remove('Cache-Control');
        $this->headers->remove('Date');
    }
}
