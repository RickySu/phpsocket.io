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

    public static function parse(MessageQueue $messageQueue)
    {
        $frame = new static();
        $data = $messageQueue->dequeue();
        $frame->decode($data);
        $frame->getPayloadLength();
        return $frame;
    }

    public static function generate($data)
    {
        $frame = new static($data);
    }

    public function __construct($data = null, $final = true, $opcode = self::OP_TEXT)
    {
        $this->firstByte = ($final ? 0x80 : 0) + $opcode;
        $this->appendData($data);
    }

    public function setFirstByte($byte)
    {
        $this->firstByte = $byte;
    }

    public function setSecondByte($byte)
    {
        $this->secondByte = $byte;
    }

    public function appendData($data)
    {
        $this->data.=$data;
        $this->secondByte &= 0x80;
        $this->secondByte += strlen($this->data);
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

    public function getPayloadLength()
    {
        return $this->secondByte & 0x7f;
    }

    public function decode($encodedData)
    {
        if(strlen($encodedData) <=2 ){
            return static::DECODE_STATUS_MORE_DATA;
        }
        $bytes = unpack("C2", $encodedData);
        $this->setFirstByte($bytes[1]);
        $this->setSecondByte($bytes[2]);
        if(!$this->verifyPayload()){
            return static::DECODE_STATUS_ERROR;
        }
        $totalFramLength = 2 + $this->getPayloadLength();
        if($this->isMask()){
            $totalFramLength += static::MASK_LENGTH;
        }
        if(strlen($encodedData) < $totalFramLength){
            return static::DECODE_STATUS_MORE_DATA;
        }

        $maskingKey = substr($encodedData, 2, static::MASK_LENGTH );
        $data = $this->applyMask($maskingKey, substr($encodedData, 2 + static::MASK_LENGTH));
        $this->appendData($data);
        return $totalFramLength;
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
        if($this->getRSV1() && $this->getRSV2() && $this->getRSV3()){
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
        return pack('CC', $this->firstByte, $this->secondByte) . $this->maskPayload($this->data);
    }

}
