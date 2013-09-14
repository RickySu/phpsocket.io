<?php
namespace PHPSocketIO\Http\WebSocket;

/**
 * Description of Message
 *
 * @author ricky
 */
class MessageQueue
{
    protected $data = '';

    public function clear()
    {
        $this->data = '';
    }
    public function add($data)
    {
        $this->data.=$data;
    }

    public function __toString()
    {
        return $this->getAll();
    }

    public function getAll()
    {
        return $this->data;
    }

    public function shift($size)
    {
        if($size > strlen($this->data)){
            return false;
        }

        $message = substr($this->data, 0, $size);

        $this->data = substr($this->data, $size);
        return $message;
    }
}

