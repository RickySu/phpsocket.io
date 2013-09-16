<?php
namespace PHPSocketIO\Protocol;

use PHPSocketIO\Event;
use PHPSocketIO\Response\Response;
use PHPSocketIO\Request\Request;
use PHPSocketIO\Http;
use PHPSocketIO\ConnectionInterface;

class Handshake
{
    const PROTOCOL_WEBSOCKET = 'websocket';
    const PROTOCOL_HTMLFILE = 'htmlfile';
    const PROTOCOL_XHR_POLLING = 'xhr-polling';
    const PROTOCOL_JSONP_POLLING = 'jsonp-polling';

    protected static $validTransportID = array(
        self::PROTOCOL_WEBSOCKET,
        self::PROTOCOL_XHR_POLLING,
        self::PROTOCOL_HTMLFILE,
        self::PROTOCOL_JSONP_POLLING,
    );

    public static function initialize(Request $request)
    {
        list($uri, ) = explode('?', $request->getRequestUri());
        $requestDocSplit = explode('/', substr($uri, 1));
        if($requestDocSplit[0] != $request->getConnection()->getNamespace()){
            return self::PROTOCOL_HTMLFILE;
        }

        return static::parseRequest($request, $requestDocSplit);
    }

    protected static function parseRequest(Request $request, $requestDocSplit)
    {

        if(!isset($requestDocSplit[1]) || $requestDocSplit[1]!=1){
            return new Response('bad protocol', 400);
        }

        $requestEvent = new Event\RequestEvent();
        $requestEvent->setRequest($request);
        $dispatcher = Event\EventDispatcher::getDispatcher();
        $dispatcher->dispatch('request.init.session', $requestEvent);

        if(!isset($requestDocSplit[2]) || $requestDocSplit[2]==''){
            return static::generateHanshakeResponse($request);
        }

        if(!in_array($requestDocSplit[2], static::$validTransportID)){
            return new Response('bad protocol', 400);
        }
        return static::upgradeProtocol($request, $requestDocSplit[2], $requestDocSplit[3]);
    }

    public static function processProtocol($data, ConnectionInterface $connection)
    {
        if(!preg_match('/^(.*?):(.*?):(.*?):(.*?)$/i', $data, $match)){
            return new Response('bad protocol', 404);
        }
        list($raw, $type, $id, $endpoint, $jsonData) = $match;
        switch($type){
            case 2:    //Heartbeat
                break;
            case 5:    //Event
                $eventData = json_decode($jsonData, true);
                if(!isset($eventData['name']) && !isset($eventData['args'])){
                    return new Response('bad protocol', 402);
                }
                $messageEvent = new Event\MessageEvent();
                $messageEvent->setMessage($eventData['args'][0]);
                $messageEvent->setConnection($connection);
                $dispatcher = Event\EventDispatcher::getDispatcher();
                $dispatcher->dispatch("client.{$eventData['name']}", $messageEvent);
                break;
        }
        return new Response('1');
    }

    protected static function upgradeProtocol(Request $request, $transportId, $sessionId)
    {
        $session = $request->getSession();
        $session->setId($sessionId);
        $session->start();
        $sessionInited = $session->get('sessionInited');

        if(!$sessionInited){
            $session->set('sessionInited', true);
        }

        switch ($transportId){
            case static::PROTOCOL_JSONP_POLLING:
                return new Http\HttpJsonpPolling($request, $sessionInited);
            case static::PROTOCOL_XHR_POLLING:
                return new Http\HttpXHRPolling($request, $sessionInited);
            case static::PROTOCOL_WEBSOCKET:
                return new Http\HttpWebSocket($request, $sessionInited);
            default:
                return new Response('bad protocol', 400);
        }
    }

    protected static function generateHanshakeResponse(Request $request)
    {
        $session = $request->getSession();
        $session->start();
        $response = new Response("{$request->getSession()->getId()}:60:60:".implode(',', self::$validTransportID));
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        return $response;
    }

}
