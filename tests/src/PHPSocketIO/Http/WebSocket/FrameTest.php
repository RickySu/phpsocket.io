<?php
namespace PHPSocketIO\Http\WebSocket;

use PHPSocketIO\Http\WebSocket\Frame;

/**
 * Description of Message
 *
 * @author ricky
 */
class FrameTest extends \PHPUnit_Framework_TestCase
{
    public function test__close()
    {
        $data = md5(microtime().rand());
        $frameData = pack("CC", 0x88, strlen($data)).$data;
        $closeFrame = Frame::close($data);
        $this->assertEquals($frameData, $closeFrame->encode());
    }

    /**
     *  @dataProvider provider_test_generate
     */
    public function test_generate($expededEncodedData, $data)
    {
        $frame = Frame::generate($data);
        $this->assertEquals(md5($expededEncodedData), md5($frame->encode()));
    }

    public function provider_test_generate()
    {
        $tinyData = md5(microtime().rand());
        $tinyDataEncoded = pack("CC", 0x81, strlen($tinyData)).$tinyData;
        $mediumData = str_pad(md5(microtime().rand()), rand(126, 65535), md5(microtime().rand())); //16 bit
        $mediumDataEncoded = pack("CCn", 0x81, 126, strlen($mediumData)).$mediumData;
        $hugeData = str_pad(md5(microtime().rand()), rand(65536, 100000), md5(microtime().rand())); //64 bit
        $hugeDataEncoded = pack("CCNN", 0x81, 127, strlen($hugeData)>>32, strlen($hugeData) & 0xffffffff).$hugeData;
        return array(
            array($tinyDataEncoded, $tinyData),
            array($mediumDataEncoded, $mediumData),
            array($hugeDataEncoded, $hugeData),
        );
    }
}

