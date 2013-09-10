<?php
namespace PHPSocketIO\Event;
use Symfony\Component\EventDispatcher\Event;
use PHPSocketIO\Connection;
use PHPSocketIO\Http;

/**
 * Description of RequestEvent
 *
 * @author ricky
 */
class MessageEvent extends Event
{

    protected $message;

    public function __construct($message)
    {
        $this->setMessage($message);
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    /**
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

}

