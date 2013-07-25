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
    protected $request = null;

    public function __construct(Connection $connection) {
        $this->parseRequest($connection);
    }

    protected function parseRequest(Connection $connection)
    {
        $request = $connection->getRequest();
        $header = $request->getInputHeaders();
        $SERVER = $this->parseServer($header);
        list($SERVER['REMOTE_ADDR'], $SERVER['REMOTE_PORT']) = $connection->getRemote();
        if(isset($header['HTTP_COOKIE'])){
            $COOKIE = $this->parseCookie($header['HTTP_COOKIE']);
        }
        else{
            $COOKIE = array();
        }
        $SERVER['REQUEST_URI'] = $request->getUri();
        if(($pos = strpos('?', $SERVER['REQUEST_URI'])) !== false){
            $SERVER['QUERY_STRING'] = substr($SERVER['REQUEST_URI'], $pos+1);
        }
        else{
            $SERVER['QUERY_STRING'] = '';
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

    protected function parseServer($headers)
    {
        foreach($headers as $key => $value){
            $key = strtoupper(str_replace('-', '_', $key));
            $SERVER["HTTP_$key"] = $value;
        }

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
