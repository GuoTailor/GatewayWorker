<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/13
 * Time: 下午2:47
 */

namespace Tools;

use Common\TTDB;
use Common\TTRedis;
use GatewayWorker\Lib\Gateway;
use model\TTPublic;
use Socket\SocketHead;

class MessageTools
{
    private static function getSendMessage($body, $mr = null) {
        // 如果没有mr,创建默认的mr
        if($mr == null) {
            $mr = SocketHead::getDefault();
        }

        // 设置body大小
        $mr[SocketHead::H6_BODY] = strlen($body);
        PhpLog::println("body", $body);
        // 连接下行数据
        $sendMsg = SocketHead::pack($mr).$body;
        PhpLog::println("sendMsg", $sendMsg);
        return $sendMsg;
    }

    private static function sendToClient($client_id, $message) {
        if(!empty($client_id) && Gateway::isOnline($client_id)) {
            Gateway::sendToClient($client_id, $message);
            PhpLog::Log("sendMessageToUserId[".$client_id."]:".substr($message, 20));
        }
    }

    // 给指定人员发消息
    // $userIds可以为数组
    public static function sendMessageToUserId($userIds, $body, $mr = null) {

        $sendMsg = self::getSendMessage($body, $mr);

        if(is_array($userIds)) {
            foreach ($userIds as $uidItem) {
                self::sendToClient(TTRedis::getUserClient($uidItem), $sendMsg);
            }
        } else {
            self::sendToClient(TTRedis::getUserClient($userIds), $sendMsg);
        }
    }

    // 给指定client发消息
    public static function sendMessageToClient($client_id, $body, $mr = null) {

        $sendMsg = self::getSendMessage($body, $mr);

        self::sendToClient($client_id, $sendMsg);
    }

    // 给指定群组发消息
    public static function sendMessageToGroup($group_id, $body, $exclude_client_id, $all = true) {

        PhpLog::Log("sendMessageToGroup[$group_id,$exclude_client_id,$all]:".$body);

        $groupInfo = TTRedis::getGroupInfo($group_id);
        if($groupInfo == null) {
            return;
        }

        $groupMember = $groupInfo[TTDB::LOCAL_GROUP_MEMBERS];
        if(empty($groupMember) || TTPublic::getRecordCount($groupMember) <= 0) {
            return;
        }

        $sendMsg = self::getSendMessage($body);

        foreach ($groupMember as $user_id) {

            // 检测是否要发送全部群组成员
            if(!$all) {
                $group_id = TTRedis::getUserGroup($user_id);
                if(empty($group_id)) {
                    continue;
                }
            }

            // 检测用户是否在线
            $client_id = TTRedis::getUserClient($user_id);
            if(empty($client_id)) {
                continue;
            }
            if(!Gateway::isOnline($client_id)) {
                continue;
            }

            // 检测是否需要排除
            if($client_id == $exclude_client_id) {
                continue;
            }

            // 发送消息
            self::sendToClient($client_id, $sendMsg);
        }
    }

    // 给所有人发消息
    public static function sendMessageToAll($body) {

        $sendMsg = self::getSendMessage($body);

        Gateway::sendToAll($sendMsg);

        PhpLog::Log("sendMessageToAll:".$body);
    }

}