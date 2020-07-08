<?php

namespace DHelper\Libs\Tree;

/**
 * Class RedBlackTree
 * @package DHelper\Libs\Tree
 * @method rotateToBalance()
 */
class RedBlackTree extends BinarySearchTree
{
    protected function createNode($value, Node $node = null)
    {
        return new class($value, $node) extends Node
        {
            /**
             * 是否为红色
             * @var bool
             */
            protected $isRed = true;

            public function __construct($value, Node $node = null)
            {
                $this->setValue($value);
                if ($node) {
                    $this->setParentNode($node);
                }
            }
        };
    }

    /**
     * 插入节点 增加旋转平衡功能
     *
     * @param int $value
     * @return bool
     */
    public function insert($value): bool
    {
        $this->setCurrentNode($this->root);
        $result = $this->_insert($value);
        if (!$result) {
            return $result;
        }

        // 旋转至平衡
        $this->rotateToBalance();
        $this->setCurrentNode();
        return $result;
    }

    /**
     * 删除节点 增加旋转平衡功能
     *
     * @param $value
     * @return Node|null
     */
    public function delete($value)
    {
        $result = parent::_delete($value);
        if (!$result) {
            return $result;
        }

        // 旋转至平衡
        $this->rotateToBalance();
        $this->setCurrentNode();
        return $result;
    }

    /**
     * 旋转至平衡 这里只考虑简单实现 不准备优化
     */
    protected function _rotateToBalance()
    {
        $ll = $lr = $rl = $rr = -1;
        $root = $this->currentNode === null ? $this->root : $this->currentNode;
        if ($root->getLeftNode()) {
            $ll = $lr = 0;
            if ($root->getLeftNode()->getLeftNode()) {
                $ll = $this->setCurrentNode($root->getLeftNode()->getLeftNode())->height();
            }
            if ($root->getLeftNode()->getRightNode()) {
                $lr = $this->setCurrentNode($root->getLeftNode()->getRightNode())->height();
            }
        }

        if ($root->getRightNode()) {
            $rl = $rr = 0;
            if ($root->getRightNode()->getLeftNode()) {
                $rl = $this->setCurrentNode($root->getRightNode()->getLeftNode())->height();
            }
            if ($root->getRightNode()->getRightNode()) {
                $rr = $this->setCurrentNode($root->getRightNode()->getRightNode())->height();
            }
        }

        $l = max($ll, $lr);
        $r = max($rl, $rr);
        $rt = $l - $r;
        if ($rt > 1) {
            if ($ll - $r > 1) {
                // ll
                $this->setCurrentNode($root)->rightRotate();
            } else {
                // lr
                $this->setCurrentNode($root->getLeftNode())->leftRotate();
                $this->setCurrentNode($root)->rightRotate();
            }
        } elseif ($rt < -1) {
            if ($rl - $l > 1) {
                // rl
                $this->setCurrentNode($root->getRightNode())->rightRotate();
                $this->setCurrentNode($root)->leftRotate();
            } else {
                // rr
                $this->setCurrentNode($root)->leftRotate();
            }
        }

        if ($root->getParentNode()) {
            $this->setCurrentNode($root->getParentNode())->_rotateToBalance();
        }
    }

}