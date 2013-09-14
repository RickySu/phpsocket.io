<?php

namespace PHPSocketIO\Http\WebSocket;

/**
 * Description of Frame
 *
 * @author ricky
 */
class Frame
{

    const DECODE_STATUS_MORE_DATA = 0;
    const DECODE_STATUS_ERROR = -1;
    const OP_CONTINUE = 0;
    const OP_TEXT = 1;
    const OP_BINARY = 2;
    const OP_CLOSE = 8;
    const OP_PING = 9;
    const OP_PONG = 10;
    const MASK_LENGTH = 4;

    protected $data = '';
    protected $firstByte = null;
    protected $secondByte = 0;
    protected $isCoalesced = false;
    protected $extendedPayload = '';

    protected $isClosed = false;

    /**
     *
     * @return Frame
     */
    public static function parse($data)
    {
        if(!($data instanceof MessageQueue)){
            $data = new MessageQueue($data);
        }
        $frame = new static();
        $frameSize = $frame->decode($data);
        if(!$frame->isCoalesced()){
            return $frameSize;
        }
        $data->shift($frameSize);
        return $frame;
    }

    /**
     *
     * @param string $data
     * @return Frame
     */
    public static function generate($data)
    {
        $frame = new static($data);
        return $frame;
    }

    /**
     *
     * @param string $data
     * @return Frame
     */
    public static function close($data)
    {
        $frame = new static($data, true, static::OP_CLOSE);
        $frame->setClosed();
        return $frame;
    }

    protected function __construct($data = null, $final = true, $opcode = self::OP_TEXT)
    {
        $this->firstByte = ($final ? 0x80 : 0) + $opcode;
        $this->appendData($data);
    }

    public function setClosed($isClosed = true)
    {
        $this->isClosed = $isClosed;
    }

    public function isClosed()
    {
        return $this->isClosed;
    }

    public function getOpcode()
    {
        return $this->firstByte & 0x7f;
    }

    public function setFirstByte($byte)
    {
        $this->firstByte = $byte;
    }

    public function setSecondByte($byte)
    {
        $this->secondByte = $byte;
    }

    public function setData($data)
    {
        $this->data = '';
        $this->appendData($data);
    }

    public function appendData($data)
    {
        $this->data.=$data;
        $this->updatePayloadLength($this->data, $this->secondByte, $this->extendedPayload);
    }

    public function setMask($mask)
    {
        $mask &= 1;
        $this->secondByte |= ($mask << 7);
    }

    public function isMask()
    {
        return ($this->secondByte >> 7) == 1;
    }

    public function generateMaskingKey()
    {
        $maskingKey = '';
        for ($i = 0; $i < static::MASK_LENGTH; $i++) {
            $maskingKey .= pack('C', rand(0, 255));
        }
        return $maskingKey;
    }

    protected function applyMask($maskingKey, $payload = null)
    {
        $applied = '';
        for ($i = 0, $len = strlen($payload); $i < $len; $i++) {
            $applied .= $payload[$i] ^ $maskingKey[$i % static::MASK_LENGTH];
        }
        return $applied;
    }

    protected function maskPayload($payload)
    {
        if (!$this->isMask()) {
            return $payload;
        }
        $maskingKey = $this->generateMaskingKey();
        return $maskingKey . $this->applyMask($maskingKey, $payload);
    }

    public function getPayloadLength($encodedData)
    {
        $length = $this->secondByte & 0x7f;
        if ($length < 126) {
            return [$length, 0];
        }

        if ($length == 126) { // with 2 bytes extended payload length
            if (($packedPayloadLength = substr($encodedData, 2, 2)) === false) {
                return [0, 0];
            }
            return [unpack("n", $packedPayloadLength)[1] + 2, 2];
        }

        if ($length == 127) { //with 8 bytes extended payload length
            if (($packedPayloadLength = substr($encodedData, 2, 8)) === false) {
                return [0, 0];
            }
            $payloadLength = unpack("N2", $packedPayloadLength);
            return [($packedPayloadLength[1] << 32) | $packedPayloadLength[2] + 8, 8];
        }
    }

    public function decode($encodedData)
    {
        $this->isCoalesced = false;
        if (strlen($encodedData) <= 2) {
            return static::DECODE_STATUS_MORE_DATA;
        }
        $bytes = unpack("C2", $encodedData);
        $this->setFirstByte($bytes[1]);
        $this->setSecondByte($bytes[2]);

        if (!$this->verifyPayload()) {
            return static::DECODE_STATUS_ERROR;
        }
        list($payloadLength, $extendedPayloadBytes) = $this->getPayloadLength($encodedData);
        $totalFramLength = 2 + $payloadLength;
        if ($this->isMask()) {
            $totalFramLength += static::MASK_LENGTH;
        }
        if ($payloadLength == 0 || strlen($encodedData) < $totalFramLength) {
            return static::DECODE_STATUS_MORE_DATA;
        }
        $maskingKey = substr($encodedData, 2 + $extendedPayloadBytes, static::MASK_LENGTH);
        $data = $this->applyMask($maskingKey, substr($encodedData, 2 + $extendedPayloadBytes + static::MASK_LENGTH));
        $this->setData($data);
        if(strlen($encodedData) >= $totalFramLength){
            $this->isCoalesced = true;
        }
        return $totalFramLength;
    }

    public function isCoalesced()
    {
        return $this->isCoalesced;
    }

    public function getRSV1()
    {
        return ($this->firstByte & 4) > 0;
    }

    public function getRSV2()
    {
        return ($this->firstByte & 2) > 0;
    }

    public function getRSV3()
    {
        return ($this->firstByte & 1) > 0;
    }

    protected function verifyPayload()
    {
        if ($this->getRSV1() && $this->getRSV2() && $this->getRSV3()) {
            return false;
        }
        return true;
    }

    public function getData()
    {
        return $this->data;
    }

    public function encode()
    {
        $this->isCoalesced = true;
        return pack('CC', $this->firstByte, $this->secondByte) . $this->extendedPayload . $this->maskPayload($this->data);
    }

    protected function updatePayloadLength($data, &$secondByte, &$extendedPayload)
    {
        $secondByte &= 0x80;
        $size = strlen($data);
        if ($size < 126) {
            $secondByte |= $size;
            return;
        }

        if ($size <= 65535) {  //use 2 bytes extended payload
            $secondByte |= 126;
            $extendedPayload = pack("n", $size);
            return;
        }

        //use 4 bytes extended payload
        $secondByte |= 127;
        $extendedPayload = pack("NN", $size >> 32, $size & 0xffffffff);
    }

}
