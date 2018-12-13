<?php

namespace model;
use Common\TTCode;
use Common\TTDB;
use Common\TTDBConst;
use Common\TTDbFun;
use Socket\SocketInd;

/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/6
 * Time: 下午1:19
 */
class TTFriend
{
    public static function addFriend($access_token, $user_id, $add_type) {

        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];
        $nick_name = $userInfo[TTDB::USER_NICK_NAME];

        // 检测要添加的好友是否是自己
        if($my_user_id == $user_id) {
            return TTPublic::getResponse(TTCode::TT_ADD_SELF_FRIEND);
        }

        // 检测好友是否存在
        $friendInfo = TTDbFun::getInfoById($user_id);
        if($friendInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NOT_USER);
        }

        // 检测是否为好友
        if(TTDbFun::isFriend($my_user_id, $user_id)) {
            return TTPublic::getResponse(TTCode::TT_ALREADY_FRIEND);
        }

        // 添加通知消息
        $retCode = TTNotify::addNotifyInvite($my_user_id, $user_id, $add_type);
        if($retCode != TTCode::TT_SUCCESS) {
            return TTPublic::getResponse($retCode);
        }

        // socket通知
        SocketInd::friendInviteInd($user_id, $my_user_id, $nick_name);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function deleteFriend($access_token, $user_id) {

        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];
        $nick_name = $userInfo[TTDB::USER_NICK_NAME];

        // 检测是否为好友
        if(!TTDbFun::isFriend($my_user_id, $user_id)) {
            return TTPublic::getResponse(TTCode::TT_NOT_FRIEND);
        }

        // 删除好友
        if(TTDbFun::deleteFriend($my_user_id, $user_id) != TTDBConst::OK) {
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        SocketInd::friendDeleteInd($user_id, $my_user_id, $nick_name);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function getFriendList($access_token, $update_time) {
        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];

        // 获取好友，包括删除的
        if($update_time > 0) {
            $updateIdList = TTDbFun::getFriendIdListIncludeDelete($my_user_id, $update_time);
        } else {
            $updateIdList = null;
        }

        // 获取全部好友添加类型
        $addTypeList = TTDbFun::getFriendAddTypeList($my_user_id);

        // 查找全部好友
        $friendList = TTDbFun::getFriendUpdateList($my_user_id, $addTypeList, $updateIdList, $update_time);

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array("users" => $friendList));
    }

    public static function remarkUserInfo($access_token, $friend_id, $nickname, $mobile) {
        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];

        // 添加备注
        $ret = TTDbFun::setRemarkInfo($my_user_id, $friend_id, $nickname, $mobile);
        if($ret != TTDBConst::OK) {
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

}