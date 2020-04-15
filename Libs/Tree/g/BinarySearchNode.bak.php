<?php

namespace DHelper\Libs\Tree\BinarySearchTree;


class BinarySearchNodebak
{
    protected $value;

    /**
     * @var BinarySearchTree|null
     */
    protected $leftTree;

    /**
     * @var BinarySearchTree|null
     */
    protected $rightTree;

    /**
     * @var BinarySearchNode|null
     */
    protected $parentNode;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * 获取当前节点值
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * 获取左子树
     * @return BinarySearchTree|null
     */
    public function getLeftTree()
    {
        return $this->leftTree;
    }

    /**
     * 设置左子树
     * @param BinarySearchTree $tree
     */
    public function setLeftTree(BinarySearchTree $tree)
    {
        $this->leftTree = $tree;
    }

    /**
     * 获取右子树
     * @return BinarySearchTree|null
     */
    public function getRightTree()
    {
        return $this->rightTree;
    }

    /**
     * 设置右子树
     */
    public function setRightTree(BinarySearchTree $tree)
    {
        $this->rightTree = $tree;
    }

    /**
     * 获取父节点
     * @return self|null
     */
    public function getParentNode()
    {
        return $this->parentNode;
    }

    /**
     * 设置父节点
     */
    public function setParentNode(self $node)
    {
        $this->parentNode = $node;
    }


}