<?php
use model\TTRidingRecord;
use \Workerman\Worker;
use \Workerman\Lib\Timer;

use Tools\PhpLog;
use Common\TTRedis;

use Socket\SocketInd;

$task = new Worker();
// 开启多少个进程运行定时任务，注意多进程并发问题
$task->count = 1;
$task->name = "my_riding_record";

$task->onWorkerStart = function($task)
{
    // 每1秒执行一次 支持小数，可以精确到0.001，即精确到毫秒级别
    $time_interval = 0.1;
    Timer::add($time_interval, function()
    {
//        PhpLog::Task("[start location]");

        while(true) {

            // 取出要转换的定位
            $group_user_id = TTRedis::popRidingRecord();
            if($group_user_id == null) {
                break;
            }

            PhpLog::Task("location pop ".$group_user_id);

            // 获取群组ID，用户ID
            list($group_id, $user_id) = explode(",", $group_user_id);
            if(empty($group_id) || empty($user_id)) {
                continue;
            }

            if(TTRidingRecord::endLocation($user_id, $group_id)) {
                // 通知用户更新骑行记录
                SocketInd::groupNewRecordInd($user_id);
            }
        }

    });
};

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START')) {
    Worker::runAll();
}