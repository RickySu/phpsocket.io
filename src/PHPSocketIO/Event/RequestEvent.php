<?php
namespace PHPSocketIO\Event;
use Symfony\Component\EventDispatcher\Event;
use PHPSocketIO\Connection;
use PHPSocketIO\HTTP;

/**
 * Description of RequestEvent
 *
 * @author ricky
 */
class RequestEvent extends Event{

    public $connection;
    public $request;

    public function __construct($connection, HTTP\Request $request)
    {
        $this->connection = $connection;
        $this->request = $request;
    }

    public function __destruct() {
        $this->connection = null;
        $this->request = null;
    }

    /**
     *
     * @return HTTP\Request
     */
    public function getRequest()
    {
        return $this->request;
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

