<?php

if(!function_exists('createReturn')){
    /**
     * 创建公共返回
     * @param int $code 错误码
     * @param array $result 返回结果
     * @param int $result_code 根据业务 细分错误码
     * @param array $extra 额外数据
     * @return array
     */
    function createReturn($code = 0, $result = [], $result_code = 0, $extra = []){
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

if(!function_exists('stdout')){
    function stdout(){
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