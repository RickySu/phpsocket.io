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
        ->onConnect(function(Connection $connection) {
            $connection->on('request.session', function(Event\ReturnEvent $event){
                $event->setReturn(md5(microtime().rand().uniqid()));
            });
            $connection->on('request', function(Event\RequestEvent $event) {
                $connection = $event->getConnection();
                //$connection->write(new Response("hello world!"), true);
                $connection->sendResponse(new Response("hello world!"));
                //$connection->write(new Response("hello world!"), true);
            });
        })->dispatch();
