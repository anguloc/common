<?php

namespace DHelper\Libs\Tree\BinarySearchTree;

class BinarySearchTree implements \JsonSerializable
{
    /**
     * @var BinarySearchNode|null
     */
    protected $root;

    public function __construct($data = [], BinarySearchNode $node = null)
    {
        if (empty($data) && $data !== 0) {
            return;
        }
        if (!is_array($data) && !is_object($data)) {
            $data = [$data];
        }
        if (count($data) != count($data, true)) {
            return;
        }

        foreach ($data as $datum) {
            $this->insert($datum);
        }

        if($this->root && $node){
            $this->root->setParentNode($node);
        }
    }

    /**
     * 插入节点
     */
    public function insert($value): void
    {
        if (empty($value) && $value !== 0 || !is_numeric($value)) {
            return;
        }

        if ($this->root == null) {
            $this->root = new BinarySearchNode($value);
        } else {
            if ($value < $this->root->getValue()) {
                if ($this->root->getLeftTree() == null) {
                    $this->root->setLeftTree(new static($value, $this->root));
                } else {
                    $this->root->getLeftTree()->insert($value);
                }
            } elseif ($value > $this->root->getValue()) {
                if ($this->root->getRightTree() == null) {
                    $this->root->setRightTree(new static($value, $this->root));
                } else {
                    $this->root->getRightTree()->insert($value);
                }
            }
        }
    }

    /**
     * 删除节点
     * @param $value
     */
    public function delete($value)
    {
        if ($this->root == null || !is_numeric($value) || !$value instanceof BinarySearchNode) {
            return;
        }
        $node = $this->search($value);
        if (!$node) {
            return;
        }

        // 没子树 直接删除
        if(1){

        }
        //
    }

    public function height()
    {
        if ($this->root == null) {
            return 0;
        }
        $left = $this->root->getLeftTree() ? $this->root->getLeftTree()->height() : 0;
        $right = $this->root->getRightTree() ? $this->root->getRightTree()->height() : 0;

        return max($left, $right) + 1;
    }

    public function count()
    {
        if ($this->root == null) {
            return 0;
        }
        $left = $this->root->getLeftTree() ? $this->root->getLeftTree()->count() : 0;
        $right = $this->root->getRightTree() ? $this->root->getRightTree()->count() : 0;

        return $left + $right + 1;
    }

    public function leftRotate()
    {

    }

    public function rightRotate()
    {

    }

    public function jsonSerialize()
    {
        return json_encode($this->preOrder());
    }

    public function __toString()
    {
        return json_encode($this->preOrder());
    }

    /**
     * 先序遍历
     */
    public function preOrder()
    {
        $result = [];

        if ($this->root != null) {
            array_push($result, $this->root->getValue());

            if ($this->root->getLeftTree()) {
                $result = array_merge($result, $this->root->getLeftTree()->preOrder());
            }

            if ($this->root->getRightTree()) {
                $result = array_merge($result, $this->root->getRightTree()->preOrder());
            }
        }

        return $result;
    }

    /**
     * 中序遍历
     */
    public function middleOrder()
    {
        $result = [];

        if ($this->root != null) {
            if ($this->root->getLeftTree()) {
                $result = array_merge($result, $this->root->getLeftTree()->middleOrder());
            }

            array_push($result, $this->root->getValue());

            if ($this->root->getRightTree()) {
                $result = array_merge($result, $this->root->getRightTree()->middleOrder());
            }
        }

        return $result;
    }

    /**
     * 后序遍历
     */
    public function afterOrder()
    {
        $result = [];

        if ($this->root != null) {
            if ($this->root->getLeftTree()) {
                $result = array_merge($result, $this->root->getLeftTree()->middleOrder());
            }

            if ($this->root->getRightTree()) {
                $result = array_merge($result, $this->root->getRightTree()->middleOrder());
            }

            array_push($result, $this->root->getValue());
        }

        return $result;
    }

    /**
     * 查找一个值
     */
    public function search($value, &$i = 0)
    {
        if (!is_numeric($value)) {
            return false;
        }
        if ($this->root === null) {
            return false;
        }
        if ($value instanceof BinarySearchNode) {
            $value = $value->getValue();
        }

        $i++;
        if ($value == $this->root->getValue()) {
            return $this->root;
        } elseif ($this->root->getLeftTree() && $value < $this->root->getValue()) {
            return $this->root->getLeftTree()->search($value, $i);
        } elseif ($this->root->getRightTree() && $value > $this->root->getValue()) {
            return $this->root->getRightTree()->search($value, $i);
        }
        return false;
    }

    /**
     * 获取最小值
     */
    public function min()
    {
        if ($this->root->getLeftTree()) {
            return $this->root->getLeftTree()->min();
        }
        return $this->root->getValue();
    }

    /**
     * 获取最大值
     */
    public function max()
    {
        if ($this->root->getRightTree()) {
            return $this->root->getRightTree()->max();
        }
        return $this->root->getValue();
    }


}