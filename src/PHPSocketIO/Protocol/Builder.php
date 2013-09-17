<?php

namespace PHPSocketIO\Protocol;

class Builder
{
    public static function Event($data, $endpoint = null)
    {
        return "5::$endpoint:".json_encode($data);
    }

    public static function Connect()
    {
        return '1::';
    }

    public static function Heartbeat()
    {
        return '2::';
    }

    public static function Noop()
    {
        return '8::';
    }
}
