# PHPSocket.io

socket.io php server side alternative.

## Install

The recommended way to install phpsocket.io is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "rickysu/phpsocket.io": "dev-master"
    }
}
```

## What is it?

PHPSocket.io is a socket.io php server side alternative.
The event loop is based on pecl event extension.
http://php.net/manual/en/book.event.php

## Requirement

pecl/event

## Usage

see example/*

## Tests

To run the test suite, you need install the dependencies via composer, then
run PHPUnit.

    $ composer install
    $ phpunit

## License

MIT, see LICENSE.

## TODO

* add more session handler support.
* event queue.
* more tests.
