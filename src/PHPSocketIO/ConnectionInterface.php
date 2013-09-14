<?php
namespace PHPSocketIO;

/**
 * Description of ConnectionInterface
 *
 * @author ricky
 */
interface ConnectionInterface
{
    public function getRequest();
    public function clearTimeout();
    public function write(Response\ResponseInterface $response, $shutdownAfterSend = false);
    public function getRemote();
}
