<?php
namespace PHPSocketIO\Http\Parser;

use PHPSocketIO\Http;
use PHPSocketIO\Event;
use PHPSocketIO\Connection;

/**
 * Description of Http
 *
 * @author ricky
 */
class RequestParser {

    const MAX_HEADER_SIZE = 1024;
    protected $data = '';
    protected $request = null;

    public function __construct(Connection $connection) {
        $connection->onRecieve(function(Event\ReceiveEvent $receiveEvent){
            $this->onReceive($receiveEvent);
        });
    }
    protected function appendData($data)
    {
        $this->data.=$data;
    }

    protected function parseRequest(Connection $connection)
    {
        if(strlen($this->data) > static::MAX_HEADER_SIZE){
            throw new Exception(HTTP\Response::$statusTexts[414], 414);
        }

        $pos = strpos($this->data, "\r\n\r\n");

        if($pos === null){
            return;
        }

        $header = substr($this->data, 0, $pos);
        $this->data = substr($this->data, $pos + 4);   //skip \r\n\r\n
        $SERVER = $this->parseServer($header);
        if(isset($SERVER['HTTP_COOKIE'])){
            $COOKIE = $this->parseCookie($SERVER['HTTP_COOKIE']);
        }
        else{
            $COOKIE = array();
        }
        $GET = $this->parseGET($SERVER['QUERY_STRING']);
        $POST = $this->parsePOST(null);
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->dispatch('request', new Event\RequestEvent($connection, new Http\Request($GET, $POST, array(), $COOKIE, array(), $SERVER, '')), $connection);
    }

    protected function parseGET($rawGET)
    {
        parse_str($rawGET, $GET);
        return $GET;
    }

    protected function parsePOST($rawPOST)
    {
        return array();
    }

    protected function parseCookie($rawCookie)
    {
        $COOKIE = array();
        foreach(explode(';', $rawCookie) as $cookie){
            $pos = strpos($cookie, '=');
            if($pos === null){
                return array();
            }
            $COOKIE[trim(substr($cookie, 0, $pos))] = trim(substr($cookie, $pos+1));
        }
        return $COOKIE;
    }

    protected function parseServer($header)
    {
        $headerArray = explode("\r\n", $header);
        list($SERVER["REQUEST_METHOD"], $SERVER["REQUEST_URI"], $SERVER["SERVER_PROTOCOL"]) = explode(' ', array_shift($headerArray));
        foreach($headerArray as $line){
            $pos = strpos($line, ':');
            $key = str_replace('-', '_', strtoupper(trim(substr($line, 0, $pos))));
            $val = trim(substr($line, $pos + 1));
            $SERVER["HTTP_$key"] = $val;
        }

        @list(, $queryString) = explode('?', $SERVER['REQUEST_URI']);
        $SERVER['QUERY_STRING'] = $queryString;
        return $SERVER;
    }

    public function onReceive(Event\ReceiveEvent $receiveEvent)
    {
        if($this->request===null){
            $this->appendData($receiveEvent->getMessage());
            $this->parseRequest($receiveEvent->getConnection());
        }
    }

}
