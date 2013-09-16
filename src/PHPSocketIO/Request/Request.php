<?php
namespace PHPSocketIO\Request;

use Symfony\Component\HttpFoundation;
use PHPSocketIO\ConnectionInterface;

/**
 * Description of Request
 *
 * @author ricky
 */
class Request extends HttpFoundation\Request
{
    protected $connection;

    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        return true;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
