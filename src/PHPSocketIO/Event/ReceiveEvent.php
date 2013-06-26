<?php
namespace PHPSocketIO\Event;
use Symfony\Component\EventDispatcher\Event;
use PHPSocketIO\Connection;

/**
 * Description of RequestEvent
 *
 * @author ricky
 */
class ReceiveEvent extends Event{

    protected $connection;
    protected $message;

    public function __construct(Connection $connection, $message)
    {
        $this->connection = $connection;
        $this->message = $message;
    }

    public function __destruct() {
        $this->connection = null;
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
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

}

