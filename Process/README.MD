基于Swoole的多进程管理，主要是对进程通讯进行简单封装

```
use DHelper\Process\DMProcess;

$dm = new DMProcess();

$dm->register('worker_1', function(DMProcess $p = null){
    Timer::tick(5000, function()use($p){
        $p->sendTo('worker_2', 'Hello worker_2');
    });
    $worker_id = $p->getWorkerId();
    go(function()use($p,$worker_id){
        while(1){
            $l = $p->recv();
            echo "worker_1[{$worker_id}]接收数据：",$l,PHP_EOL;
        }
    });
},2);
$dm->register('worker_2', function(DMProcess $p = null)
{
    Timer::tick(3000, function()use($p){
        $p->sendTo('worker_1', 'Hello worker_1');
    });

    $worker_id = $p->getWorkerId();
    go(function()use($p,$worker_id){
        while(1){
            $l = $p->recv();
            echo "worker_2[{$worker_id}]接收数据：",$l,PHP_EOL;
        }
    });

},3);

$dm->start();

```