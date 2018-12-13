<?php

// Using Autoload all classes are loaded on-demand
require_once __DIR__ . '/ApnsPHP/Autoload.php';

use Common\TTRedis;
use Tools\PhpLog;
use \Workerman\Worker;

use ThirdParty\IOSPush;

const FILE_PATH = "/home/admin123/server/GatewayWorker/";
const CERT = FILE_PATH."cert/server_certificates_bundle_sandbox.pem";
const CERT_ROOT = FILE_PATH."cert/entrust_root_certification_authority.pem";

// Adjust to your timezone
date_default_timezone_set('Asia/Shanghai');

// Report all PHP errors
error_reporting(-1);

$task = new Worker();
// 开启多少个进程运行定时任务，注意多进程并发问题
$task->count = 1;
$task->name = "my_ios_push";

$task->onWorkerStart = function($task)
{
//    PhpLog::Task("[start push begin]");

    // 创建发送服务
    $server = new ApnsPHP_Push_Server(
        ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
        CERT
    );

    // Set the Root Certificate Autority to verify the Apple remote peer
    $server->setRootCertificationAuthority(CERT_ROOT);

    // Set the number of concurrent processes
    $server->setProcesses(1);

    // Starts the server forking the new processes
    $server->start();

    while ($server->run()) {
        // Check the error queue
        $aErrorQueue = $server->getErrors();
        if (!empty($aErrorQueue)) {
            // Do somethings with this error messages...
            var_dump($aErrorQueue);
        }

        while (true) {

            // 从队列取数据
            $messageObj = IOSPush::pop();
            if(empty($messageObj)) {
                break;
            }

            // 分解数据
            $user_id = $messageObj[IOSPush::USER_ID];
            $title = $messageObj[IOSPush::TITLE];
            $text = $messageObj[IOSPush::TEXT];
            $data = $messageObj[IOSPush::DATA];

            // 检测数据是否合法
            if(empty($user_id) || empty($title) || empty($text) || empty($data)) {
                PhpLog::Task("send error [".json_encode($messageObj)."]");
                continue;
            }

            // 获取设备Token
            $sDeviceToken = TTRedis::getDeviceToken($user_id);
            if(empty($sDeviceToken)) {
                PhpLog::Task("send no device");
                continue;
            }

            // 发送消息
            $apnsMsg = new ApnsPHP_Message($sDeviceToken);
            $apnsMsg->setBadge(1);
            $apnsMsg->setTitle($title);
            $apnsMsg->setText($text);
            $apnsMsg->setCustomProperty("data", $data);
            // Add the message to the message queue
            $server->add($apnsMsg);

            PhpLog::Task("send ok [".$sDeviceToken."] [".json_encode($messageObj)."]");
        }

        // Sleep a little...
        usleep(200000); // 0.2S
    }

//    PhpLog::Task("[start push end]");
};

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START')) {
    Worker::runAll();
}