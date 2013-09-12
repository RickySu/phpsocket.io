<?php

$loader = include '../vendor/autoload.php';
$loader->add('PHPSocketIO', __DIR__ . '/../src');

use PHPSocketIO\SocketIO;
use PHPSocketIO\Connection;
use PHPSocketIO\Http\Response;
use PHPSocketIO\Event;

$socketio = new SocketIO();
$socketio
        ->listen(8080)
        ->on('msg', function(Event\MessageEvent $messageEvent) use($socketio){
            $message = $messageEvent->getMessage();
            $connection = $messageEvent->getConnection();
            $socketio->emit('update', $message);
        })
        ->onConnect(function(Connection $connection) use($socketio){
            echo "connected {$connection->getRemote()[0]}:{$connection->getRemote()[1]}\n";
        })
        ->onRequest('/hello', function($connection, \EventHttpRequest $request) {
                //$connection = $event->getConnection();
                //$connection->write(new Response("hello world!"), true);
                $connection->sendResponse(new Response("hello world!"));
                //$connection->write(new Response("hello world!"), true);
        })
        ->dispatch();
