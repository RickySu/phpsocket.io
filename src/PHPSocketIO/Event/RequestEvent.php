<?php

namespace PHPSocketIO\Event;

use Symfony\Component\EventDispatcher\Event;
use PHPSocketIO\Request\Request;

/**
 * Description of RequestEvent
 *
 * @author ricky
 */
class RequestEvent extends Event
{

    protected $request;

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
    /**
     *
     * @return Request\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

}

