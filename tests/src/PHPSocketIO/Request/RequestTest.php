<?php
namespace PHPSocketIO\Request;
use PHPSocketIO\ConnectionInterface;

class MockConnection implements ConnectionInterface
{
    public function clearTimeout()
    {

    }

    public function getRemote()
    {

    }

    public function getRequest()
    {

    }

    public function setRequest(Request $request)
    {

    }

    public function write(\PHPSocketIO\Response\ResponseInterface $response, $shutdownAfterSend = false)
    {

    }
    public function getSessionId()
    {
        return '';
    }
}

/**
 * Description of RequestTest
 *
 * @author ricky
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    public function test_setConnection()
    {
        $request = new Request();
        $this->assertTrue($request->setConnection(new MockConnection()));
    }

    public function test_getConnection()
    {
        $request = new Request();
        $connection = new MockConnection();
        $request->setConnection($connection);
        $this->assertEquals($connection, $request->getConnection());
    }

}
