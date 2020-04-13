<?php
/**
 * Created by PhpStorm.
 * User: gk
 * Date: 2019/10/21
 * Time: 0:37
 */

use ArrayAccess;

/**
 * 这个是easySwoole的  有些地方没法用composer  就copy过来了
 *
 * Class JWT
 */
class JWT implements ArrayAccess,JsonSerializable,Serializable
{


    private $container; // 数据容器

    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    public function offsetGet($offset){
        return $this->container[$offset]??null;
    }

    public function offsetSet($offset, $value){
        return $this->container[$offset] = $value;
    }

    public function offsetUnset($offset){

    }

    public function serialize(){}

    public function unserialize($serialized){}

    public function jsonSerialize (){}









    const STATUS_OK = 1;
    const STATUS_SIGNATURE_ERROR = -1;
    const STATUS_EXPIRED = -2;
    protected $alg;//加密方式
    protected $iss = "EasySwoole";//发行人
    protected $exp; //到期时间
    protected $sub;//主题
    protected $nbf;//在此之前不可用
    protected $aud;//用户
    protected $iat;//发布时间
    protected $jti;//JWT ID用于标识该JWT
    protected $signature;
    protected $status = 0;
    protected $data;
    protected function initialize(): void
    {
        if(empty($this->nbf)){
            $this->nbf = time();
        }
        if(empty($this->iat)){
            $this->iat = time();
        }
        if(empty($this->exp)){
            $this->exp = time() + 7200;
        }
        if(empty($this->jti)){
            $this->jti = $this->random(10);
        }
        /*
         * 说明是解包的
         */
        if(!empty($this->signature)){
            if($this->signature !== Jwt::getInstance()->__signature($this)){
                $this->status = self::STATUS_SIGNATURE_ERROR;
                return;
            }
            if(time() > $this->exp){
                $this->status = self::STATUS_EXPIRED;
                return;
            }
        }
        $this->status = self::STATUS_OK;
    }

    protected function random($length = 6, $alphabet = 'AaBbCcDdEeFfGgHhJjKkMmNnPpQqRrSsTtUuVvWwXxYyZz23456789')
    {
        /*
         * mt_srand() is to fix:
            mt_rand(0,100);
            if(pcntl_fork()){
                var_dump(mt_rand(0,100));
            }else{
                var_dump(mt_rand(0,100));
            }
         */
        mt_srand();
        // 重复字母表以防止生成长度溢出字母表长度
        if ($length >= strlen($alphabet)) {
            $rate = intval($length / strlen($alphabet)) + 1;
            $alphabet = str_repeat($alphabet, $rate);
        }

        // 打乱顺序返回
        return substr(str_shuffle($alphabet), 0, $length);
    }

    /**
     * @return mixed
     */
    public function getAlg()
    {
        return $this->alg;
    }
    /**
     * @param mixed $alg
     */
    public function setAlg($alg): void
    {
        $this->alg = $alg;
    }
    /**
     * @return string
     */
    public function getIss(): string
    {
        return $this->iss;
    }
    /**
     * @param string $iss
     */
    public function setIss(string $iss): void
    {
        $this->iss = $iss;
    }
    /**
     * @return mixed
     */
    public function getExp()
    {
        return $this->exp;
    }
    /**
     * @param mixed $exp
     */
    public function setExp($exp): void
    {
        $this->exp = $exp;
    }
    /**
     * @return mixed
     */
    public function getSub()
    {
        return $this->sub;
    }
    /**
     * @param mixed $sub
     */
    public function setSub($sub): void
    {
        $this->sub = $sub;
    }
    /**
     * @return mixed
     */
    public function getNbf()
    {
        return $this->nbf;
    }
    /**
     * @param mixed $nbf
     */
    public function setNbf($nbf): void
    {
        $this->nbf = $nbf;
    }
    /**
     * @return mixed
     */
    public function getAud()
    {
        return $this->aud;
    }
    /**
     * @param mixed $aud
     */
    public function setAud($aud): void
    {
        $this->aud = $aud;
    }
    /**
     * @return mixed
     */
    public function getIat()
    {
        return $this->iat;
    }
    /**
     * @param mixed $iat
     */
    public function setIat($iat): void
    {
        $this->iat = $iat;
    }
    /**
     * @return mixed
     */
    public function getJti()
    {
        return $this->jti;
    }
    /**
     * @param mixed $jti
     */
    public function setJti($jti): void
    {
        $this->jti = $jti;
    }
    /**
     * @return mixed
     */
    public function getSignature()
    {
        return $this->signature;
    }
    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }
    /**
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }
    public function getData()
    {
        return $this->data;
    }
    function __toString()
    {
        $this->signature = Jwt::getInstance()->__signature($this);
        $array = $this->toArray();
        return urlencode(base64_encode(json_encode($array,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)));
    }
}