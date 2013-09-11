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
//        self::PROTOCOL_WEBSOCKET,
        self::PROTOCOL_XHR_POLLING,
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

        $requestEvent = new Event\RequestEvent($connection, $request);
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->dispatch('request.init.session', $requestEvent);

        if(!isset($requestDocSplit[2]) || $requestDocSplit[2]==''){
            return static::generateHanshakeResponse($connection);
        }

        if(!in_array($requestDocSplit[2], static::$validTransportID)){
            return new Response('bad protocol', 400);
        }
        static::upgradeProtocol($connection, $request, $requestDocSplit[2], $requestDocSplit[3]);
    }

    public static function processProtocol($data)
    {
        if(!preg_match('/^(.*?):(.*?):(.*?):(.*?)$/i', $data, $match)){
            return new Response('bad protocol', 404);
        }
        list($raw, $type, $id, $endpoint, $jsonData) = $match;
        switch($type){
            case 5:    //Event
                $eventData = json_decode($jsonData, true);
                if(!isset($eventData['name']) && !isset($eventData['args'])){
                    return new Response('bad protocol', 402);
                }
                $dispatcher = Event\EventDispatcher::getDispatcher();
                $dispatcher->dispatch("client.{$eventData['name']}", new Event\MessageEvent($eventData['args'][0]));
                break;
        }
        return new Response('1');
    }

    protected static function upgradeProtocol(Connection $connection, Request $request, $transportId, $sessionId)
    {
        $session = $request->getSession();
        $session->setId($sessionId);
        $session->start();
        $sessionInited = $session->get('sessionInited');

        if(!$sessionInited){
            $session->set('sessionInited', true);
        }

        switch ($transportId){
            case 'jsonp-polling':
                return new HttpJsonpPolling($connection, $sessionInited);
            case 'xhr-polling':
                return new HttpXHRPolling($connection, $sessionInited);
        }
    }

    protected static function generateHanshakeResponse(Connection $connection)
    {
        $request = $connection->getRequest();
        $session = $request->getSession();
        $session->start();
        $response = new Response("{$request->getSession()->getId()}:60:60:".implode(',', self::$validTransportID));
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        return $response;
    }

}
