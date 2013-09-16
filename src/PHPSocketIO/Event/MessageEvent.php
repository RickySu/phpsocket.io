<?php

namespace PHPSocketIO\Event;

use Symfony\Component\EventDispatcher\Event;
use PHPSocketIO\ConnectionInterface;

/**
 * Description of MessageEvent
 *
 * @author ricky
 */
class MessageEvent extends Event
{

    protected $message;
    protected $connection;

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getMessage()
    {
        return $this->message;
    }

}

