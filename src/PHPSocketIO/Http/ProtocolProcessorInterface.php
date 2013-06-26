<?php
namespace PHPSocketIO\Http;

use PHPSocketIO\Connection;
use PHPSocketIO\Event;

interface ProtocolProcessorInterface
{
    public function __construct(Connection $connection);
}
