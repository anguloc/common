<?php

namespace DHelper\Process;

use Swoole\Process as SwooleProcess;
use Swoole\Process\Pool;
use Swoole\Timer;

/**
 * 基于Swoole的Pool封装
 * 工作进程一旦开始，无法退出，否则master会自动拉起
 *
 * Class Process
 * @package DHelper\Process
 */
class DMProcess
{
    protected $masterPid; // master进程id

    /**
     * 进程池
     *
     * @var Pool
     */
    protected $pool;

    /**
     * master进程名称
     *
     * @var string
     */
    protected $masterProcessName = 'dhelper process';

    /**
     * 注册的进程配置
     * @see register() 由此方法加入
     *
     * @var array
     */
    protected $processes = [];

    /**
     * work_id对于进程的映射
     *
     * @var array
     */
    protected $workIdMap = [];

    /**
     * 进程编号，在子进程中赋值
     *
     * @var int|null
     */
    protected $workerId;

    protected static $debug = false;

    public function register(string $name, $callback, int $num = 1, array $options = [])
    {
        if (!is_callable($callback)) {
            return $this;
        }
        $num = $num > 0 ? $num : 1;

        $count = count($this->workIdMap);

        $this->processes[$name] = array_merge($options, [
            'worker_id' => range($count, $count + $num - 1),
            'callback' => $callback,
            'num' => $num,
        ]);

        $this->workIdMap = $this->workIdMap + array_fill_keys($this->processes[$name]['worker_id'], $name);

        return $this;
    }

    public function start()
    {
        if (empty($this->processes)) {
            throw new \LogicException("Missing worker process....");
        }

        try {
            SwooleProcess::daemon();
            $this->masterPid = getmypid();

            self::setProcessName($this->masterProcessName);
            $num = array_sum(array_column($this->processes, 'num'));

            /**
             * 这里采用unixSock通讯
             * $msgqueue_key这个参数必须设置为int，这里不用所以就是0
             * 用Swoole就是用协程
             */
            $this->pool = new Pool($num, SWOOLE_IPC_UNIXSOCK, 0, true);
            $this->pool->on('WorkerStart', function (Pool $pool, $worker_id) {
                $name = $this->workIdMap[$worker_id];
                $setting = $this->processes[$name];

                $process_name = $setting['process_name'] ?? "{$this->masterProcessName}:process:worker {$name} #{$worker_id}";
                self::setProcessName($process_name);

                /** @var SwooleProcess $process */
                $process = $pool->getProcess($worker_id);
                $this->workerId = $worker_id;

                self::log("worker [{$worker_id}#] [pid:{$process->pid}] start" . PHP_EOL);

                call_user_func($setting['callback'], $this);
            });
            $this->pool->on('WorkerStop', function (Pool $pool, $worker_id) {
                self::log("worker [{$worker_id}#] stop");
            });

            $this->pool->start();
            return true;
        } catch (\Throwable $e) {
            self::log("进程启动失败：{$e->getMessage()}");
            return false;
        }
    }

    public function send(int $worker_id, string $data, float $timeout = -1)
    {
        return $this->getProcess($worker_id)->exportSocket()->send($data, $timeout);
    }

    public function sendTo(string $name, string $data, float $timeout = -1)
    {
        if (!$setting = $this->processes[$name] ?? []) {
            return false;
        }
        srand((double)microtime() * 1000000);
        $index = array_rand($setting['worker_id']);
        $worker_id = $setting['worker_id'][$index];
        return $this->send($worker_id, $data, $timeout);
    }

    public function sendAll(string $data, bool $is_myself = false, float $timeout = -1)
    {
        foreach ($this->workIdMap as $worker_id => $name) {
            if (!$is_myself && $worker_id == $this->getWorkerId()) {
                continue;
            }
            $this->send($worker_id, $data, $timeout);
        }
    }

    public function recv(int $length = 65535, float $timeout = -1)
    {
        return $this->getProcess()->exportSocket()->recv($length, $timeout);
    }

    public function stop($sig = SIGTERM)
    {
        if ($this->masterPid > 0) {
            SwooleProcess::kill($this->masterPid, $sig);
        }
    }

    /**
     * 设置master进程名称
     *
     * @param string $name
     * @return $this
     */
    public function setMasterProcessName(string $name)
    {
        $this->masterProcessName = $name;
        return $this;
    }

    public static function setProcessName($name)
    {
        if (PHP_OS != 'Darwin' && function_exists('swoole_set_process_name')) {
            swoole_set_process_name($name);
        }
    }

    public function getWorkerId()
    {
        return $this->workerId;
    }

    protected static function log($msg, array $context = [], $log = false)
    {
        if (!self::$debug) {
            return;
        }
        if (is_array($msg) || is_object($msg)) {
            $msg = json_encode($msg);
        }

        echo "$msg", PHP_EOL;
    }

    public function getProcess(int $worker_id = null): SwooleProcess
    {
        if (is_null($worker_id)) {
            $worker_id = $this->workerId;
        }
        return $this->pool->getProcess($worker_id);
    }
}