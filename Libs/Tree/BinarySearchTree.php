<?php

namespace DHelper\Libs\Tree;

/**
 * Class BinarySearchTree
 * @package DHelper\Libs\Tree
 * @method bool insert(int $value)
 * @method Node|null delete(int $value)
 * @method Node|null max()
 * @method Node|null min()
 * @method int count()
 * @method int height()
 * @method Node|null search($value)
 * @method array preOrder()
 * @method array middleOrder()
 * @method array afterOrder()
 * @method array levelOrder()
 * @method array levelBottomOrder()
 */
class BinarySearchTree implements \JsonSerializable
{
    /**
     * 根节点
     *
     * @var Node|null
     */
    protected $root;

    /**
     * 当前用于活动的节点
     *
     * @var Node|null
     */
    protected $currentNode;

    public function __construct($data = [])
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

    }

    /**
     * 创建节点
     *
     * @param $value
     * @param Node|null $node
     * @return Node
     */
    protected function createNode($value, Node $node = null)
    {
        return new class($value, $node) extends Node
        {
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
     * 插入节点
     *
     * @param int $value
     * @return bool
     */
    protected function _insert($value): bool
    {
        if (empty($value) && $value !== 0 || !is_numeric($value)) {
            return false;
        }

        if ($this->root == null) {
            $this->root = $this->createNode($value);
        } else {
            if ($value < $this->currentNode->getValue()) {
                if ($this->currentNode->getLeftNode() == null) {
                    $this->currentNode->setLeftNode($this->createNode($value, $this->currentNode));
                } else {
                    $this->setCurrentNode($this->currentNode->getLeftNode())->_insert($value);
                }
            } elseif ($value > $this->currentNode->getValue()) {
                if ($this->currentNode->getRightNode() == null) {
                    $this->currentNode->setRightNode($this->createNode($value, $this->currentNode));
                } else {
                    $this->setCurrentNode($this->currentNode->getRightNode())->_insert($value);
                }
            }
        }
        return true;
    }

    /**
     * 删除节点
     * @param $value
     * @return Node|null
     */
    protected function _delete($value)
    {
        if ($value instanceof Node) {
            $value = $value->getValue();
        }
        if ($this->root == null || !is_numeric($value)) {
            return null;
        }

        $node = $this->setCurrentNode($this->root)->_search($value);
        if (!$node) {
            return null;
        }
        // 如果能搜索到  其实 currentNode已经指向node

        /**
         * 如果有左子树，用其最大节点覆盖当前节点
         * 如果有右子树，用其最小节点覆盖当前节点
         * 如果没子树 直接删除
         */
        if ($node->getLeftNode()) {
            $replace_node = $this->setCurrentNode($node->getLeftNode())->_max();
        } elseif ($node->getRightNode()) {
            $replace_node = $this->setCurrentNode($node->getRightNode())->_min();
        } else {
            if (!$node->getParentNode()) {
                // 没有父节点 就是root
                $this->root = null;
                return $node;
            }
            $replace_node = null;
        }

        if ($replace_node) {
            // 删除替换节点
            $this->_delete($replace_node);
            // 修改替换节点指向
            $replace_node->setParentNode($node->getParentNode())
                ->setLeftNode($node->getLeftNode())
                ->setRightNode($node->getRightNode());

            // 修改左子节点指向
            if ($node->getLeftNode()) {
                $node->getLeftNode()->setParentNode($replace_node);
            }
            // 修改右子节点指向
            if ($node->getRightNode()) {
                $node->getRightNode()->setParentNode($replace_node);
            }
        }

        // 修改父节点指向
        if ($node->getParentNode()->getLeftNode() === $node) {
            $node->getParentNode()->setLeftNode($replace_node);
        } else {
            $node->getParentNode()->setRightNode($replace_node);
        }

        // 返回删除的节点
        return $node->setParentNode()->setLeftNode()->setRightNode();
    }

    /**
     * 树高
     */
    protected function _height()
    {
        if (($current_node = $this->currentNode) == null) {
            return 0;
        }
        $left = 0;
        if ($current_node->getLeftNode()) {
            $left = $this->setCurrentNode($current_node->getLeftNode())->_height();
            $this->setCurrentNode($current_node);
        }

        $right = 0;
        if ($current_node->getRightNode()) {
            $right = $this->setCurrentNode($current_node->getRightNode())->_height();
            $this->setCurrentNode($current_node);
        }

        return max($left, $right) + 1;
    }

    /**
     * 节点数量
     * @return int
     */
    protected function _count()
    {
        if (($current_node = $this->currentNode) == null) {
            return 0;
        }

        $left = 0;
        if ($current_node->getLeftNode()) {
            $left = $this->setCurrentNode($current_node->getLeftNode())->_count();
            $this->setCurrentNode($current_node);
        }

        $right = 0;
        if ($current_node->getRightNode()) {
            $right = $this->setCurrentNode($current_node->getRightNode())->_count();
            $this->setCurrentNode($current_node);
        }

        return $left + $right + 1;
    }

    /**
     * 获取最小值
     */
    protected function _min()
    {
        if (!$this->currentNode) {
            return null;
        }
        if ($this->currentNode->getLeftNode()) {
            return $this->setCurrentNode($this->currentNode->getLeftNode())->_min();
        }
        return $this->currentNode;
    }

    /**
     * 获取最大值
     */
    protected function _max()
    {
        if (!$this->currentNode) {
            return null;
        }
        if ($this->currentNode->getRightNode()) {
            return $this->setCurrentNode($this->currentNode->getRightNode())->_max();
        }
        return $this->currentNode;
    }

    /**
     * 左旋转
     */
    public function leftRotate()
    {
        $this->currentNode == null && $this->currentNode = $this->root;
        if(($right = $this->currentNode->getRightNode()) == null){
            return false;
        }
        $parent = $this->currentNode->getParentNode();
        if ($parent == null) {
            $this->root = $right;
        } else {
            if($this->currentNode === $parent->getRightNode()){
                $parent->setRightNode($right);
            }else{
                $parent->setLeftNode($right);
            }
        }
        $this->currentNode->setParentNode($right)->setRightNode($right->getLeftNode());
        $right->setParentNode($parent)->setLeftNode($this->currentNode);
        $this->setCurrentNode();
        return true;
    }

    /**
     * 右旋转
     */
    public function rightRotate()
    {
        $this->currentNode == null && $this->currentNode = $this->root;
        if(($left = $this->currentNode->getLeftNode()) == null){
            return false;
        }
        $parent = $this->currentNode->getParentNode();
        if ($parent == null) {
            $this->root = $left;
        } else {
            if($this->currentNode === $parent->getRightNode()){
                $parent->setRightNode($left);
            }else{
                $parent->setLeftNode($left);
            }
        }
        $this->currentNode->setParentNode($left)->setLeftNode($left->getRightNode());
        $left->setParentNode($parent)->setRightNode($this->currentNode);
        $this->setCurrentNode();
        return true;
    }

    public function jsonSerialize()
    {
        return $this->middleOrder();
    }

    public function __toString()
    {
        return implode(',', $this->middleOrder());
    }

    /**
     * 先序遍历
     */
    protected function _preOrder()
    {
        $result = [];

        // 递归实现
        if (($current_node = $this->currentNode) != null) {
            array_push($result, $current_node->getValue());

            if ($current_node->getLeftNode()) {
                $result = array_merge($result, $this->setCurrentNode($current_node->getLeftNode())->_preOrder());
                $this->setCurrentNode($current_node);
            }

            if ($current_node->getRightNode()) {
                $result = array_merge($result, $this->setCurrentNode($current_node->getRightNode())->_preOrder());
                $this->setCurrentNode($current_node);
            }
        }

        // 单栈单while实现
//        $stack = new \SplStack();
//        $stack->push($this->root);
//        while(!$stack->isEmpty()){
//            /** @var Node $node */
//            $node = $stack->pop();
//            if($node->getRightNode()){
//                $stack->push($node->getRightNode());
//            }
//            if($node->getLeftNode()){
//                $stack->push($node->getLeftNode());
//            }
//            $result[] = $node->getValue();
//        }

        // 单栈双while实现
        // 这种方式体现了，先序、中序的定义：第一次、第二次访问节点时输出节点
//        $stack = new \SplStack();
//        /** @var Node $node */
//        $node = $this->root;
//        while($node || !$stack->isEmpty()){
//            while($node){
//                $result[] = $node->getValue();
//                $stack->push($node);
//                $node = $node->getLeftNode();
//            }
//            if(!$stack->isEmpty()){
//                $node = $stack->pop();
//                $node = $node->getRightNode();
//            }
//        }

        return $result;
    }

    /**
     * 中序遍历
     */
    protected function _middleOrder()
    {
        $result = [];

        if (($current_node = $this->currentNode) != null) {
            if ($current_node->getLeftNode()) {
                $result = array_merge($result, $this->setCurrentNode($current_node->getLeftNode())->_middleOrder());
                $this->setCurrentNode($current_node);
            }

            array_push($result, $current_node->getValue());

            if ($current_node->getRightNode()) {
                $result = array_merge($result, $this->setCurrentNode($current_node->getRightNode())->_middleOrder());
                $this->setCurrentNode($current_node);
            }
        }

        // 没想到单while的实现方式
        // 单栈双while实现
//        $stack = new \SplStack();
//        /** @var Node $node */
//        $node = $this->root;
//        while($node || !$stack->isEmpty()){
//            while($node){
//                $stack->push($node);
//                $node = $node->getLeftNode();
//            }
//            if(!$stack->isEmpty()){
//                $node = $stack->pop();
//                $result[] = $node->getValue();
//                $node = $node->getRightNode();
//            }
//        }


        return $result;
    }

    /**
     * 后序遍历
     * 这里用递归
     */
    protected function _afterOrder()
    {
        $result = [];

        if (($current_node = $this->currentNode) != null) {
            if ($current_node->getLeftNode()) {
                $result = array_merge($result, $this->setCurrentNode($current_node->getLeftNode())->_afterOrder());
                $this->setCurrentNode($current_node);
            }

            if ($current_node->getRightNode()) {
                $result = array_merge($result, $this->setCurrentNode($current_node->getRightNode())->_afterOrder());
                $this->setCurrentNode($current_node);
            }

            array_push($result, $current_node->getValue());
        }

        /**
         * 后序定义为：第三次访问节点时输出，表现为按 左-右-根 顺序输出，先序表现为 根-左-右
         * 当前树的镜像树的先序遍历，即可看成时当前树的特殊遍历方式：根-右-左
         * 得出 当前树的后序遍历 === 镜像树的先序遍历的逆序结果
         */

        // 单栈单while实现
//        $stack = new \SplStack();
//        $stack->push($this->root);
//        while(!$stack->isEmpty()){
//            /** @var Node $node */
//            $node = $stack->pop();
//            if($node->getLeftNode()){
//                $stack->push($node->getLeftNode());
//            }
//            if($node->getRightNode()){
//                $stack->push($node->getRightNode());
//            }
//
//            $result[] = $node->getValue();
//        }

        // 单栈双while实现
//        $stack = new \SplStack();
//        /** @var Node $node */
//        $node = $this->root;
//        while($node || !$stack->isEmpty()){
//            while($node){
//                $stack->push($node);
//                $result[] = $node->getValue();
//                $node = $node->getRightNode();
//            }
//            if(!$stack->isEmpty()){
//                $node = $stack->pop();
//                $node = $node->getLeftNode();
//            }
//        }

//        $result = array_reverse($result);

        /**
         * TODO 后序这里应该还有种做法：
         * 后序的定义是第三次访问节点时输出，输出节点的条件有：
         * 一、该节点无左右节点；
         * 二、上一次访问的是左节点同时无右节点；
         * 三、上一次访问的是右节点
         * 定义一个变量来存储上一次访问的节点，应该是可以处理，这里就只记录不写了
         */
        return $result;
    }

    /**
     * 层次遍历（自上至下）
     */
    protected function _levelOrder()
    {
        $result = [];
        if ($this->root == null) {
            return $result;
        }
        $queue = new \SplQueue();
        $queue->enqueue($this->root);

        while (!$queue->isEmpty()) {
            /** @var Node $node */
            $node = $queue->dequeue();
            if ($node->getLeftNode()) {
                $queue->enqueue($node->getLeftNode());
            }
            if ($node->getRightNode()) {
                $queue->enqueue($node->getRightNode());
            }
            $result[] = $node->getValue();
        }

        return $result;
    }

    /**
     * 层次遍历（自下至上）
     */
    protected function _levelBottomOrder()
    {
        $result = [];
        if ($this->root == null) {
            return $result;
        }
        $queue = new \SplQueue();
        $queue->enqueue($this->root);

        // 分层思想
        $i = 0;
        while (!$queue->isEmpty()) {
            $i++;
            $count = $queue->count();
            $tmp = [];
            $result[$i] = [];
            for ($j = 0; $j < $count; $j++) {
                /** @var Node $node */
                $node = $queue->dequeue();
                $tmp = array_merge($tmp, [$node->getValue()]);
                if ($node->getLeftNode()) {
                    $queue->enqueue($node->getLeftNode());
                }
                if ($node->getRightNode()) {
                    $queue->enqueue($node->getRightNode());
                }
            }
            $result[$i] = array_merge($result[$i], $tmp);
        }

        if (!empty($result)) {
            $result = array_reverse($result);
            $result = array_merge_recursive(...$result);
        }

        return $result;
    }

    /**
     * 查找一个值
     * @param $value
     * @return Node|null;
     */
    protected function _search($value)
    {
        if (!is_numeric($value)) {
            return null;
        }
        if ($this->currentNode === null) {
            return null;
        }
        if ($value instanceof Node) {
            $value = $value->getValue();
        }

        if ($value == $this->currentNode->getValue()) {
            return $this->currentNode;
        } elseif ($this->currentNode->getLeftNode() && $value < $this->currentNode->getValue()) {
            return $this->setCurrentNode($this->currentNode->getLeftNode())->_search($value);
        } elseif ($this->currentNode->getRightNode() && $value > $this->currentNode->getValue()) {
            return $this->setCurrentNode($this->currentNode->getRightNode())->_search($value);
        }
        return null;
    }

    /**
     * 设置指向的node节点
     *
     * @param Node|null $node
     * @return $this
     */
    public function setCurrentNode(Node $node = null)
    {
        $this->currentNode = $node;
        return $this;
    }

    public function __call($name, $args)
    {
        if (method_exists($this, '_' . $name) && is_callable([$this, $name])) {
            $this->setCurrentNode($this->root);
            $result = $this->{'_' . $name}(...$args);
            $this->setCurrentNode();
            return $result;
        }

        throw new \Error('Call to undefined method ' . static::class . '::' . $name);
    }


}