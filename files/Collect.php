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
        $bytes = $len = 0;
        for ($i = 0; $i < strlen($input); $i++) {
            $bytes <<= 8;
            $bytes += ord($input[$i]);
            $len += 8;
            // 这里最多循环三次
            while ($len >= 5) {
                // 取前5位
                $len -= 5;
                $ascii = $bytes >> $len;
                $output .= $ascii < 26 ? chr($ascii + 65) : chr($ascii + 24);

                // 去掉前5位
                $bytes &= ((1 << $len) - 1);
            }
        }

        if ($len > 0) {
            $bytes <<= (5 - $len);
            $output .= $bytes < 26 ? chr($bytes + 65) : chr($bytes + 24);
            // 8 16 24 32  补等号
            if ($len == 1) {
                $output .= "====";
            } elseif ($len == 2) {
                $output .= "=";
            } elseif ($len == 3) {
                $output .= "======";
            } else {
                $output .= "===";
            }
        }


        return $output;
    }
}

if (!function_exists('base32_decode')) {
    function base32_decode($input)
    {
        $input = strtoupper(rtrim($input, '='));
        $output = '';
        $bytes = 0;
        $len = 0;

        for ($i = 0, $j = strlen($input); $i < $j; $i++) {
            $bytes <<= 5;
            if ($input[$i] >= 'A' && $input[$i] <= 'Z') {
                $bytes += (ord($input[$i]) - 65);
            } elseif ($input[$i] >= '2' && $input[$i] <= '7') {
                $bytes += (ord($input[$i]) - 24);
            } else {
                return false;
            }

            $len += 5;
            while ($len >= 8) {
                $len -= 8;
                $output .= chr($bytes >> $len);
                $bytes &= ((1 << $len) - 1);
            }
        }
        return $output;
    }
}
