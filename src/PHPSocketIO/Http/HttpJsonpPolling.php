<?php
namespace PHPSocketIO\Http;

class HttpJsonpPolling extends HttpPolling
{

    protected function parseClientEmitData()
    {
        return json_decode($this->request->request->get('d'), true);
    }

    protected function generateResponseData($content)
    {
        return 'io.j[0](' . json_encode($content) . ');';
    }

    protected function setResponseHeaders($response)
    {
        $response->headers->set('X-XSS-Protection', 0);
        return $response;
    }

}
