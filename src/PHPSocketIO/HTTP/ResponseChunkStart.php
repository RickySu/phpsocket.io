<?php

namespace PHPSocketIO\HTTP;

use Symfony\Component\HttpFoundation;

/**
 * Description of ResponseChunkStart
 *
 * @author ricky
 */
class ResponseChunkStart extends HttpFoundation\Response implements ResponseInterface
{

    public function __construct($content = '', $status = 200, $headers = array())
    {
        $headers['Transfer-Encoding'] = 'chunked';
        parent::__construct($content, $status, $headers);
        $this->setProtocolVersion('1.1');
    }

    public function __toString() {
        return
            sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText)."\r\n".
            $this->headers."\r\n".
            sprintf("%x\r\n%s\r\n", strlen($this->getContent()), $this->getContent());
    }
}
