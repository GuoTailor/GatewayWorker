<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/17
 * Time: 下午4:50
 */

namespace Common;

use model\TTPublic;
use Predis\Client;
use Tools\PhpLog;

class TTRedis
{
    const NO_USER_ID = 0;

    const SMS_VERIFY_TIMEOUT = 3600; // 短信校验超时时间1小时
    const GROUP_INTERCOM_TIMEOUT = 61; // 抢麦超时时间 // 60秒客户端自动断开, 如果没断, 61秒服务器强制断开.
    const USER_ONLINE_TIMEOUT = 600; // 10分钟 不在线的超时时间 // 客户端270秒心跳一次，必须大于这个值,

    // 短信验证
    const SMS_VERIFY = "sms_verify"; // mobile // 短信验证码验证通过时间

    // IOS推送消息
    const SEND_MESSAGE = "send_message";

    // Location to address
    const LOCATION_ADDRESS = "location_address";
    const RIDING_RECORD_END = "riding_record_end";

    // socket连接信息, 绑定user_id和client_id
    const CLIENT_USER = "client_user";

    // 用户信息
    const CLIENT_ID = "client_id"; // user_id // 用户在线状态
    const ACCESS_TOKEN = "access_token"; // 用户登录状态 空：未登录，其他未登录的AccessToken
    const ACTIVE_TIME = "active_time"; // user_id // 活跃时间
    const ONLINE = "online"; // 在线标记, 1: 在线，其他：离线
    const DEVICE_TOKEN = "device_token"; // 设备标识
    const LOCATION = "location"; // 定位信息
    const GROUP_ID = "group_id"; // user_id // 群登录状态, 0：表示不在群，其他表示群号
    const GROUP_VOICE_ID = "group_voice_id"; // user_id // 用户在群中的语音ID
    const GROUP_MAX_SPEED = "group_max_speed"; // 最高速度
    const GROUP_TOTAL_TIME = "group_total_time"; // 总时间
    const GROUP_ENTER_TIME = "group_enter_time"; // 进入群组时间

    // 群
    const GROUP_INFO = "group_info"; // 群信息
    const INTERCOM_USER = "intercom_user"; // group_id // 抢到麦的用户id
    const INTERCOM_ACCESSORY = "intercom_accessory"; // group_id // 抢到麦的用户accessory
    const INTERCOM_TIME = "intercom_time"; // group_id // 抢麦时间
    const REAL_LOCATION_USERS = "real_location_users"; // group_id // 实时定位用户
    const GROUP_SHARE_LOCATION = "group_share_location"; // group_id // 共享位置开关

    // ********************************** 公共接口 ********************************** //

    public static function getConnect() {

        static $mClient = null;

        if(empty($mClient)) {
            $mClient = new Client();
            $mClient->connect();
        }

        return $mClient;
    }

    /**
     * 封装 hset 接口
     * @param $key
     * @param $field
     * @param $value
     * @return int
     */
    public static function hset($key, $field, $value) {
        if(is_null($value)) {
            return self::getConnect()->hdel($key, $field);
        }
        return self::getConnect()->hset($key, $field, $value);
    }

    /**
     * 封装 hget 接口
     * @param $key
     * @param $field
     * @return string
     */
    public static function hget($key, $field) {
        return self::getConnect()->hget($key, $field);
    }

    /**
     * 封装 keys 接口
     * @param $pattern
     * @return array
     */
    public static function keys($pattern) {
        return self::getConnect()->keys($pattern);
    }

    /**
     * 检测ID是否有效
     * @param $id
     * @return bool
     */
    public static function isValidId($id) {
        if(!empty($id) && is_numeric($id) && $id > 0) {
            return true;
        }

        return false;
    }

    // ********************************** ISO推送消息接口 ********************************** //

    /**
     * 添加推送消息
     * @param $message
     */
    public static function pushSendMessage($message) {
        self::getConnect()->rpush(self::SEND_MESSAGE, $message);
    }

    /**
     * 取出同时消息
     * @return string
     */
    public static function popSendMessage() {
        return self::getConnect()->lpop(self::SEND_MESSAGE);
    }

    // ********************************** 定位地址转换接口 ********************************** //

    /**
     * 添加推送消息
     * @param $group_user_id
     */
    public static function pushRidingRecord($group_user_id) {
        self::getConnect()->rpush(self::RIDING_RECORD_END, $group_user_id);
    }

    /**
     * 取出通知消息
     * @return string
     */
    public static function popRidingRecord() {
        return self::getConnect()->lpop(self::RIDING_RECORD_END);
    }

    /**
     * 设置定位点的地址
     * @param $location
     * @param $address
     */
    public static function setAddress($location, $address) {
        self::hset(self::LOCATION_ADDRESS, $location, $address);
    }

    /**
     * @param $location
     * @return string
     */
    public static function getAddress($location) {
        return self::hget(self::LOCATION_ADDRESS, $location);
    }

    // ********************************** 短信验证码接口 ********************************** //

    /**
     * 设置短信验证
     * @param $mobile
     */
    public static function setSmsVerify($mobile) {
        self::hset(self::SMS_VERIFY, $mobile, time());
    }

    /**
     * 检测短信是否验证
     * @param $mobile
     * @return bool
     */
    public static function getSmsVerify($mobile) {

        $lastTime = (int)self::hget(self::SMS_VERIFY, $mobile);

        if($lastTime + TTRedis::SMS_VERIFY_TIMEOUT > time()) {
            return true;
        }

        return false;
    }

    // ********************************** SOCKET连接信息接口 ********************************** //

    /**
     * 设置Socket连接信息
     * @param $client_id
     * @param $user_id
     */
    public static function setClientUser($client_id, $user_id) {
        self::hset(self::CLIENT_USER, $client_id, $user_id);
        self::hset(self::CLIENT_USER, $user_id, $client_id);
    }

    /**
     * 删除socket连接信息
     * @param $client_id
     */
    public static function delClientUser($client_id) {
        $user_id = self::getClientUser($client_id);
        self::hset(self::CLIENT_USER, $client_id, null);
        if(!empty($user_id)) {
            self::hset(self::CLIENT_USER, $user_id, null);
        }
    }

    /**
     * 根据连接id获取用户id
     * @param $client_id
     * @return string
     */
    public static function getClientUser($client_id) {
        return self::hget(self::CLIENT_USER, $client_id);
    }

    /**
     * 获取用户的socket连接id
     * @param $user_id
     * @return string
     */
    public static function getUserClient($user_id) {
        return self::hget(self::CLIENT_USER, $user_id);
    }

    // ********************************** 用户接口 ********************************** //

    private static function getUserKey($user_id) {
        return sprintf("user_%d", $user_id);
    }

    /**
     * 设置用户活跃时间
     * @param $user_id
     * @param $time
     */
    public static function setActiveTime($user_id, $time) {
        PhpLog::Log("setActiveTime time=".$time);
        self::hset(self::getUserKey($user_id), self::ACTIVE_TIME, $time);
    }

    /**
     * 获取用户活跃时间
     * @param $user_id
     * @return int
     */
    public static function getActiveTime($user_id) {
        return (int)self::hget(self::getUserKey($user_id), self::ACTIVE_TIME);
    }

    /**
     * 设置访问令牌
     * @param $user_id
     * @param $access_token
     */
    public static function setAccessToken($user_id, $access_token) {
        self::hset(self::getUserKey($user_id), self::ACCESS_TOKEN, $access_token);
    }

    /**
     * 获取访问令牌
     * @param $user_id
     * @return mixed
     */
    public static function getAccessToken($user_id) {
        return self::hget(self::getUserKey($user_id), self::ACCESS_TOKEN);
    }

    /**
     * 设置用户是否在线
     * @param $user_id
     * @param $is_online
     */
    public static function setOnline($user_id, $is_online) {
        self::hset(self::getUserKey($user_id), self::ONLINE, $is_online);
    }

    /**
     * 检测用户是否在线
     * @param $user_id
     * @return string
     */
    public static function isOnline($user_id) {
        return self::hget(self::getUserKey($user_id), self::ONLINE);
    }

    /**
     * 保存用户定位
     * @param $user_id
     * @param $location
     */
    public static function setLocation($user_id, $location) {
        self::hset(self::getUserKey($user_id), self::LOCATION, $location);
    }

    /**
     * 获取用户定位
     * @param $user_id
     * @return string
     */
    public static function getLocation($user_id) {
        return self::hget(self::getUserKey($user_id), self::LOCATION);
    }

    /**
     * 用户所在群组
     * @param $user_id
     * @param $group_id
     */
    public static function setUserGroup($user_id, $group_id) {
        self::hset(self::getUserKey($user_id), self::GROUP_ID, $group_id);
    }

    public static function getUserGroup($user_id) {
        return self::hget(self::getUserKey($user_id), self::GROUP_ID);
    }

    /**
     * 设置用户的voice id
     * @param $user_id
     * @param $group_id
     * @param $voice
     */
    public static function setUserVoice($user_id, $group_id, $voice) {
        self::hset(self::getUserKey($user_id), self::GROUP_VOICE_ID."_".$group_id, $voice);
    }

    /**
     * 获取用户的voice id
     * @param $user_id
     * @param $group_id
     * @return mixed
     */
    public static function getUserVoice($user_id, $group_id) {
        return self::hget(self::getUserKey($user_id), self::GROUP_VOICE_ID."_".$group_id);
    }

    /**
     * 设置最高速度
     * @param $user_id
     * @param $group_id
     * @param $max_speed
     */
    public static function setMaxSpeed($user_id, $group_id, $max_speed) {
        self::hset(self::getUserKey($user_id), self::GROUP_MAX_SPEED."_".$group_id, $max_speed);
    }

    /**
     * 获取最高速度
     * @param $user_id
     * @param $group_id
     * @return string
     */
    public static function getMaxSpeed($user_id, $group_id) {
        $maxSpeed = self::hget(self::getUserKey($user_id), self::GROUP_MAX_SPEED."_".$group_id);
        return $maxSpeed > 999 ? 999 : $maxSpeed;
    }

    private static function delDeviceToken($key) {
        if(!empty($key)) {

            // 读取KEY对应的值，并删除值作为KEY的项
            $value = self::hget(self::DEVICE_TOKEN, $key);
            if(!empty($value)) {
                self::hset(self::DEVICE_TOKEN, $value, null);
            }

            // 删除KEY
            self::hset(self::DEVICE_TOKEN, $key, null);
        }
    }

    /**
     * 设置用户的DeviceToken
     * @param $user_id
     * @param $device_token
     */
    public static function setDeviceToken($user_id, $device_token) {

        // 清除user_id相关信息
        self::delDeviceToken($user_id);

        // 清除device_token相关信息
        self::delDeviceToken($device_token);

        // 设置新的user_id与device_token对应关系
        if(!empty($user_id) && !empty($device_token)) {
            self::hset(self::DEVICE_TOKEN, $user_id, $device_token);
            self::hset(self::DEVICE_TOKEN, $device_token, $user_id);
        }

    }

    // 获取用户的DeviceToken
    public static function getDeviceToken($user_id) {
        return self::hget(self::DEVICE_TOKEN, $user_id);
    }

    /**
     * 检测用户是否超时掉线
     * @param $user_id
     * @return bool
     */
    public static function isTimeOut($user_id) {

        $time = self::getActiveTime($user_id);

        $isTimeout = ($time != 0) && ($time + self::USER_ONLINE_TIMEOUT < TTPublic::getTime());

//        PhpLog::Log("isTimeout [$user_id][$isTimeout] time=".(TTPublic::getTime() - $time));

        return $isTimeout;
    }

    /**
     * 设置总时间
     * @param $user_id
     * @param $group_id
     * @param $total_time
     */
    public static function setTotalTime($user_id, $group_id, $total_time) {
        self::hset(self::getUserKey($user_id), self::GROUP_TOTAL_TIME."_".$group_id, $total_time);
    }

    /**
     * 获取总时间
     * @param $user_id
     * @param $group_id
     * @return string
     */
    public static function getTotalTime($user_id, $group_id) {
        return self::hget(self::getUserKey($user_id), self::GROUP_TOTAL_TIME."_".$group_id);
    }

    /**
     * 设置进入群组时间
     * @param $user_id
     * @param $group_id
     * @param $time
     */
    public static function setEnterTime($user_id, $group_id, $time) {
        self::hset(self::getUserKey($user_id), self::GROUP_ENTER_TIME."_".$group_id, $time);
    }

    /**
     * 获取进入群组时间
     * @param $user_id
     * @param $group_id
     * @return string
     */
    public static function getEnterTime($user_id, $group_id) {
        return self::hget(self::getUserKey($user_id), self::GROUP_ENTER_TIME."_".$group_id);
    }

    // ********************************** 群组接口 ********************************** //

    private static function getGroupKey($group_id) {
        return sprintf("group_%d", $group_id);
    }

//allowTalk
//allowIntercom

    /**
     * 保存群组信息
     * @param $groupInfo
     * @param $groupMember
     */
    public static function setGroupInfo($groupInfo, $groupMember) {

        PhpLog::Log("setGroupInfo ".json_encode($groupInfo).",".json_encode($groupMember));

        $client = self::getConnect();

        $group_id = $groupInfo[TTDB::GROUP_ID];
        $key = self::getGroupKey($group_id);

        $client->hset($key, TTDB::GROUP_MASTER, $groupInfo[TTDB::GROUP_MASTER]);
        $client->hset($key, TTDB::GROUP_GROUP_TYPE, $groupInfo[TTDB::GROUP_GROUP_TYPE]);
        $client->hset($key, TTDB::GROUP_LEADER, $groupInfo[TTDB::GROUP_LEADER]);
        $client->hset($key, TTDB::GROUP_RIDER1, $groupInfo[TTDB::GROUP_RIDER1]);
        $client->hset($key, TTDB::GROUP_RIDER2, $groupInfo[TTDB::GROUP_RIDER2]);
        $client->hset($key, TTDB::GROUP_RIDER3, $groupInfo[TTDB::GROUP_RIDER3]);
        $client->hset($key, TTDB::GROUP_ENDING, $groupInfo[TTDB::GROUP_ENDING]);
        $client->hset($key, TTDB::LOCAL_GROUP_MEMBERS, json_encode($groupMember));

    }

    public static function getGroupInfo($group_id) {

        $client = self::getConnect();

        $key = self::getGroupKey($group_id);

        $groupMember = $client->hget($key, TTDB::LOCAL_GROUP_MEMBERS);

        $groupInfo = array(TTDB::GROUP_MASTER => $client->hget($key, TTDB::GROUP_MASTER),
            TTDB::GROUP_GROUP_TYPE => $client->hget($key, TTDB::GROUP_GROUP_TYPE),
            TTDB::GROUP_LEADER => $client->hget($key, TTDB::GROUP_LEADER),
            TTDB::GROUP_RIDER1 => $client->hget($key, TTDB::GROUP_RIDER1),
            TTDB::GROUP_RIDER2 => $client->hget($key, TTDB::GROUP_RIDER2),
            TTDB::GROUP_RIDER3 => $client->hget($key, TTDB::GROUP_RIDER3),
            TTDB::GROUP_ENDING => $client->hget($key, TTDB::GROUP_ENDING),
            TTDB::LOCAL_GROUP_MEMBERS => json_decode($groupMember, true));

//        PhpLog::Log("getGroupInfo ".json_encode($groupInfo));

        return $groupInfo;
    }

    /**
     * 设置群组抢麦用户
     * @param $group_id
     * @param $userId
     */
    public static function setGroupIntercomUser($group_id, $userId) {
        self::hset(self::getGroupKey($group_id), self::INTERCOM_USER, $userId);
        self::hset(self::getGroupKey($group_id), self::INTERCOM_TIME, time());
    }

    public static function getIntercomUser($group_id) {
        return self::hget(self::getGroupKey($group_id), self::INTERCOM_USER);
    }

    public static function isIntercomTimeout($group_id) {
        $lastTime = (int)self::hget(self::getGroupKey($group_id), self::INTERCOM_TIME);
        return ($lastTime + TTRedis::GROUP_INTERCOM_TIMEOUT < time());
    }

    /**
     * 获取群组抢麦用户
     * @param $group_id
     * @return int|string
     */
    public static function getGroupIntercomUser($group_id) {
        return self::isIntercomTimeout($group_id) ? TTRedis::NO_USER_ID : self::getIntercomUser($group_id);
    }

    /**
     * 设置群组抢麦参数accessory
     * @param $group_id
     * @param $accessory
     */
    public static function setGroupIntercomAccessory($group_id, $accessory) {
        self::hset(self::getGroupKey($group_id), self::INTERCOM_ACCESSORY, $accessory);
    }

    /**
     * 获取群组抢麦参数accessory
     * @param $group_id
     * @return mixed
     */
    public static function getGroupIntercomAccessory($group_id) {
        return self::hget(self::getGroupKey($group_id), self::INTERCOM_ACCESSORY);
    }

    /**
     * 添加实时对讲用户
     * @param $group_id
     * @param $user_id
     */
    public static function addGroupRealLocation($group_id, $user_id) {

        // 获取当前实时定位的用户
        $users = self::getGroupRealLocation($group_id);
        $usersJson = json_decode($users, true);
        if(empty($usersJson)) {
            $usersJson = array();
        }

        // 检查用户是否在实时定位
        if(in_array($user_id, $usersJson)) {
            return;
        }

        // 添加用户到实时定位
        array_push($usersJson, $user_id);
        $users = json_encode($usersJson);

        self::hset(self::getGroupKey($group_id), self::REAL_LOCATION_USERS, $users);
    }

    /**
     * 移除实时对讲用户
     * @param $group_id
     * @param $user_id
     */
    public static function removeGroupRealLocation($group_id, $user_id) {

        // 获取当前实时定位的用户
        $users = self::getGroupRealLocation($group_id);
        $usersJson = json_decode($users, true);

        // 检测之前的实时对讲用户
        if(empty($usersJson)) {
            return;
        }

        // 删除用户
        $usersJson = array_merge(array_diff($usersJson, array($user_id)));
        $users = json_encode($usersJson);

        self::hset(self::getGroupKey($group_id), self::REAL_LOCATION_USERS, $users);
    }

    /**
     * 获取实时对讲用户
     * @param $group_id
     * @return string
     */
    public static function getGroupRealLocation($group_id) {
        return self::hget(self::getGroupKey($group_id), self::REAL_LOCATION_USERS);
    }

    /**
     * 删除群组
     * @param $group_id
     */
    public static function deleteGroup($group_id) {
        self::getConnect()->del(array(self::getGroupKey($group_id)));
    }

    /**
     * 获取群组定位开关
     * @param $user_id
     * @param $group_id
     * @return string
     */
    public static function getGroupShareLocation($user_id, $group_id) {
        return self::hget(self::getUserKey($user_id), self::GROUP_SHARE_LOCATION."_".$group_id);
    }

    /**
     * 设置群组定位开关
     * @param $user_id
     * @param $group_id
     * @param $share_location
     */
    public static function setGroupShareLocation($user_id, $group_id, $share_location) {
        self::hset(self::getUserKey($user_id), self::GROUP_SHARE_LOCATION."_".$group_id, $share_location);
    }

}