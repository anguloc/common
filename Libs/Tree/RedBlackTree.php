<?php

namespace DHelper\Libs\Tree;

use DHelper\Libs\Tree\Node as BaseNode;
use DHelper\Libs\Tree\RBNode as Node;

/**
 * Class RedBlackTree
 * @package DHelper\Libs\Tree
 * @method fixInsertToBalance()
 * @method fixDeleteToBalance()
 */
class RedBlackTree extends BinarySearchTree
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

    protected function createNode($value, BaseNode $node = null)
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

    public function __toString()
    {
        $res = "";
        if ($this->root === null) {
            return $res;
        }

        $height = $this->height();
        $weight = pow(2, $height - 1);

        $queue = new \SplQueue();
        $queue->enqueue($this->root);

        $draw = function ($arr) use ($weight) {
            $len = 4 * $weight - 1;
            $num = count($arr);
            $glue = array_fill(0, $len, "<span style='color: black'>    </span>");

            foreach ($arr as $k => $item) {
                $index = floor($len * ($k + 1) / ($num + 1));
                if (is_numeric($index)) {
                    $glue[$index] = "<span style='color: {$item['color']}'>" . str_pad($item['value'], 4, ' ', STR_PAD_BOTH) . "</span>";
                }
            }

            return implode('', $glue) . PHP_EOL;
        };

        $i = 0;
        while (!$queue->isEmpty()) {
            $i++;
            if ($i > $height) {
                break;
            }
            $count = $queue->count();
            $tmp = [];
            for ($j = 0; $j < $count; $j++) {
                $node = $queue->dequeue();
                if ($node instanceof Node) {
                    $tmp = array_merge($tmp, [['color' => $node->isRed() ? 'red' : 'black', 'value' => $node->getValue()]]);
                    $queue->enqueue($node->getLeftNode() ?? -1);
                    $queue->enqueue($node->getRightNode() ?? -1);
                } else {
                    $tmp = array_merge($tmp, [['color' => 'black', 'value' => '    ']]);
                }
            }
            $res .= call_user_func($draw, $tmp);
        }
        $res = nl2br($res);
        return rtrim($res);
    }

    /**
     * 插入节点 增加旋转平衡功能
     *
     * @param int $value
     * @return Node|bool
     */
    public function insert($value)
    {
        $this->setCurrentNode($this->root);
        $node = $this->_insert($value);
        if (!$node instanceof Node) {
            return $node;
        }

        // 旋转至平衡
        $this->currentNode = $node;
        $this->fixInsertToBalance();
        $this->setCurrentNode();
        return $node;
    }

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
        } else {
            $node = $this->setCurrentNode($this->root)->_search($value);
        }

        if (!$node) {
            return null;
        }
        // 如果能搜索到  其实 currentNode已经指向node

        /**
         *
         * 一、删除节点没有子节点
         *      直接删除，如果是红色就返回，如果是黑色需要做平衡  做法就是变红并平衡（重点）
         * 二、删除节点只有一个子节点
         *      只有一个子节点则必为黑色且子节点必为红色，递归一下就是（一）
         * 三、删除节点有两个子节点
         *      递归一下就是（一）或者（二）
         */
        /** @var Node $replace_node */
        if ($node->getLeftNode() && $node->getRightNode()) {
            $replace_node = $this->setCurrentNode($node->getLeftNode())->_max();
        } elseif ($node->getLeftNode()) {
            $replace_node = $node->getLeftNode();
        } elseif ($node->getRightNode()) {
            $replace_node = $node->getRightNode();
        } else {
            if ($node->isRed()) {
                $replace_node = null;
            } else {
                // 删除无子黑节点
                // 如果没有父节点 则为root 直接删除返回
                if (!$node->getParentNode()) {
                    // 没有父节点 就是root
                    $this->root = null;
                    $this->setCurrentNode();
                    return $node;
                }
                // 否则将其变红并调整平衡后删除
                $this->fixDeleteToBalance();
                $node->setRed();
                $replace_node = null;
            }
        }


        if ($replace_node) {
            // 删除替换节点
            $this->_delete($replace_node);
            // 修改替换节点指向
            $replace_node->setParentNode($node->getParentNode())
                ->setLeftNode($node->getLeftNode())
                ->setRightNode($node->getRightNode());

            if ($node->isRed()) {
                $replace_node->setRed();
            } else {
                $replace_node->setBlack();
            }

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
            if ($replace_node) {
                $replace_node->setBlack();
            }
            $this->root = $replace_node;
        }

        // 删除完成后 指向替换元素 或 删除元素父级元素
        $current = $replace_node ?? $node->getParentNode();
        $this->setCurrentNode($current);

        // 返回删除的节点
        return $node->setParentNode()->setLeftNode()->setRightNode();
    }

    /**
     * 修改插入后至平衡 这里只考虑简单实现 不准备优化
     */
    protected function _fixInsertToBalance()
    {
        // 如果是root 变黑之间返回
        if ($this->currentNode === $this->root) {
            $this->currentNode->setBlack();
            return;
        }

        /** @var Node $parent_node */
        $parent_node = $this->currentNode->getParentNode();

        // 如果父节点为黑 直接插入 不作调整
        if (!$parent_node->isRed()) {
            return;
        }

        /**
         * 如果父节点为红色
         *
         * 叔叔节点存在并为红色  将父、叔设置为黑色，祖父设为红，并将祖父设置为指针节点后开始递归
         * 叔叔节点存在并为黑色 或者 不存在
         *      父节点为左节点
         *          插入节点为左节点 将父节点设置为黑色 祖父设置为红色 祖父进行右旋
         *          插入节点为右节点 将父节点左旋 指针节点指向父节点 递归（其实就变成上面的情况）
         *      父节点为右节点  和上面的情况镜像对称
         *          插入节点为左节点
         *          插入节点为右节点
         */
        /** @var Node $pparent_node */
        $pparent_node = $parent_node->getParentNode();
        /** @var Node $uncle_node */
        $uncle_node = $pparent_node->getLeftNode() === $parent_node ? $pparent_node->getRightNode() : $pparent_node->getLeftNode();

        if ($uncle_node !== null && $uncle_node->isRed()) {
            $parent_node->setBlack();
            $uncle_node->setBlack();
            $pparent_node->setRed();
            $this->currentNode = $pparent_node;
            $this->_fixInsertToBalance();
            return;
        }

        if ($pparent_node->getLeftNode() === $parent_node) {
            if ($this->currentNode === $parent_node->getLeftNode()) {
                $parent_node->setBlack();
                $pparent_node->setRed();
                $this->setCurrentNode($pparent_node)->rightRotate();
            } else {
                $this->setCurrentNode($parent_node)->leftRotate();
                $this->setCurrentNode($parent_node)->_fixInsertToBalance();
            }
        } else {
            if ($this->currentNode === $parent_node->getLeftNode()) {
                $this->setCurrentNode($parent_node)->rightRotate();
                $this->setCurrentNode($parent_node)->_fixInsertToBalance();
            } else {
                $parent_node->setBlack();
                $pparent_node->setRed();
                $this->setCurrentNode($pparent_node)->leftRotate();
            }
        }
    }

    /**
     * 修改删除后至平衡 这里只考虑简单实现 不准备优化
     */
    protected function _fixDeleteToBalance()
    {
        // $root 为黑色无子节点 且不是根节点
        /** @var Node $node */
        $node = $this->currentNode;

        /**
         * 注释只考虑节点为父节点左子节点情况，右节点相反
         * 必有兄弟节点
         * 一、兄弟为红，则父为黑，必有子且子为黑，此时父节点黑高为2
         *      兄设为黑，兄左子设为红，对父进行左旋
         * 二、兄弟为黑，有左右子，并兄弟左右子为红，存在右子时，此时父节点黑高可能为1或者2（父黑）
         *      兄设为父色，父设为黑色，兄右子设为黑色，对父进行左旋
         * 三、兄弟为黑，有左右子，并兄弟左右子为红，只存在左子时，此时父节点黑高可能为1或者2（父黑）
         *      左子设为黑，兄设为红，对兄右旋，成为二的情形，递归
         * 四、兄弟为黑，无左右子，父为红
         *      父设为黑，兄设为红
         * 五、兄弟为黑，无左右子，父为黑，即全黑（重点）
         *      如果父节点不是root，兄弟设为红，递归父节点变红处理
         *      如果是root，兄弟变红
         *
         *  二、三，正常情况兄为黑，有左右子的话，左右子必为红，不过由下向上递归的情况除外
         */
        /** @var Node $parent_node 父节点 */
        /** @var Node $brother_node 兄弟节点 */
        /** @var Node $nl_node 兄弟左节点 */
        /** @var Node $nr_node 兄弟右节点 */
        $parent_node = $node->getParentNode();
        if ($node === $parent_node->getLeftNode()) {
            $brother_node = $parent_node->getRightNode();
            $brother_in_node = 'getLeftNode';
            $brother_out_node = 'getRightNode';
            $rotate = 'leftRotate';
            $lrotate = 'rightRotate';
        } else {
            $brother_node = $parent_node->getLeftNode();
            $brother_in_node = 'getRightNode';
            $brother_out_node = 'getLeftNode';
            $rotate = 'rightRotate';
            $lrotate = 'leftRotate';
        }

        if ($brother_node->isRed()) {
            $brother_node->setBlack();
            $brother_node->$brother_in_node()->setRed();
            $this->setCurrentNode($parent_node)->$rotate();
        } elseif ($brother_node->$brother_out_node() && $brother_node->$brother_out_node()->isRed()) {
            if ($parent_node->isRed()) {
                $brother_node->setRed();
                $parent_node->setBlack();
            }
            $brother_node->$brother_out_node()->setBlack();
            $this->setCurrentNode($parent_node)->$rotate();
        } elseif ($brother_node->$brother_in_node() && $brother_node->$brother_in_node()->isRed()) {
            $brother_node->setRed();
            $brother_node->$brother_in_node()->setBlack();
            $this->setCurrentNode($brother_node)->$lrotate();
            $this->setCurrentNode($node)->_fixDeleteToBalance();
        } elseif ($parent_node->isRed()) {
            $parent_node->setBlack();
            $brother_node->setRed();
        } else {
            if ($parent_node === $this->root) {
                $brother_node->setRed();
            } else {
                $this->setCurrentNode($parent_node);
                $this->_fixDeleteToBalance();
                $brother_node->setRed();
            }
        }

        $this->setCurrentNode($node);

        return $node;
    }

    protected function _blackHeight()
    {
        if (($current_node = $this->currentNode) == null) {
            return 0;
        }
        $left = 0;
        if ($current_node->getLeftNode()) {
            $left = $this->setCurrentNode($current_node->getLeftNode())->_blackHeight();
            $this->setCurrentNode($current_node);
        }

        $right = 0;
        if ($current_node->getRightNode()) {
            $right = $this->setCurrentNode($current_node->getRightNode())->_blackHeight();
            $this->setCurrentNode($current_node);
        }

        $l = 0;
        if (!$current_node->isRed()) {
            $l = 1;
        }

        $r = max($left, $right) + $l;
//        var_dump('val-' . $this->currentNode->getValue(),$r);
        return $r;
    }

    /**
     * 测试用 其他的时候不要用
     *
     * @param bool $is_root
     * @return bool
     */
    public function validate($is_root = false)
    {
        if ($is_root) {
            $this->setCurrentNode($this->root);
        }
        $root = $this->currentNode;
        $l = $r = 0;
        $lt = $rt = true;

        if ($root && $root->getLeftNode()) {
            $l = $this->setCurrentNode($root->getLeftNode())->_blackHeight();
            $lt = $this->validate();
        }
        if (!$lt) {
            $this->setCurrentNode($root);
            return $lt;
        }

        if ($root && $root->getRightNode()) {
            $r = $this->setCurrentNode($root->getRightNode())->_blackHeight();
            $rt = $this->validate();
        }

        if (!$rt) {
            $this->setCurrentNode($root);
            return $rt;
        }

        $this->setCurrentNode($root);

        if ($l != $r) {
            return false;
        }

        return true;
    }


}