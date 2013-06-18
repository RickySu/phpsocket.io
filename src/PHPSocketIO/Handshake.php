<?php

namespace PHPSocketIO;

use PHPSocketIO\HTTP\HTTP;
use PHPSocketIO\HTTP;

class Handshake
{
    protected $validTransportID = array(/*'websocket', 'htmlfile', 'xhr-polling',*/ 'jsonp-polling');

    public function onRequest(Connection $connection, HTTP\Request $request)
    {
        $requestDocSplit = explode('/', substr($request->getDoc(), 1));
        if($requestDocSplit[0] != $connection->getNamespace()){
            return;
        }
        $this->parseRequest($connection, $request, $requestDocSplit);
        return Event\Dispatcher::STOP_PROPAGATE;
    }

    protected function parseRequest(Connection $connection, HTTP\Request $request, $requestDocSplit)
    {

        if(!isset($requestDocSplit[1]) || $requestDocSplit[1]!=1){
            $connection->write($this->generateUnsupportedProtocolResponse(), true);
            return;
        }

        if(!isset($requestDocSplit[2]) || $requestDocSplit[2]==''){
            $connection->write($this->generateHanshakeResponse($request), true);
            return;
        }

        if(!in_array($requestDocSplit[2], $this->validTransportID)){
            $connection->write($this->generateUnsupportedProtocolResponse(), true);
            return;
        }

        $this->upgradeProtocol($connection, $request, $requestDocSplit[2]);
    }

    protected function upgradeProtocol(Connection $connection, HTTP\Request $request, $transportId)
    {
        switch ($transportId){
            case 'jsonp-polling':
                $connection->setProtocolProcessor(new Adapter\HttpJsonpPolling($connection));
                break;
            case 'xhr-polling':
                $connection->setProtocolProcessor(new Adapter\HttpXHRPolling($connection));
                break;
        }
    }

    protected function generateUnsupportedProtocolResponse()
    {
        return (new HTTP\Response())->setStatusCode(500, 'unsupported protocol');
    }

    protected function generateHanshakeResponse(HTTP\Request $request)
    {
        $response = new HTTP\Response();
        $response
                 ->setContentType('text/plain')
                 ->setRawHeader('Access-Control-Allow-Origin', $request->getOrigin())
                 ->setRawHeader('Access-Control-Allow-Credentials', 'true')
                 ->setContent(md5(rand().time()).':60:60:'.implode(',', $this->validTransportID));
        return $response;
    }
}
