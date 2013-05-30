<?php

namespace PHPSocketIO\HTTPHeader;

class Request
{
    protected $method;
    protected $uri;
    protected $doc;
    protected $params;
    protected $protocol;
    protected $protocolVersion;

    public function __construct($rawHeader) {
        $headerLines = explode("\r\n", $rawHeader);
        $this->parseMethod($headerLines[0]);
    }

    protected function parseMethod($line)
    {
        $line = preg_replace('/\s{2,}/i',' ',trim($line));
        list($this->method, $this->uri, $protocolWithVersion) = explode(' ', $line);
        list($this->protocol, $this->protocolVersion) = explode('/', $protocolWithVersion);
        if(strpos($this->uri, '?')===false){
            $this->doc = $this->uri;
        }
        else{
            list($this->doc, $params) = explode('?', $this->uri);
            parse_str($params, $this->params);
        }
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getDoc()
    {
        return $this->doc;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getProtocol()
    {
        return $this->protocol;
    }

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }
}