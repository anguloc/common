<?php

if (!function_exists('create_return')) {
    /**
     * 创建公共返回
     * @param int $code 错误码
     * @param mixed $result 返回结果
     * @param array $extra 额外数据
     * @param int $result_code 根据业务 细分错误码
     * @return array
     */
    function create_return($code = 0, $result = null, $extra = [], $result_code = 0)
    {
        $response = [
            'code' => $code,
            'result' => $result,
            'result_code' => $result_code,
        ];
        if ($extra) {
            $response = array_merge($response, $extra);
        }
        return $response;
    }
}

if (!function_exists('stdout')) {
    function stdout()
    {
        $message = func_get_args();
        if (count($message) == 1) {
            $message = $message[0];
        }
        $tile = '[' . date('Y-n-d H:i:s') . ']';
//        $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        $content = print_r($message, true);

        $content = explode("\n", $content);
        $send = "";
        foreach ($content as $value) {
            if (!empty($value)) {
                $echo = "[$tile] $value";
                echo " > $echo\n";
            }
        }
    }
}

if (!function_exists('catch_exception')) {
    function catch_exception(\Throwable $exception, $error = '')
    {
        $error .= '错误类型：' . get_class($exception) . PHP_EOL;
        $error .= '错误代码：' . $exception->getCode() . PHP_EOL;
        $error .= '错误信息：' . $exception->getMessage() . PHP_EOL;
        $error .= '错误堆栈：' . $exception->getTraceAsString() . PHP_EOL;
        return $error;
    }
}