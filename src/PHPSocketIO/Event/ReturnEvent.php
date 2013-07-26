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
class ReturnEvent extends Event
{

    protected $return;

    public function setReturn($return)
    {
        $this->return = $return;
        $this->stopPropagation();
    }

    public function getReturn()
    {
        return $this->return;
    }
}

