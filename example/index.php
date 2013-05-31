<?php

$loader = include '../vendor/autoload.php';
$loader->add('PHPSocketIO', __DIR__ . '/../src');

use PHPSocketIO\SocketIO;
use PHPSocketIO\Connection;
use PHPSocketIO\Adapter\HTTP\Request;
use PHPSocketIO\Adapter\HTTP;

$socketio = new SocketIO();
$socketio
        ->listen(8080)
        ->onConnect(function(Connection $connection) {
            $connection->on(Connection::EVENT_HTTP_REQUEST, function(Connection $connection, Request $request){
                $content = $request->getUri();
                $response = (new HTTP\Response())->setContent($content);
                $connection->write($response, true);
            });
        })->dispatch();
