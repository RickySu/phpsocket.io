<?php

$loader = include '../vendor/autoload.php';
$loader->add('PHPSocketIO', __DIR__ . '/../src');

use PHPSocketIO\SocketIO;
use PHPSocketIO\Connection;
use PHPSocketIO\HTTP\Request;
use PHPSocketIO\HTTP\Response;

$socketio = new SocketIO();
$socketio
        ->listen(8080)
        ->onConnect(function(Connection $connection) {
            $connection->on(Request::EVENT_HTTP_REQUEST, function(Connection $connection, Request $request){
                $content = $request->getUri();
                $response = (new Response)->setContent($content);
                $connection->write($response, true);
            });
        })->dispatch();
