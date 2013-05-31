<?php

namespace PHPSocketIO\Event;

use PHPSocketIO\Connection;

class Holder
{
    static protected $connectionEvents=[];
    static protected $eventQueue=[];

    static public function register($event, Connection $connection, $callback)
    {
        self::$eventQueue[$event][$connection->getAddress()][] = $callback;
        self::$connectionEvents[$connection->getAddress()][$event] = true;
    }

    static public function get($event, Connection $connection = null)
    {
        if($connection){
            return self::$eventQueue[$event][$connection->getAddress()];
        }

        $callbacks=[];

        array_walk_recursive(self::$eventQueue[$event], function($callback, $key) use(&$callbacks){
            $callbacks[]=$callback;
        });
        
        return $callbacks;
    }

    static public function unRegister($event, Connection $connection)
    {
        $address = $connection->getAddress();
        if(!isset(self::$eventQueue[$event][$address])){
            return;
        }
        unset(self::$eventQueue[$event][$address]);
        unset(self::$connectionEvents[$address][$event]);
    }

    static public function unRegisterAllEvent(Connection $connection)
    {
        $address = $connection->getAddress();
        foreach(self::$connectionEvents[$address] as $event => $registed){
            unset(self::$eventQueue[$event][$address]);
        }
        unset(self::$connectionEvents[$address]);
    }

}
