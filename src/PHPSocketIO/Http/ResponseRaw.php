<?php
namespace PHPSocketIO\Http;

/**
 * Description of Request
 *
 * @author ricky
 */
class ResponseRaw implements ResponseInterface {

    protected $content;

    public function __construct($content) {
        $this->content = $content;
    }

    public function __toString() {
        return $this->content;
    }

}
