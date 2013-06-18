<?php
namespace PHPSocketIO\Http;

use PHPSocketIO\Connection;

interface ProtocolProcessorInterface
{
    public function __construct(Connection $connection);
    public function onReceive($reveiceMessage);
    public function onWriteBufferEmpty();
    public function free();
}
