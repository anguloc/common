<?php

namespace DHelper\Libs\Tree;


abstract class RBNode extends Node
{
    /**
     * 是否为红色
     * @var bool
     */
    protected $isRed = true;

    public function isRed()
    {
        return $this->isRed;
    }

    public function setRed()
    {
        $this->isRed = true;
    }

    public function setBlack()
    {
        $this->isRed = false;
    }

}