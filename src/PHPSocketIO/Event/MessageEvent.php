<?php

namespace PHPSocketIO\Event;

use Symfony\Component\EventDispatcher\Event;
use PHPSocketIO\Connection;

/**
 * Description of RequestEvent
 *
 * @author ricky
 */
class MessageEvent extends Event
{

    protected $message;
    protected $connection;

    public function __construct($message = null, Connection $connection = null)
    {
        $this->setMessage($message);
        $this->setConnection($connection);
    }

    public function setMessage($message = null)
    {
        $this->message = $message;
    }

    public function setConnection(Connection $connection = null)
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

