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
class RequestEvent extends Event{

    protected $connection;
    protected $request;

    public function __construct(Connection $connection, Http\Request $request)
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
     * @return Http\Request
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

