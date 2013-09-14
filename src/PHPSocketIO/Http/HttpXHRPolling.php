<?php
namespace PHPSocketIO\Http;

class HttpXHRPolling extends HttpPolling
{

    protected function parseClientEmitData()
    {
        return $this->request->getContent();
    }

    protected function generateResponseData($content)
    {
        return $content;
    }

    protected function setResponseHeaders($response)
    {
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Access-Control-Allow-Origin', $this->request->headers->get('Origin'));
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        return $response;
    }

}
