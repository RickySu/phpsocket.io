<?php

namespace PHPSocketIO\Event;

use PHPSocketIO\Connection;

class Dispatcher
{
    const STOP_PROPAGATE = 'STOP';

    static public function dispatch($event, Connection $connection = null)
    {
        $params = func_get_args();
        array_shift($params);
        array_shift($params);
        $callbacks = Holder::get($event, $connection);
        foreach($callbacks as $callback){
            if(call_user_func_array($callback, $params)==self::STOP_PROPAGATE){
                break;
            }
        }
    }

    static public function brocast($event, Connection $connection = null, $data)
    {
        $params = func_get_args();
        array_shift($params);
        array_shift($params);
        $callbacks = Holder::get($event, $connection);
        foreach($callbacks as $callback){
            call_user_func_array($callback, $params);
        }
    }

}