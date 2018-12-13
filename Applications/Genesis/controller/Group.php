<?php

namespace controller;

use Common\TTCode;
use Common\TTDB;
use Common\TTDBConst;
use Common\TTDBLimit;
use model\TTAvatar;
use model\TTGroup;
use model\TTNotify;
use model\TTPublic;
use Socket\SocketConst;

class Group
{
    // 1. 创建群组
    public static function createGroup($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");

        $group_name = TTPublic::getValue($request, "group_name");
        $avatar_url = TTPublic::getValue($request, "avatar_url");
        $type = TTPublic::getValue($request, "type");
        $users = TTPublic::getValue($request, "users");
        $notice = TTPublic::getValue($request, "notice");
        $longitude = TTPublic::getValue($request, "longitude");
        $latitude = TTPublic::getValue($request, "latitude");

        $leader = TTPublic::getValue($request, "leader");
        $rider1 = TTPublic::getValue($request, "rider1");
        $rider2 = TTPublic::getValue($request, "rider2");
        $rider3 = TTPublic::getValue($request, "rider3");
        $ending = TTPublic::getValue($request, "ending");

        // 检测参数
        if(empty($access_token) || empty($type)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::createGroup($access_token, $group_name, $avatar_url, $type,
            $users, $notice, $longitude, $latitude, $leader, $rider1, $rider2, $rider3, $ending);
    }

    // 2. 删除群组
    public static function deleteGroup($request) {
        
        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $group_id = TTPublic::getValue($request, "group_id");

        // 检测参数
        if(empty($access_token) || empty($group_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::deleteGroup($access_token, $group_id);
    }

    // 3. 退出群组
    public static function exitGroup($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $group_id = TTPublic::getValue($request, "group_id");

        // 检测参数
        if(empty($access_token) || empty($group_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::exitGroup($access_token, $group_id);
    }

    // 4. 修改群组信息
    public static function modifyGroup($client_id, $request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $group_id = TTPublic::getValue($request, "group_id");
        $group_name = TTPublic::getValue($request, "group_name");
        $notice = TTPublic::getValue($request, "notice");
        $riding_status = TTPublic::getValue($request, "riding_status");

        $leader = TTPublic::getValue($request, "leader");
        $rider1 = TTPublic::getValue($request, "rider1");
        $rider2 = TTPublic::getValue($request, "rider2");
        $rider3 = TTPublic::getValue($request, "rider3");
        $ending = TTPublic::getValue($request, "ending");

        // 检测参数
        if(empty($access_token) || empty($group_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        // 检测参数是否有效
        if(!empty($notice) && !TTDBLimit::isValidNotice($notice)) {
            return TTPublic::getResponse(TTCode::TT_INVALID_DATA);
        }

        return TTGroup::modifyGroup($client_id, $access_token, $group_id, $group_name,
            $notice, $riding_status, $leader, $rider1, $rider2, $rider3, $ending);
    }

    // 5. 拉人进群
    public static function addUsersToGroup($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $group_id = TTPublic::getValue($request, "group_id");
        $users = TTPublic::getValue($request, "users");

        // 检测参数
        if(empty($access_token) || empty($group_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::addUsersToGroup($access_token, $group_id, $users);
    }

    // 6. 获取群组信息
    public static function getGroupInformation($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $group_id = TTPublic::getValue($request, "group_id");

        // 检测参数
        if(empty($access_token)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::getGroupInformation($access_token, $group_id);
    }

    // 6. 获取当前骑行的群组信息
    public static function getRidingGroup($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");

        // 检测参数
        if(empty($access_token)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::getRidingGroup($access_token);
    }

    // 7. 获取骑行记录
    public static function getRidingRecord($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $update_time = TTPublic::getValue($request, "update_time");

        // 检测参数
        if(empty($access_token)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::getRidingRecord($access_token, $update_time);
    }

    // 8. 获取通知消息
    public static function getNotificationMessage($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $update_time = TTPublic::getValue($request, "update_time");

        // 检测参数
        if(empty($access_token)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTNotify::getNotify($access_token,
            TTDBConst::NOTIFICATION_JOIN_GROUP, $update_time);

    }

    // 9. 加群邀请处理
    public static function responseGroupInvite($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $msg_id = TTPublic::getValue($request, "msg_id");
        $status = TTPublic::getValue($request, "status");

        // 检测参数
        if(empty($access_token) || empty($msg_id) || empty($status)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTNotify::setNotifyInvite($access_token, $msg_id, $status);

    }

    // 10. 根据群组口令加入群组
    public static function joinGroup($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $access_code = TTPublic::getValue($request, "access_code");

        // 检测参数
        if(empty($access_token) || empty($access_code)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        // 检测参数是否有效
        if(!TTDBLimit::isValidAccessCode($access_code)) {
            return TTPublic::getResponse(TTCode::TT_INVALID_DATA);
        }

        return TTGroup::joinGroup($access_token, $access_code);

    }

    // 11. 上传头像
    public static function uploadAvatar($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, SocketConst::ACCESS_TOKEN);
        $group_id = TTPublic::getValue($request, "group_id");
        $file = TTPublic::getValue($request, "file");

        // 检测参数
        if(empty($access_token) || empty($group_id) || empty($file)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        if(!file_exists($file)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTAvatar::uploadGroupAvatar($group_id, $access_token, $file);
    }

    // 12. 进入群组
    public static function enterGroup($client_id, $user_id, $request) {

        // 读取参数
        $group_id = TTPublic::getValue($request, SocketConst::GROUP_ID);
        $voice_id = TTPublic::getValue($request, TTDB::LOCAL_VOICE_ID);
        $location = TTPublic::getValue($request, TTDB::LOCAL_LOCATION);

        // 检测参数
        if(empty($user_id) || empty($group_id) || !isset($voice_id)) { // || empty($location)
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::enterGroup($client_id, $user_id, $group_id, $voice_id, $location);
    }

    // 13. 离开群组
    public static function leaveGroup($user_id, $request) {

        // 读取参数
        $group_id = TTPublic::getValue($request, SocketConst::GROUP_ID);

        // 检测参数
        if(empty($user_id) || empty($group_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::leaveGroup($user_id, $group_id);
    }

    // 14. 申请对讲
    public static function acquireIntercom($client_id, $user_id, $request) {

        // 读取参数
        $group_id = TTPublic::getValue($request, SocketConst::GROUP_ID);
        $accessory = TTPublic::getValue($request, TTDB::LOCAL_ACCESSORY);

        // 检测参数
        if(empty($user_id) || empty($group_id) || !isset($accessory)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::acquireIntercom($client_id, $user_id, $group_id, $accessory);
    }

    // 15. 释放对讲
    public static function releaseIntercom($client_id, $user_id, $request) {

        // 读取参数
        $group_id = TTPublic::getValue($request, SocketConst::GROUP_ID);

        // 检测参数
        if(empty($user_id) || empty($group_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::releaseIntercom($client_id, $user_id, $group_id);
    }

    // 17. 获取群组成员
    public static function getGroupMembers($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $group_id = TTPublic::getValue($request, "group_id");
        $update_time = TTPublic::getValue($request, "update_time");

        // 检测参数
        if(empty($access_token) || empty($group_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        if(empty($update_time)) {
            $update_time = 0;
        }

        return TTGroup::getGroupMembers($access_token, $group_id, $update_time);
    }

    // 18. 获取在线群组成员
    public static function getGroupOnline($client_id, $user_id, $request) {

        // 读取参数
        $group_id = TTPublic::getValue($request, SocketConst::GROUP_ID);

        // 检测参数
        if(empty($user_id) || empty($group_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::getGroupOnline($client_id, $group_id);
    }

    // 19. 上传最新定位
    public static function uploadLocation($client_id, $user_id, $request) {

        // 读取参数
        $group_id = TTPublic::getValue($request, SocketConst::GROUP_ID);
        $location = TTPublic::getValue($request, SocketConst::LOCATION);
        $max_speed = TTPublic::getValue($request, SocketConst::MAX_SPEED);

        // 检测参数
        if(empty($user_id) || empty($group_id) || empty($location)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::uploadLocation($client_id, $group_id, $user_id, $location, $max_speed);
    }

    // 20. 进入实时定位
    public static function enterRealLocation($client_id, $user_id, $request) {

        // 读取参数
        $group_id = TTPublic::getValue($request, SocketConst::GROUP_ID);

        // 检测参数
        if(empty($user_id) || empty($group_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::enterRealLocation($client_id, $group_id, $user_id);
    }

    // 21. 退出实时定位
    public static function exitRealLocation($client_id, $user_id, $request) {

        // 读取参数
        $group_id = TTPublic::getValue($request, SocketConst::GROUP_ID);

        // 检测参数
        if(empty($user_id) || empty($group_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::exitRealLocation($client_id, $group_id, $user_id);
    }

    // 22. 上传语音ID
    public static function uploadVoiceId($client_id, $user_id, $request) {

        // 读取参数
        $group_id = TTPublic::getValue($request, SocketConst::GROUP_ID);
        $voice_id = TTPublic::getValue($request, TTDB::LOCAL_VOICE_ID);

        // 检测参数
        if(empty($user_id) || empty($group_id) || !isset($voice_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::uploadVoiceId($client_id, $group_id, $user_id, $voice_id);
    }

    // 23. 删除骑行记录
    public static function delRidingRecord($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $group_id = TTPublic::getValue($request, SocketConst::GROUP_ID);

        // 检测参数
        if(empty($access_token) || empty($group_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::delRidingRecord($access_token, $group_id);
    }

    // 24. 设置共享定位
    public static function setShareLocation($client_id, $user_id, $request) {

        // 读取参数
        $group_id = TTPublic::getValue($request, SocketConst::GROUP_ID);
        $share_location = TTPublic::getValue($request, TTDB::GR_SHARE_LOCATION);

        // 检测参数
        if(empty($group_id) || empty($user_id) || !isset($share_location)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTGroup::setShareLocation($client_id, $group_id, $user_id, $share_location);
    }


}
