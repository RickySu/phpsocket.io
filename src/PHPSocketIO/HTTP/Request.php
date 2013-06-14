<?php
namespace PHPSocketIO\HTTP;

use Symfony\Component\HttpFoundation;

/**
 * Description of Request
 *
 * @author ricky
 */
class Request extends HttpFoundation\Request {
    const EVENT_HTTP_REQUEST = 'event.http.request';
}
