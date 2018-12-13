<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2018/1/12
 * Time: 下午3:12
 */

namespace Socket;


use Common\TTDBConst;
use Common\TTRedis;
use Tools\PhpLog;

class SocketGroup
{
    const STATUS_OUT = 0;
    const STATUS_IN = 1;
    const STATUS_PAUSE = 2;

    // 用户主动登录群组
    public static function login($client_id, $user_id, $group_id, $voice_id) {

        PhpLog::Log("SocketGroup login ".$user_id.",".$group_id.",".$voice_id);

        // 保存在线群组
        TTRedis::setUserGroup($user_id, $group_id);
        TTRedis::setUserVoice($user_id, $group_id, $voice_id);

        // 通知群成员
        SocketInd::enterGroupInd($client_id, $group_id, $user_id, $voice_id);
    }

    // 用户主动登出群组
    public static function logout($client_id, $user_id) {

        PhpLog::Log("SocketGroup logout ".$client_id.",".$user_id);

        // 检测在线用户id
        if(!TTRedis::isValidId($user_id)) {
            return false;
        }

        // 获取在线群组id
        $group_id = TTRedis::getUserGroup($user_id);
        if(!TTRedis::isValidId($group_id)) {
            return false;
        }

        // 检测自己是否在抢麦
        $intercomUserId = TTRedis::getGroupIntercomUser($group_id);
        if($intercomUserId == $user_id) {
            // 关闭抢麦
            TTRedis::setGroupIntercomUser($group_id, TTRedis::NO_USER_ID);
        }

        // 清除在线群组
        TTRedis::setUserGroup($user_id, null);
        TTRedis::setUserVoice($user_id, $group_id, null);

        // socket通知其他组员
        SocketInd::leaveGroupInd($client_id, $group_id, $user_id);

        return true;
    }

    // 群主停止或删除骑行
    public static function close($group_id, $user_list) {
        // 删除用户的在线状态
        foreach ($user_list as $user_id) {
            $online_group_id = TTRedis::getUserGroup($user_id);
            if($online_group_id == $group_id) {
                // 清除在线群组
                TTRedis::setUserGroup($user_id, null);
            }
        }
    }

    public static function isOnline($user_id, $group_id) {

//        PhpLog::Log("SocketGroup isOnline ".$user_id.",".$group_id);

        // 检测用户是否在线
        if(!SocketUser::isOnline($user_id)) {
            return false;
        }

        // 获取用户在线的群组
        $online_group_id = TTRedis::getUserGroup($user_id);
        if(!TTRedis::isValidId($online_group_id)) {
            return false;
        }

        // 检测用户是否所在群组是否正确
        if($online_group_id != $group_id) {
            return false;
        }

        return true;
    }

    /**
     * 改变语音ID
     * @param $client_id => 连接id
     * @param $group_id => 群组ID
     * @param $user_id => 用户ID
     * @param $voice_id => 语音ID
     * @return bool 是否改变
     */
    public static function changeVoiceId($client_id, $group_id, $user_id, $voice_id) {

        // 检测group_id
        if(TTRedis::getUserGroup($user_id) != $group_id) {
            return false;
        }

        // 保存语音ID
        TTRedis::setUserVoice($user_id, $group_id, $voice_id);

        // 通知群成员
        SocketInd::voiceIdChangeInd($client_id, $group_id, $user_id, $voice_id);

        return true;
    }

    public static function timeoutIntercom() {

        $allGroup = TTRedis::keys("group_*");

        foreach ($allGroup as $groupItem) {

            // 获取群组ID
            list($u, $group_id) = explode("_", $groupItem, 2);

            // 获取群组对讲用户
            $intercomUserId = TTRedis::getIntercomUser($group_id);

            if($intercomUserId == TTDBConst::NO_USER_ID) {
                continue;
            }

            // 检测是否超时
            if(TTRedis::isIntercomTimeout($group_id)) {

                // 清除群组对讲用户
                TTRedis::setGroupIntercomUser($group_id, TTDBConst::NO_USER_ID);

                // 通知群组
                SocketInd::releaseIntercomInd(null,
                    $group_id, TTDBConst::NO_USER_ID, TTRedis::NO_USER_ID);

                PhpLog::Task("timeoutIntercom group_id=".$group_id.", intercomUserId=".$intercomUserId);
            }

        }
    }

}