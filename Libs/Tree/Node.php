<?php

namespace DHelper\Libs\Tree;


abstract class Node
{

    /**
     * 其实这里可以加上节点所在高度、为左、右、根节点等杂项属性，方便搜索和旋转
     * 不过这里只是简单实现，就不写那么多了
     */

    /**
     * @var int
     */
    protected $value;

    /**
     * @var self
     */
    protected $leftNode;

    /**
     * @var static
     */
    protected $rightNode;

    /**
     * @var static
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