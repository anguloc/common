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
        $secretkey = base32_decode($publicKey);
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

}