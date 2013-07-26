<?php
namespace PHPSocketIO\Http;

use PHPSocketIO\Connection;
use PHPSocketIO\Event;

class Handshake
{
    const PROTOCOL_WEBSOCKET = 'websocket';
    const PROTOCOL_HTMLFILE = 'htmlfile';
    const PROTOCOL_XHR_POLLING = 'xhr-polling';
    const PROTOCOL_JSONP_POLLING = 'jsonp-polling';

    protected static $validTransportID = array(
        /*self::PROTOCOL_WEBSOCKET,
        self::PROTOCOL_XHR_POLLING,*/
        self::PROTOCOL_HTMLFILE,
        self::PROTOCOL_JSONP_POLLING,
    );

    public static function initialize(Connection $connection, Request $request)
    {
        list($uri, ) = explode('?', $request->getRequestUri());
        $requestDocSplit = explode('/', substr($uri, 1));
        if($requestDocSplit[0] != $connection->getNamespace()){
            return self::PROTOCOL_HTMLFILE;
        }

        return static::parseRequest($connection, $request, $requestDocSplit);
    }

    protected static function parseRequest(Connection $connection, Request $request, $requestDocSplit)
    {

        if(!isset($requestDocSplit[1]) || $requestDocSplit[1]!=1){
            return new Response('bad protocol', 400);
        }

        if(!isset($requestDocSplit[2]) || $requestDocSplit[2]==''){
            return static::generateHanshakeResponse($request);
        }

        if(!in_array($requestDocSplit[2], static::$validTransportID)){
            return new Response('bad protocol', 400);
        }

        return static::upgradeProtocol($connection, $request, $requestDocSplit[2]);
    }

    protected static function upgradeProtocol(Connection $connection, Request $request, $transportId)
    {
        switch ($transportId){
            case 'jsonp-polling':
                return new HttpJsonpPolling($connection, $request);
            case 'xhr-polling':
                return new HttpXHRPolling($connection, $request);
        }
    }

    protected static function generateHanshakeResponse(Request $request)
    {
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->dispatch('request.session', $returnEvent = new Event\ReturnEvent());
        $response = new Response($returnEvent->getReturn().':60:60:'.implode(',', self::$validTransportID));
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        return $response;
    }

}
