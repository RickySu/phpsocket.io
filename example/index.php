<?php

$loader = include '../vendor/autoload.php';
$loader->add('PHPSocketIO', __DIR__ . '/../src');

use PHPSocketIO\SocketIO;
use PHPSocketIO\Connection;
use PHPSocketIO\Response\Response;
use PHPSocketIO\Event;

$socketio = new SocketIO();
$chat = $socketio
        ->of('/chat')
        ->on('addme', function(Event\MessageEvent $messageEvent) use(&$chat){
            $messageEvent->getConnection()->emit('update', array('msg' => "歡迎登入 {$messageEvent->getMessage()}"));
            $chat->emit("update", array('msg' => "{$messageEvent->getMessage()} 進入聊天室了"));
        })
        ->on('msg', function(Event\MessageEvent $messageEvent) use(&$chat, $socketio){
            $message = $messageEvent->getMessage();
            $chat->emit('update', $message);
            $socketio->emit('update', $message);
        });
$socketio
        ->listen(8080)
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
