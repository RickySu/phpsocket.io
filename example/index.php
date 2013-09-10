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
        ->onConnect(function(Connection $connection) use($socketio){
            echo "connected {$connection->getRemote()[0]}:{$connection->getRemote()[1]}\n";
            $connection->on('msg', function(Event\MessageEvent $messageEvent) use($socketio){
                $message = $messageEvent->getMessage();
                $socketio->emit('update', $message);
                print_r($message);
            });
        })
        ->onRequest('/hello', function($connection, \EventHttpRequest $request) {
                //$connection = $event->getConnection();
                //$connection->write(new Response("hello world!"), true);
                $connection->sendResponse(new Response("hello world!"));
                //$connection->write(new Response("hello world!"), true);
        })
        ->dispatch();
