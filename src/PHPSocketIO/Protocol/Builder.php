<?php

namespace PHPSocketIO\Protocol;

class Builder
{
    public static function Event($data)
    {
        return "5:::".json_encode($data);
    }

    public static function Connect()
    {
        return '1::';
    }

    public static function Noop()
    {
        return '8::';
    }
}
