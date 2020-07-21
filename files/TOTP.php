<?php

namespace DHelper\files;

/**
 * TOTP算法 这里只是记录下
 *
 * Class TOTP
 * @package DHelper\files
 */
class TOTP
{

    public function test()
    {

        $publicKey = "ZCEY4IVHXJOCED75TKNVZ4AFSMUIXBTQONGDHD4M2HRKHVY4I6TRFG7VNU4M5TXH";
        $interval = 30;
        $su = $interval - microtime(true) % $interval;
        $timestamp = floor(microtime(true) / $interval);
        $secretkey = $this->base32_decode($publicKey);
        $bin_counter = pack('N*', 0) . pack('N*', $timestamp);
        $hash = hash_hmac('sha1', $bin_counter, $secretkey, true);
        $offset = ord($hash[19]) & 0xf;
        $hash = (
                ((ord($hash[$offset + 0]) & 0x7f) << 24) |
                ((ord($hash[$offset + 1]) & 0xff) << 16) |
                ((ord($hash[$offset + 2]) & 0xff) << 8) |
                (ord($hash[$offset + 3]) & 0xff)
            ) % pow(10, 6);
        $otp = str_pad($hash, 6, '0', STR_PAD_LEFT);


        echo("Init key: $publicKey\n");
        echo("Timestamp: $timestamp\n");
        echo("su: $su\n");
        echo("One time password: $otp\n");
    }

    /**
     * 丑陋的实现 不过也是实现
     *
     * @param $input
     * @return string
     */
    public function base32_decode($input)
    {
        $input = strtolower($input);
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0, $j = strlen($input); $i < $j; $i++) {
            $v <<= 5;
            if ($input[$i] >= 'a' && $input[$i] <= 'z') {
                $v += (ord($input[$i]) - 97);
            } elseif ($input[$i] >= '2' && $input[$i] <= '7') {
                $v += (24 + $input[$i]);
            } else {
                echo 23123;
                die(1);
            }

            $vbits += 5;
            while ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr($v >> $vbits);
                $v &= ((1 << $vbits) - 1);
            }
        }
        return $output;
    }

}