<?php

namespace Flat3\Lodata\Helper;

class Base64
{
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

    protected $inputBuffer;
    protected $inputBufferPosition;
    protected $outputBuffer;

    public function __construct($data)
    {
        $this->inputBuffer = $data;
    }

    public function fread($length)
    {
        $result = substr($this->inputBuffer, $this->inputBufferPosition, $length);
        $this->inputBufferPosition += $length;

        return $result;
    }

    public function fwrite($buffer)
    {
        $this->outputBuffer .= $buffer;
    }

    public function get()
    {
        return $this->outputBuffer;
    }

    public function encode()
    {
        while ($buf = $this->fread(3)) {
            $pad = '';

            while (strlen($buf) < 3) {
                $buf .= "\0";
                $pad .= '=';
            }

            $grp = (ord($buf[0]) << 16) | (ord($buf[1]) << 8) | ord($buf[2]);
            $res = self::chars[($grp >> 18) & 0x3f];
            $res .= self::chars[($grp >> 12) & 0x3f];
            $res .= self::chars[($grp >> 6) & 0x3f];
            $res .= self::chars[($grp >> 0) & 0x3f];

            if (!$pad) {
                $this->fwrite($res);
            } else {
                $this->fwrite(substr($res, 0, strlen($res) - strlen($pad)).$pad);
            }
        }

        return $this;
    }

    public function decode()
    {
        $chars = array_flip(str_split(self::chars));

        while ($buf = $this->fread(4)) {
            $pad = ($buf[-1] == '=' ? ($buf[-2] == '=' ? 'AA' : 'A') : '');
            $buf = substr($buf, 0, strlen($buf) - strlen($pad)).$pad;

            $res = $chars[$buf[0]] << 18;
            $res += $chars[$buf[1]] << 12;
            $res += $chars[$buf[2]] << 6;
            $res += $chars[$buf[3]];

            $result = chr(($res >> 16) & 0xff).chr(($res >> 8) & 0xff).chr(($res & 0xff));

            if (!$pad) {
                $this->fwrite($result);
            } else {
                $this->fwrite(substr($result, 0, strlen($result) - strlen($pad)));
            }
        }

        return $this;
    }
}
