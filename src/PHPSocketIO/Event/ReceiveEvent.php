<?php
namespace PHPSocketIO\Event;
use Symfony\Component\EventDispatcher\Event;
use PHPSocketIO\ConnectionInterface;

/**
 * Description of ReceiveEvent
 *
 * @author ricky
 */
class ReceiveEvent extends Event{

    protected $connection;
    protected $message;

    public function __construct(ConnectionInterface $connection, $message)
    {
        $this->connection = $connection;
        $this->message = $message;
    }
    
    /**
     *
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     *
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

}

