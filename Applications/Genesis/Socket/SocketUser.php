<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/12/25
 * Time: 下午12:59
 */

namespace Socket;

use Common\TTRedis;
use GatewayWorker\Lib\Gateway;
use model\TTPublic;
use Tools\PhpLog;

class SocketUser
{

    // 绑定Socket连接
    public static function bindUserAndClient($client_id, $user_id) {

        PhpLog::Log("bindUserAndClient $user_id:$client_id");

        // 建立client_id和user_id的关系
        TTRedis::setClientUser($client_id, $user_id);

        // 设置在线标记
        TTRedis::setOnline($user_id, true);
    }

    // 取消Socket连接绑定
    public static function unbindUserAndClient($client_id) {

        PhpLog::Log("unbindUserAndClient $client_id");

        TTRedis::delClientUser($client_id);

        return true;
    }

    // 设置断网
    public static function updateActiveTime($client_id) {
        $user_id = TTRedis::getClientUser($client_id);
        if(empty($user_id)) {
            return false;
        }

        TTRedis::setActiveTime($user_id, TTPublic::getTime());

        return true;
    }

    public static function loop($client_id, $mr) {

        // 获取loop的用户id
        $user_id = $mr[SocketHead::H2_ID];

        PhpLog::Log("SocketUser loop ".$client_id.",".$user_id);

        if(!empty($user_id)) {

            // 检测是否重新连接
            $last_client_id = TTRedis::getUserClient($user_id);
            if($last_client_id != $client_id) {
                // 重新绑定关系
                self::bindUserAndClient($client_id, $user_id);
            }
        }

        // 响应客户端心跳
        Gateway::sendToClient($client_id, SocketHead::pack($mr));

    }

    private static function closeOtherDevice($user_id, $client_id) {

        // 通知用户下线
        SocketInd::userOtherDeviceLoginInd($user_id, $client_id, "");

        // 退出群组
        SocketGroup::logout($client_id, $user_id);

        // 取消原来的绑定关系
        self::unbindUserAndClient($client_id);
    }

    // 手动登录
    public static function login($client_id, $user_id, $access_token) {

        // 获取原来用户绑定的client_id
        $last_client_id = TTRedis::getUserClient($user_id);
        if(!empty($last_client_id)) {
            self::closeOtherDevice($user_id, $last_client_id);
        }

        // 绑定Socket连接
        self::bindUserAndClient($client_id, $user_id);

        // 保存登录信息
        TTRedis::setAccessToken($user_id, $access_token);

        return true;
    }

    // 手动登出
    public static function logout($client_id, $user_id) {

        // 退出群组
        SocketGroup::logout($client_id, $user_id);

        // 取消Socket连接绑定
        self::unbindUserAndClient($client_id);

        // 清除登录信息
        TTRedis::setAccessToken($user_id, null);

        // 删除最后活跃时间
        TTRedis::setActiveTime($user_id, null);

        // 删除在线标记
        TTRedis::setOnline($user_id, null);
    }

    // 离线
    public static function offline($user_id) {

        PhpLog::Log("SocketUser $user_id is offline!");

        // 删除在线标记
        TTRedis::setOnline($user_id, null);

        // 登出群组
        SocketGroup::logout(null, $user_id);
    }

    // 检测用户是否在线
    public static function isOnline($user_id) {

        // 用户有效，access_token有效，用户在线
        return TTRedis::isValidId($user_id)
            && !empty(TTRedis::getAccessToken($user_id))
            && TTRedis::isOnline($user_id);
    }

    public static function isSocketOnline($user_id) {
        // 读取client_id
        $client_id = TTRedis::getUserClient($user_id);

        if(empty($client_id)) {
            return false;
        }

        return Gateway::isOnline($client_id);
    }

    public static function timeoutOffline() {
        $allUser = TTRedis::keys("user_*");

        foreach ($allUser as $userItem) {

            // 获取用户ID
            list($u, $user_id) = explode("_", $userItem, 2);

            // 用户在线,Socket不在线,超时
            if(SocketUser::isOnline($user_id)
                && !SocketUser::isSocketOnline($user_id)
                && TTRedis::isTimeOut($user_id)) {

                // 用户离线
                SocketUser::offline($user_id);

                PhpLog::Task("timeoutOffline user_id=".$user_id);
            }
        }
    }

    // 输出调试信息
    public static function debugInfo($client_id) {

        $debugInfo = '';
        $user_id = TTRedis::getClientUser($client_id);
        if(!empty($user_id)) {
            $debugInfo = "online: ".(TTRedis::isOnline($user_id) ? 1 : 0);
            $debugInfo .= ", client_user: ".$user_id;
            $debugInfo .= ", user_client: ".TTRedis::getUserClient($user_id);
            $debugInfo .= ", access_token: ".TTRedis::getAccessToken($user_id);
            $debugInfo .= ", active_time: ".TTRedis::getActiveTime($user_id);
            $group_id = TTRedis::getUserGroup($user_id);
            if(!empty($group_id)) {
                $debugInfo .= ", user_group: ".$group_id;
                $debugInfo .= ", group_intercom_user: ".TTRedis::getGroupIntercomUser($group_id);
                $debugInfo .= ", group_info: ".json_encode(TTRedis::getGroupInfo($group_id));
            }
        }

        PhpLog::Log("SocketUser.debugInfo ".$debugInfo);
    }

}