<?php
namespace PHPSocketIO\Http;

use PHPSocketIO\Connection;

class Http
{
    static public function init(Connection $connection, \EventHttpRequest $eventHTTPRequest)
    {
        $request = static::parseRequest($connection, $eventHTTPRequest);
        return $request;
    }

    static public function handleRequest(Connection $connection, Request $request)
    {
        $handshakeResult = Handshake::initialize($connection, $request);

        if($handshakeResult === Handshake::PROTOCOL_HTMLFILE){
            return;
        }

        if($handshakeResult instanceof Response){
            $connection->write($handshakeResult, true);
            return;
        }
    }

    static protected function parseRequest(Connection $connection, \EventHttpRequest $eventHTTPRequest)
    {
        $header = $eventHTTPRequest->getInputHeaders();
        $SERVER = static::parseServer($header);
        list($SERVER['REMOTE_ADDR'], $SERVER['REMOTE_PORT']) = $connection->getRemote();
        if(isset($header['HTTP_COOKIE'])){
            $COOKIE = static::parseCookie($header['HTTP_COOKIE']);
        }
        else{
            $COOKIE = array();
        }
        $SERVER['REQUEST_URI'] = $eventHTTPRequest->getUri();
        if(($pos = strpos('?', $SERVER['REQUEST_URI'])) !== false){
            $SERVER['QUERY_STRING'] = substr($SERVER['REQUEST_URI'], $pos+1);
        }
        else{
            $SERVER['QUERY_STRING'] = '';
        }
        $GET = static::parseGET($SERVER['QUERY_STRING']);
        $BODY = $eventHTTPRequest->getInputBuffer()->read(4096);
        $POST = static::parsePOST($BODY);
        $SERVER['REQUEST_METHOD'] = array_search($eventHTTPRequest->getCommand(), array(
            'GET' => \EventHttpRequest::CMD_GET ,
            'POST' => \EventHttpRequest::CMD_POST ,
            'HEAD' => \EventHttpRequest::CMD_HEAD ,
            'PUT' => \EventHttpRequest::CMD_PUT ,
            'DELETE' => \EventHttpRequest::CMD_DELETE ,
            'OPTIONS' => \EventHttpRequest::CMD_OPTIONS ,
            'TRACE ' => \EventHttpRequest::CMD_TRACE ,
            'CONNECT ' => \EventHttpRequest::CMD_CONNECT ,
            'PATCH ' => \EventHttpRequest::CMD_PATCH ,
        ));
        return new Request($GET, $POST, array(), $COOKIE, array(), $SERVER, $BODY);
    }

    static protected function parseGET($rawGET)
    {
        parse_str($rawGET, $GET);
        return $GET;
    }

    static protected function parsePOST($rawPOST)
    {
        parse_str($rawPOST, $POST);
        return $POST;
    }

    static protected function parseCookie($rawCookie)
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

    static protected function parseServer($headers)
    {
        foreach($headers as $key => $value){
            $key = strtoupper(str_replace('-', '_', $key));
            $SERVER["HTTP_$key"] = $value;
        }

        return $SERVER;
    }

}
