<?php

namespace DHelper\Libs\Tree;


abstract class Node
{

    protected $value;

    /**
     * @var self
     */
    protected $leftNode;

    /**
     * @var self
     */
    protected $rightNode;

    /**
     * @var self
     */
    protected $parentNode;

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function getLeftNode()
    {
        return $this->leftNode;
    }

    public function setParentNode(self $node = null)
    {
        $this->parentNode = $node;
        return $this;
    }

    public function getParentNode()
    {
        return $this->parentNode;
    }

    public function setLeftNode(self $node = null)
    {
        $this->leftNode = $node;
        return $this;
    }

    public function getRightNode()
    {
        return $this->rightNode;
    }

    public function setRightNode(self $node = null)
    {
        $this->rightNode = $node;
        return $this;
    }

}