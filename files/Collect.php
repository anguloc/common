<?php
if (!function_exists('br2nl')) {
    function br2nl($string)
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    }
}

if (!function_exists('base32_encode')) {
    function base32_encode($input)
    {
        // base32 A - Z  2 - 7  ->  65 - 90  50 - 55
        $output = '';




        $len = 0;
        $bit = 0;
        $a = $b = $c = $d = $e = [];
        for ($i = 0; $i < strlen($input); $i++) {
            $bit <<= 8;
            $a[] = $ascii = ord($input[$i]);
//        $output .= $ascii & 0xf8; // 拿前5位

            $b[] = $ascii & 0xf8;
            $c[] = $ascii & 0x7;
            $f = $ascii >> 3;
            $s = $ascii << 5;

            if ($f < 26) {

            } elseif ($f >= 26 && $f <= 31) {

            } else {
                return false;
            }


            $len += 8;
            // 这里可能会循环两次
            while($bit >= 5){
                $bit -= 5;

            }


        }

        print_r($a);
        print_r($b);
        print_r($c);
        print_r($d);

        return $output;
    }
}

function asd($input){
    $BASE32_ALPHABET = 'abcdefghijklmnopqrstuvwxyz234567';
    $output = '';
    $v = 0;
    $vbits = 0;

    for ($i = 0, $j = strlen($input); $i < $j; $i++) {
        $v <<= 8;
        $v += ord($input[$i]);
        $vbits += 8;

        while ($vbits >= 5) {
            $vbits -= 5;
            $output .= $BASE32_ALPHABET[$v >> $vbits];
            $v &= ((1 << $vbits) - 1);
        }
    }

    if ($vbits > 0) {
        $v <<= (5 - $vbits);
        $output .= $BASE32_ALPHABET[$v];
    }

    return $output;
}

if (!function_exists('base32_decode')) {
    function base32_decode($input)
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