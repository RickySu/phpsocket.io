<?php
namespace PHPSocketIO\Adapter;

use PHPSocketIO\Connection;

interface ProtocolProcessorInterface
{
    /**
     * @return Connection
     */
    public function getConnection();

    public function __construct(Connection $connection);
    public function onReceive($reveiceMessage);
    public function onWriteBufferEmpty();
    public function __destruct();
}
