<?php

namespace PHPSocketIO\Protocol;

class Builder
{
    public static function Connect()
    {
        return '1::';
    }

    public static function Noop()
    {
        return '8::';
    }
}
