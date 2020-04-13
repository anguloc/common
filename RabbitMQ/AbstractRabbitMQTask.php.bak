<?php

namespace DHelper\RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Exception;

abstract class AbstractRabbitMQTask
{
    protected static $queue_list = [];

    protected static $dead_exchanger_type = 'direct';
    protected static $dead_exchanger_name = 'dead_exchanger';

    protected static $exchanger_type        = 'direct';
    protected static $exchanger_passive     = false;
    protected static $exchanger_durable     = true;
    protected static $exchanger_auto_delete = false;

    protected static $queue_passive     = false;
    protected static $queue_durable     = true;
    protected static $queue_auto_delete = false;
    protected static $queue_exclusive   = false;

    protected static $message_mandatory = true;

    /**
     * @param string $id
     */
    public static function getQueueConfig($queue_index) {
        $servers = static::$queue_list[$queue_index]['servers'] ?: [RabbitmqConfig::DEFAULT_SERVER];
        $queue_name = static::$queue_list[$queue_index]['queue'];
        $timeout_queue_name = isset(static::$queue_list[$queue_index]['timeout_queue'])? static::$queue_list[$queue_index]['timeout_queue']: "";
        $queue_timeout = isset(static::$queue_list[$queue_index]['queue_timeout'])? static::$queue_list[$queue_index]['queue_timeout']: -1;
        $consumers = isset(static::$queue_list[$queue_index]['consumers'])? static::$queue_list[$queue_index]['consumers']: 1;
        $exchange_name = static::$queue_list[$queue_index]['exchange'];
        $callback = static::$queue_list[$queue_index]['callback'];
        $trace = isset(static::$queue_list[$queue_index]['trace'])? static::$queue_list[$queue_index]['trace']: true;
        return [$servers, $queue_name, $timeout_queue_name, $exchange_name, $callback, $consumers, $trace, $queue_timeout];
    }

    /**
     * @param string|int $id
     */
    public static function selectServer($servers, $id = '') {
        if (empty($servers)) {
            return RabbitmqConfig::DEFAULT_SERVER;
        }

        if (empty($id)) {
            $id = time();
        }
        $index = (crc32($id) % count($servers));
        return $servers[$index];
    }

    public static function start_listen_queue($queue_index) {
        // 修改错误级别
        $parent_error_reporting = error_reporting();

        $debug_info = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $file_name = $debug_info[0]['file'];

        $normal_queue_name = "php:" . pathinfo($file_name, PATHINFO_FILENAME);

        // check env
        $workerman_log_file = RabbitmqTask::WORKERMAN_LOG_FILE;
        $log_file = RabbitmqTask::RABBITMQ_CMD_LOG_FILE;
        $error_log_file = RabbitmqTask::RABBITMQ_CMD_ERROR_LOG_FILE;
        $pid_dir = ROOT_PATH . "/zhichi/protected/commands/bin/rabbitmq/pid/";
        $pid_file = $pid_dir . $normal_queue_name . ".pid";
        if (!file_exists($pid_dir)) {
            exec("mkdir -p {$pid_dir} && chmod 777 -R {$pid_dir}");
        }

        if ((is_writable($log_file) && is_writable($error_log_file) && is_writable($pid_dir) && is_writable($pid_file))) {
            echo "文件 {$log_file}, {$error_log_file}, {$pid_dir}, {$pid_file} 不可写" . PHP_EOL;
            exit;
        }

        // check queue config
        list($servers, $queue_name, $timeout_queue_name, $exchange_name, $callback, $consumers, $trace, $queue_timeout) = static::getQueueConfig($queue_index);
        if (!empty($timeout_queue_name) && $queue_timeout < 0) {
            echo "error QueueConfig, queue_name={$queue_name}" . PHP_EOL;
            exit;
        }

        global $argc;
        global $argv;
        if ($argc == 1) {
            $argc += 1;
            $argv[] = "start";
        }

        Worker::$stdoutFile = $log_file;
        Worker::$logFile = $workerman_log_file;
        Worker::$pidFile = $pid_file;
        Worker::$daemonize = true;
        $worker = new Worker();
        $worker->count = $consumers;
        $worker->name = $normal_queue_name;
        $worker->onWorkerStart = function (Worker $worker) use ($queue_index, $parent_error_reporting) {
            error_reporting($parent_error_reporting);
            self::do_listen($queue_index);
        };
        Worker::runAll();
    }

    /**
     * 队列卡住删除失效需要重启rabbitmq再去删除重启！！！
     *
     * @param $queue_index
     */
    public static function do_listen($queue_index)
    {
        $_SERVER['SERVER_ADDR'] = getHostByName(getHostName());
        list($servers, $queue_name, $timeout_queue_name, $exchange_name, $callback, $consumers, $trace, $queue_timeout) = static::getQueueConfig($queue_index);

        // 随机选一个 server
        $server = self::selectServer($servers);
        $host = RabbitmqConfig::$arrServers[$server]['host'];
        $port = RabbitmqConfig::$arrServers[$server]['port'];
        $user = RabbitmqConfig::$arrServers[$server]['user'];
        $pass = RabbitmqConfig::$arrServers[$server]['password'];

        // 直连本机
        //$host = '0.0.0.0';
        //$port = 5672;
        //$user = 'guest';
        //$pass = 'uB8!yDEmpeh9';

        $connection = new AMQPStreamConnection($host, $port, $user, $pass);
        $channel = $connection->channel();

        $channel->exchange_declare("dead_exchanger", self::$dead_exchanger_type, self::$exchanger_passive, self::$exchanger_durable, self::$exchanger_auto_delete);

        $channel->exchange_declare($exchange_name, self::$exchanger_type, self::$exchanger_passive, self::$exchanger_durable, self::$exchanger_auto_delete);
        if (empty($timeout_queue_name)) {
            $channel->queue_declare($queue_name, self::$queue_passive, self::$queue_durable, false, self::$queue_auto_delete);
            $channel->queue_bind($queue_name, $exchange_name);
        } else if ($queue_timeout != -1) {  // 如果有 dead queue，且 timeout 的定义不为空
            // 定义超时删除并自动进入死信(超时)队列的消息属性
            $route_key = $queue_name;
            $queue_args = new AMQPTable([
                'x-message-ttl' => $queue_timeout,
                'x-dead-letter-exchange' => self::$dead_exchanger_name,
                'x-dead-letter-routing-key' => $route_key
            ]);

            $channel->queue_declare($queue_name, self::$queue_passive, self::$queue_durable, false, self::$queue_auto_delete, false, $queue_args);
            $channel->queue_declare($timeout_queue_name, self::$queue_passive, self::$queue_durable, false, self::$queue_auto_delete);  // 声明一个延时队列

            $channel->queue_bind($queue_name, $exchange_name);
            $channel->queue_bind($timeout_queue_name, self::$dead_exchanger_name, $route_key);  // 绑定死信（超时）队列的路径
        }

        $consume = function ($msg) use ($callback, $channel, $trace, $queue_name) {
            try {
                // 设置上下文 LogId
                $contextLogId = json_decode($msg->body, true)['context_log_id'];
                if ($contextLogId) {
                    \Loggers::getInstance("rabbitmq_task")->setLogId($contextLogId);
                }
                if ($trace) {
                    \Loggers::getInstance("rabbitmq_task")->notice("run rabbitmq handler: [queue_name {$queue_name}] param=" . $msg->body);
                }
                $res = call_user_func($callback, $msg->body);
                if ((is_bool($res) && $res == false) || (is_array($res) && $res['status'] != 0)) {
                    \Loggers::getInstance('rabbitmq_task')->warning("rabbitmq task run error: {$msg->body}, res: " . json_encode($res));
                }
            } catch (RabbitmqRequeueException $queue_exception) {  // 重新入队（该消息的 handler 会重新运行）
                \Loggers::getInstance('rabbitmq_task')->warning("rabbitmq task requeue: [queue_name {$queue_name}] {$msg->body}");
                $channel->basic_nack($msg->delivery_info['delivery_tag'], false, true); // 发送信号提醒mq该消息不能被删除，且重新入队列
                return;
            } catch (Exception $e) {
                $exceptionInfoStr = "{$e->getFile()} {$e->getLine()} {$e->getMessage()}";
                \Loggers::getInstance('rabbitmq_task')->warning("rabbitmq task catch error: [queue_name {$queue_name}] {$msg->body}, exception: {$exceptionInfoStr}");
            }
            $channel->basic_ack($msg->delivery_info['delivery_tag']);   // 发送信号提醒mq可删除该信息
        };

        $channel->basic_qos(null, 1, null); // 设置一次只从queue取一条信息，在该信息处理完（消费者没有发送ack给mq），queue将不会推送信息给该消费者

        // no_ack:false 表示该队列的信息必须接收到消费者信号才能被删除
        // 消费者从queue拿到信息之后，该信息不会从内存中删除，需要消费者处理完之后发送信号通知mq去删除消息（如果没此通知，queue会不断积累旧的信息不会删除）
        // 超时队列：推送message到消息队列，但不主动去该队列获取message,等到ttl超时，自动进入绑定的死信队列，在死信队列处理业务
        if (empty($timeout_queue_name)) {
            $channel->basic_consume($queue_name, '', false, false, false, false, $consume);
        } else {
            $channel->basic_consume($timeout_queue_name, '', false, false, false, false, $consume);
        }

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
        exit;
    }

    /**
     * 普通模式  对一个exchange中推一条消息
     *
     * @param $exchange_name
     * @param $param
     * @return bool
     */
    public static function addTask($exchange_name, $param) {
        if (is_array($param)) {
            $param = json_encode($param);
        }
        $host = HOST_1;
        $port = PORT_3;
        $user = USER_1;
        $pass = PWD_1;

        try {
            // 同时持久化`交换机`和`消息`，可以大概率保证mq重启或服务器宕机之后，消息不会丢失(如果mq重启或者服务器宕机前没能即时将新的消息持久化，也会造成丢失消息的情况)
            $connection = new AMQPStreamConnection($host, $port, $user, $pass);
            $channel = $connection->channel();
            $channel->exchange_declare($exchange_name, self::$exchanger_type, self::$exchanger_passive, self::$exchanger_durable, self::$exchanger_auto_delete);

            $msg_attr = [];
            $msg_attr['delivery_mode'] = AMQPMessage::DELIVERY_MODE_PERSISTENT; // 声明消息持久化
            $msg = new AMQPMessage($param, $msg_attr);
            $channel->basic_publish($msg, $exchange_name);

            $channel->close();
            $connection->close();
        } catch (\Exception $e){
            // TODO Log
            return catch_exception($e);

            return false;
        }

        return true;
    }

    /**
     *  AMQP-0-9-1 协议支持 事务和publisher confirms机制，都是能够确认消息能够到达队列，但二者只能二选一；事务可保证消息一定被确认，但事务机制效率为正常的1/250，且如果是镜像队列，需要全部入队才有回调返回
     *  以下采用 publisher confirms 机制确认生产者消息是否入队
     *  确认顺序： broker -> 交换机 -> 队列
     *
     * @param $queue_index
     * @param $param
     * @param string $obj_id
     * @return bool 返回true表示入队成功
     */
    static public function publish($queue_index, $param, $obj_id = '')
    {
        if (!is_array($param)) {
            $param = json_decode($param, 1);
        }
        $param['_add_time'] = time();
        // 传入上下文 LogId
        $param['context_log_id'] = \Loggers::getInstance("rabbitmq_task")->getLogId();
        if (is_array($param)) {
            $param = json_encode($param);
        }

        list($servers, $queue_name, $timeout_queue_name, $exchange_name, $callback, $consumers, $trace, $queue_timeout) = static::getQueueConfig($queue_index);
        $server = static::selectServer($servers, $obj_id);
        $host = RabbitmqConfig::$arrServers[$server]['host'];
        $port = RabbitmqConfig::$arrServers[$server]['port'];
        $user = RabbitmqConfig::$arrServers[$server]['user'];
        $pass = RabbitmqConfig::$arrServers[$server]['password'];

        try {
            // 同时持久化`交换机`和`消息`，可以大概率保证mq重启或服务器宕机之后，消息不会丢失(如果mq重启或者服务器宕机前没能即时将新的消息持久化，也会造成丢失消息的情况)
            $connection = new AMQPStreamConnection($host, $port, $user, $pass);
            $channel = $connection->channel();
            $channel->exchange_declare($exchange_name, self::$exchanger_type, self::$exchanger_passive, self::$exchanger_durable, self::$exchanger_auto_delete);

            // 开启 publisher confirm 模式
            $channel->confirm_select();

            // 交换机路由不到正确的队列，会触发该回调
            $failure = function ($reply_code, $reply_text, $exchange, $routing_key) use ($queue_name, $param, $obj_id, &$status) {
                $status = false;
                $reply  = compact("reply_code", "reply_text", "exchange", "routing_key");
                $log    = "publish exception - {$queue_name} not found, exchange={$exchange},param={$param},obj_id={$obj_id},reply:". json_encode($reply);
                \Loggers::getInstance("rabbitmq_task")->warning($log);

                return;
            };
            $channel->set_return_listener($failure);                                                    // basic.return回调

            $attributes['delivery_mode'] = AMQPMessage::DELIVERY_MODE_PERSISTENT;                       // 声明消息持久化
            $msg = new AMQPMessage($param, $attributes);
            $try = 10;                                                                                  // 重试N次
            while ($try-- >= 0) {
                $status = true;                                                                         // 推送消息状态标志
                $channel->basic_publish($msg, $exchange_name, '', self::$message_mandatory);            // mandatory属性：如果交换机无法根据路由找到队列，为true会将消息以basic.return信号返回生产者，为false直接丢弃信息
                $channel->wait_for_pending_acks_returns();                                              // 主要监听 basic.return 事件信号
                if ($status) {
                    break;
                }
            }
        } catch (AMQPProtocolChannelException $exception) {                                             // broker 无法找到交换机，直接抛出异常
            $status = false;
            \Loggers::getInstance("rabbitmq_task")->warning("publish exception - {$exchange_name} not found, queue_index={$queue_index},param={$param},obj_id={$obj_id}");
        } catch (Exception $e) {
            $status = false;
            \Loggers::getInstance("rabbitmq_task")->warning("publish exception, queue_index={$queue_index},param={$param},obj_id={$obj_id}");
        } finally {
            $connection->close();
        }

        if ($status && $trace) {
            \Loggers::getInstance("rabbitmq_task")->notice("add rabbitmq task: queue_name={$queue_name} queue_index={$queue_index}, param={$param}, host={$host}");
        }

        return $status;
    }

    public static function addDeferTask($task_param, $queue_index, $expire) {
        $param = [
            'task_param' => $task_param,
            'queue_index' => $queue_index
        ];

        if ($expire / 86400 >= 1) {                                              // 倒计时超过一天
            $param['expire'] = $expire - 86400;
            $defer_queue_index = self::DO_IN_A_FEW_DAYS;
        } elseif ($expire / 3600 >= 1) {                                         // 倒计时超过一小时
            $param['expire'] = $expire - 3600;
            $defer_queue_index = self::DO_IN_A_FEW_HOURS;
        } elseif ($expire / 600 >= 1) {                                          // 倒计时超过10分钟
            $param['expire'] = $expire - 600;
            $defer_queue_index = self::DO_IN_DOZENS_MINUTES;
        } else {
            $param['expire'] = $expire - 60;
            $defer_queue_index = self::DO_IN_A_FEW_MINUTES;
        }

        return self::addTask($defer_queue_index, $param);
    }

    /**
     * 入队失败会返回bool值
     *
     * @param $task_param
     * @param $queue_index
     * @param $expire
     * @return bool
     */
    public static function applyDeferTask($task_param, $queue_index, $expire)
    {
        $param = [
            'task_param'    => $task_param,
            'queue_index'   => $queue_index,
            'new_method'    => true
        ];

        if ($expire / 86400 >= 1) {                                              // 倒计时超过一天
            $param['expire'] = $expire - 86400;
            $defer_queue_index = self::DO_IN_A_FEW_DAYS;
        } elseif ($expire / 3600 >= 1) {                                         // 倒计时超过一小时
            $param['expire'] = $expire - 3600;
            $defer_queue_index = self::DO_IN_A_FEW_HOURS;
        } elseif ($expire / 600 >= 1) {                                          // 倒计时超过10分钟
            $param['expire'] = $expire - 600;
            $defer_queue_index = self::DO_IN_DOZENS_MINUTES;
        } else {                                                                 // 倒计时超过1分钟
            $param['expire'] = $expire - 60;
            $defer_queue_index = self::DO_IN_A_FEW_MINUTES;
        }

        return self::publish($defer_queue_index, $param);
    }
}