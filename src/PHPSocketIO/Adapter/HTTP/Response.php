<?php

namespace PHPSocketIO\Adapter\HTTP;

class Response
{

    const STATUS_OK = 200;

    protected $statusCode;
    protected $statusMessage = 'OK';
    protected $header=[];
    protected $content;

    public function __construct() {
        $this->setContentType();
        $this->setStatusCode(self::STATUS_OK);
    }

    public function setStatusCode($statusCode, $statusMessage = 'OK')
    {
        $this->statusCode = $statusCode;
        $this->statusMessage = $statusMessage;
        return $this;
    }

    public function setContentType($type='text/plain', $charset = 'utf-8')
    {
        $this->setRawHeader('Content-Type', "$type; charset=$charset");
        return $this;
    }

    public function setContent($content)
    {
        $this->content = $content;
        $this->setContentLength(strlen($content));
        return $this;
    }

    public function setContentLength($length)
    {
        $this->setRawHeader('Content-Length', $length);
        return $this;
    }

    public function setRawHeader($field, $content)
    {
        $this->header[$field] = $content;
        return $this;
    }

    public function getOutput()
    {
        $output = "HTTP/1.1 {$this->statusCode} {$this->statusMessage}\r\n";
        foreach($this->header as $field => $content){
            $output.="$field: $content\r\n";
        }
        $output.="\r\n";
        return $output.$this->content;
    }

    public function __toString()
    {
        return $this->getOutput();
    }
}