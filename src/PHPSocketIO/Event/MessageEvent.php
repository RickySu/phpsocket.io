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
        return true;
    }

    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        return true;
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

