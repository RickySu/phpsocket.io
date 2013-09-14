<?php
namespace PHPSocketIO\Http\WebSocket;
use PHPSocketIO\Request\Request;
use PHPSocketIO\Response\ResponseWebSocket;

/**
 * Description of WebSocketTest
 *
 * @author ricky
 */
class WebSocketTest extends \PHPUnit_Framework_TestCase
{
    /**
     *  @dataProvider provider_test_getHandshakeReponse
     */
    public function test_getHandshakeReponse($key, $acceptKey)
    {
        $request = new Request();
        $request->headers->set('Upgrade', 'websocket');
        $request->headers->set('Sec-WebSocket-Version', 13);
        $request->headers->set('Sec-WebSocket-Key', $key);
        $websocket = new WebSocket();
        $response = $websocket->getHandshakeReponse($request);
        $this->assertTrue($response instanceof ResponseWebSocket);
        $this->assertEquals($acceptKey, $response->headers->get('Sec-WebSocket-Accept'));
    }

    public function provider_test_getHandshakeReponse()
    {
        return array(
            array('8MXSz0cH4mjNrI2d+w9Mbw==', 'JDvj7/uPnMEWPrAUvYR9SD+T2XI='),
            array('MAMGHM22VDXYF+s6CW2RUw==', 'TG3bsTWUBOalA6WXocR+xP4DSA0='),
        );
    }
}
