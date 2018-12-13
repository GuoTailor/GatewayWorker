<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use Common\TTCode;
use model\TTPublic;
use Socket\SocketUser;
use Tools\MessageTools;
use Tools\PhpLog;
use Socket\SocketFile;
use Socket\SocketEvent;
use Socket\SocketHead;
use Socket\SocketCache;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    const CMD_ID_UNKNOWN = 0;
    const CMD_ID_NOOP = 1; // System Use
    const CMD_ID_SEND_MESSAGE = 2;
    const CMD_ID_UPLOAD_FILE = 3;

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id) {
        PhpLog::println("client_id", $client_id);
        PhpLog::Log("$client_id onConnect");
    }

    private static function handleMessage($client_id, $mr, $message) {

        try {
            $messageSize = strlen($message);
            $headSize = SocketHead::HEAD_SIZE;
            $bodySize = $mr[SocketHead::H6_BODY];
            PhpLog::println("mr2", $mr);
            PhpLog::println("message2", $message);

            switch ($mr[SocketHead::H4_CMD]) {
                case Events::CMD_ID_NOOP:
                    SocketUser::loop($client_id, $mr);
                    break;

                case Events::CMD_ID_SEND_MESSAGE:
                    // 如果数据不完整，就启动缓存
                    if($messageSize < $headSize + $bodySize) {
                        SocketCache::write($client_id, $message);
                    } else {
                        $body = substr($message, $headSize, $bodySize);
                        SocketEvent::handle($client_id, $mr, $body);
                    }
                    break;

                case Events::CMD_ID_UPLOAD_FILE:
                    // 如果数据不完整，就启动缓存
                    if($messageSize < $headSize + $bodySize) {
                        SocketCache::write($client_id, $message);
                    } else {
                        SocketFile::handleUploadFile($client_id, $mr, $message);
                    }
                    break;

                default:
                    break;
            }
        } catch (Exception $ex) {
            PhpLog::Error($ex->getMessage());
            $result = TTPublic::getResponse(TTCode::TT_FAILED);
            MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
        }

    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message) {

        PhpLog::Log("Request message size: ".strlen($message));

        // 解析head
        $head = substr($message, 0, SocketHead::HEAD_SIZE);
        $mr = SocketHead::unpack($head);
        PhpLog::Log("Request head: ".json_encode($mr));
        PhpLog::println("message>>", $message);
        PhpLog::println("head", $head);
        PhpLog::println("mr", $mr);
        // 检测头是否有效
        if($mr != null) {
            self::handleMessage($client_id, $mr, $message);
        } else {
            // 拼接数据
            $fileData = SocketCache::write($client_id, $message, true);
            PhpLog::println("fileData", $fileData);
            // 检测是否拼接完成
            if($fileData != null) {
                SocketCache::delete($client_id); // 删除缓存文件
                $fileHead = substr($fileData, 0, SocketHead::HEAD_SIZE);
                $fileMr = SocketHead::unpack($fileHead);
                self::handleMessage($client_id, $fileMr, $fileData);
            }
        }

        // 显示用户在线信息
        SocketUser::debugInfo($client_id);

        // 更新活跃时间
        SocketUser::updateActiveTime($client_id);
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id) {
        PhpLog::Log("$client_id onClose");

        // 取消socket连接
        SocketUser::unbindUserAndClient($client_id);

        // 显示用户在线信息
        SocketUser::debugInfo($client_id);

        // 保存断线时间
        SocketUser::updateActiveTime($client_id);

    }


}

