<?php
namespace PHPSocketIO\Adapter;

use PHPSocketIO\Connection;

interface ProtocolProcessorInterface
{
    public function __construct(Connection $connection);
    public function onReceive($reveiceMessage);
    public function onWriteBufferEmpty();
    public function __destruct();
}
