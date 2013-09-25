<?php

$loader = include '../autoload.php.dist';
$loader->add('PHPSocketIO', __DIR__ . '/../src');

use PHPSocketIO\SocketIO;
use PHPSocketIO\Connection;
use PHPSocketIO\Response\Response;
use PHPSocketIO\Event;

$socketio = new SocketIO();
$chat = $socketio
        ->getSockets()
        ->on('addme', function(Event\MessageEvent $messageEvent) use (&$chat) {
            $messageEvent->getConnection()->emit('update', array('msg' => "Welcome {$messageEvent->getMessage()}"));
            $chat->emit("update", array('msg' => "{$messageEvent->getMessage()} is coming."));
        })
        ->on('msg', function(Event\MessageEvent $messageEvent) use (&$chat, $socketio) {
            $message = $messageEvent->getMessage();
            $chat->emit('update', $message);
            $socketio->emit('update', $message);
        });
$socketio
        ->listen(8080)
        ->onConnect(function(Connection $connection) use ($socketio) {
            list($host, $port) = $connection->getRemote();
            echo "connected $host:$port\n";
        })
        ->onRequest('/', function($connection, \EventHttpRequest $request) {
                $response = new Response(file_get_contents(__DIR__.'/web/index.html'));
                $response->setContentType('text/html', 'UTF-8');
                $connection->sendResponse($response);
        })
        ->onRequest('/socket.io.js', function($connection, \EventHttpRequest $request) {
                $response = new Response(file_get_contents(__DIR__.'/web/socket.io.js'));
                $response->setContentType('text/html', 'UTF-8');
                $connection->sendResponse($response);
        })
        ->dispatch();
