<?php

namespace DHelper\Libs\Tree;


/**
 * Class BalanceBinaryTree
 * @package DHelper\Libs\Tree
 * @method rotateToBalance()
 */
class BalanceBinaryTree extends BinarySearchTree
{

    /**
     * 插入节点 增加旋转平衡功能
     *
     * @param int $value
     * @return bool
     */
    public function insert($value)
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

//    /**
//     * 删除节点 增加旋转平衡功能
//     * 这里记录下   这个是以前错误的做法 之前是没有_delete只有delete  替换删除的时候少了旋转
//     *
//     * @param $value
//     * @return Node|null
//     */
//    public function delete($value)
//    {
//        $result = parent::_delete($value);
//        if (!$result) {
//            return $result;
//        }
//
//        // 旋转至平衡
//        $this->rotateToBalance();
//        $this->setCurrentNode();
//        return $result;
//    }

    /**
     * 删除节点
     * @param $value
     * @return Node|null
     */
    protected function _delete($value)
    {
        if ($this->root == null) {
            return null;
        }

        if ($value instanceof Node) {
            $node = $value;
            $this->setCurrentNode($node);
        }else{
            $node = $this->setCurrentNode($this->root)->_search($value);
        }

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
                $this->setCurrentNode();
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
        if ($node->getParentNode()) {
            if ($node->getParentNode()->getLeftNode() === $node) {
                $node->getParentNode()->setLeftNode($replace_node);
            } else {
                $node->getParentNode()->setRightNode($replace_node);
            }
        } else {
            $this->root = $replace_node;
        }

        // 删除完成后 指向替换元素 或 删除元素父级元素
        $current = $replace_node ?? $node->getParentNode();
        $this->setCurrentNode($current);

        $this->rotateToBalance();

        // 返回删除的节点
        return $node->setParentNode()->setLeftNode()->setRightNode();
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

//        $rt = $ll - $lr;
//        if ($rt > 1) {
//            // ll
//            $this->setCurrentNode($this->root)->rightRotate();
//            return ;
//        } elseif ($rt < -1) {
//            // lr
//            $this->setCurrentNode($this->root->getLeftNode())->leftRotate();
//            $this->setCurrentNode($this->root)->rightRotate();
//            return;
//        }

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