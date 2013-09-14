<?php

namespace PHPSocketIO\Response;

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
        $response =
            sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText)."\r\n".
            $this->headers."\r\n";
        if($this->getContent()!==''){
            $response.=sprintf("%x\r\n%s\r\n", strlen($this->getContent()), $this->getContent());
        }
        return $response;
    }
}
