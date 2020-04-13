<?php

namespace DHelper\Libs\Tree;

use ArrayAccess;
use JsonSerializable;
use Serializable;

class RedBlackTree implements ArrayAccess,JsonSerializable,Serializable
{



    /**
     * @var array|\object
     */
    protected $item;

    public function __construct($data = [])
    {
        $this->init($data);
    }

    protected function init($data)
    {
        $this->validate($data);
    }

    protected function validate()
    {

    }

    protected function createNode()
    {
        return new class{
            protected $isRed = false;

            public function isRed()
            {

            }
        };
    }



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
}