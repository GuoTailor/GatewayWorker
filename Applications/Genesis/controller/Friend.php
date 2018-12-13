<?php
namespace controller;

use Common\TTCode;
use Common\TTDBConst;
use model\TTFriend;
use model\TTNotify;
use model\TTPublic;

class Friend
{
    // 1. 添加好友
    public static function addFriend($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $user_id = TTPublic::getValue($request, "user_id");
        $add_type = TTPublic::getValue($request, "add_type");

        // 检测参数
        if(empty($access_token) || empty($user_id) || empty($add_type)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTFriend::addFriend($access_token, $user_id, $add_type);
    }

    // 2. 删除好友
    public static function deleteFriend($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $user_id = TTPublic::getValue($request, "user_id");

        // 检测参数
        if(empty($access_token) || empty($user_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTFriend::deleteFriend($access_token, $user_id);
    }

    // 3. 获取好友列表　
    public static function getFriendList($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $update_time = TTPublic::getValue($request, "update_time");

        // 检测参数
        if(empty($access_token)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTFriend::getFriendList($access_token, $update_time);
    }

    // 4. 获取通知消息
    public static function getNotificationMessage($request) {


        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $update_time = TTPublic::getValue($request, "update_time");

        // 检测参数
        if(empty($access_token)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTNotify::getNotify($access_token,
            TTDBConst::NOTIFICATION_ADD_FRIEND, $update_time);

    }

    // 5. 好友邀请处理
    public static function responseFriendInvite($request) {


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

    // 6. 备注用户信息
    public static function remarkUserInfo($request) {


        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $friend_id = TTPublic::getValue($request, "friend_id");
        $nickname = TTPublic::getValue($request, "nickname");
        $mobile = TTPublic::getValue($request, "mobile");

        // 检测参数
        if(empty($access_token) || empty($friend_id)
            || (!isset($nickname) && !isset($mobile))) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTFriend::remarkUserInfo($access_token, $friend_id, $nickname, $mobile);

    }

}
