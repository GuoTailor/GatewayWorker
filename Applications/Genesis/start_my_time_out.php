<?php
use \Workerman\Worker;
use \Workerman\Lib\Timer;

use Tools\PhpLog;
use Socket\SocketUser;
use Socket\SocketGroup;

$task = new Worker();
// 开启多少个进程运行定时任务，注意多进程并发问题
$task->count = 1;
$task->name = "my_time_out";

$task->onWorkerStart = function($task)
{
    // 每1秒执行一次 支持小数，可以精确到0.001，即精确到毫秒级别
    $time_interval = 1;
    Timer::add($time_interval, function()
    {
        SocketUser::timeoutOffline();
        SocketGroup::timeoutIntercom();
    });
};

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START')) {
    Worker::runAll();
}