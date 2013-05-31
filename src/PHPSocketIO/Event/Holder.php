<?php

namespace PHPSocketIO\Event;

use PHPSocketIO\Connection;

class Holder
{
    static protected $connectionEvents=[];
    static protected $eventQueue=[];

    static public function register($event, Connection $connection, $callback)
    {
        static::$eventQueue[$event][$connection->getAddress()][] = $callback;
        static::$connectionEvents[$connection->getAddress()][$event] = true;
    }

    static public function get($event, Connection $connection = null)
    {
        if($connection){
            return static::$eventQueue[$event][$connection->getAddress()];
        }

        $callbacks=[];

        array_walk_recursive(static::$eventQueue[$event], function($callback, $key) use(&$callbacks){
            $callbacks[]=$callback;
        });

        return $callbacks;
    }

    static public function unRegister($event, Connection $connection)
    {
        $address = $connection->getAddress();
        if(!isset(static::$eventQueue[$event][$address])){
            return;
        }
        unset(static::$eventQueue[$event][$address]);
        unset(static::$connectionEvents[$address][$event]);
    }

    static public function unRegisterAllEvent(Connection $connection)
    {
        $address = $connection->getAddress();
        foreach(static::$connectionEvents[$address] as $event => $registed){
            unset(static::$eventQueue[$event][$address]);
        }
        unset(static::$connectionEvents[$address]);
    }

}
