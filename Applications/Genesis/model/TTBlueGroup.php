<?php

namespace model;
use Common\TTCode;
use Common\TTDB;
use Common\TTDBConst;
use Common\TTDbFun;
use Exception;
use Socket\SocketInd;
use Tools\PhpLog;

/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/6
 * Time: 下午1:19
 */
class TTBlueGroup
{
    public static function create($access_token, $unique, $users) {

        PhpLog::Log("createGroup ".$access_token.",".$unique.",".$users);

        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];
        $nick_name = $userInfo[TTDB::USER_NICK_NAME];

        TTDbFun::sqlStart();
        try{
            // 读取当前时间
            $dateTime = TTPublic::getDateTime();

            // 获取要添加的群成员ID
            $allUsers = json_decode($users, true);

            // users去重，并检测是否为好用
            if(count($allUsers) > 0) {

                // 获取好友列表
                $friendIdList = TTDbFun::getFriendIdList($my_user_id);

                //  去重
                $allUsers = array_unique($allUsers);
                $friendIdList = array_unique($friendIdList);

                // 添加群组成员
                foreach ($allUsers as $userItem) {

                    // 检测是否为好友
                    if(!in_array($userItem, $friendIdList)) {
                        TTDbFun::sqlCancel();
                        return TTPublic::getResponse(TTCode::TT_NOT_FRIEND);
                    }
                }
            }

            // 创建群组
            $ret = TTDbFun::createBlueGroup($my_user_id, $allUsers, $unique, $dateTime);

            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            // 读取刚刚添加的群组信息
            $groupInfo = TTDbFun::getLastInsertBlueGroupInfo();
            if($groupInfo == null) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }
            $group_id = $groupInfo[TTDB::GROUP_ID];

            // 将自己添加到群组成员
            $ret = TTDbFun::addBlueGroupMember($group_id, $my_user_id, true);
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            // 创建入群邀请
            if(count($allUsers) > 0) {

                // 添加群组成员
                foreach ($allUsers as $userItem) {

                    $ret = TTDbFun::addBlueGroupMember($group_id, $userItem, false);
                    if($ret != TTDBConst::OK) {
                        TTDbFun::sqlCancel();
                        return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                    }

                    // 添加群成员邀请
                    $retCode = TTDbFun::addBlueNotifyGroup($my_user_id, $userItem, $group_id);
                    if($retCode != TTDBConst::OK) {
                        TTDbFun::sqlCancel();
                        return TTPublic::getResponse($retCode);
                    }
                }
            }

            // 获取邀请用户ID列表
            $inviteUserIds = TTDbFun::getBlueGroupInviteUserId($group_id);
            $groupInfo[TTDB::LOCAL_INVITE_USERS] = $inviteUserIds;

            // 通知邀请的群成员
            SocketInd::blueGroupInviteInd($allUsers, $group_id, $my_user_id, $nick_name);

        } catch(Exception $ex) {
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        return TTPublic::getResponse(TTCode::TT_SUCCESS, $groupInfo);
    }

    public static function getInvite($access_token, $update_time) {

        PhpLog::Log("getBlueGroupInvite ".$access_token.",".$update_time);

        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];

        // 获取通知消息
        $notifyInfo = TTDbFun::getBlueGroupInvite($my_user_id, $update_time);

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array("msgList" => $notifyInfo));
    }

    public static function setInvite($access_token, $msg_id, $status) {

        PhpLog::Log("setBlueGroupInvite ".$access_token.",".$msg_id.",".$status);

        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 检测状态参数是否有效(同意或拒绝)
        if($status != TTDBConst::MSG_PROCESS_AGREE
            && $status != TTDBConst::MSG_PROCESS_REFUSE) {
            return TTPublic::getResponse(TTCode::TT_INVALID_PARAM);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];
        $nick_name = $userInfo[TTDB::USER_NICK_NAME];

        // 获取到需要处理的消息id
        $inviteInfo = TTDbFun::getBlueGroupInviteById($msg_id);
        if($inviteInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NO_INVITE);
        }

        $user_id = $inviteInfo[TTDB::NOTIFY_SENDER_ID];
        $group_id = $inviteInfo[TTDB::GROUP_ID];

        TTDbFun::sqlStart();

        try{

            // 如果同意，先清除
            if($status == TTDBConst::MSG_PROCESS_AGREE) {
                TTDbFun::setBlueGroupMemberStatus($group_id, $my_user_id, TTDBConst::STATUS_NORMAL);
            }

            // 更新邀请状态
            $ret = TTDbFun::updateBlueGroupInviteStatus($inviteInfo[TTDB::NOTIFY_ID], $status);
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }
        } catch(Exception $ex) {
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        $userIds = TTDbFun::getBlueGroupMemberIds($group_id, $my_user_id);

        // 通知群组成员，自己同意或拒绝蓝牙群组
        SocketInd::blueGroupResponseInd($group_id, $my_user_id, $nick_name, $status, $userIds);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function getGroup($access_token) {

        PhpLog::Log("getBlueGroup ".$access_token);

            // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];

        // 检测是否有正在骑行的蓝牙群组
        $groupInfo = TTDbFun::getBlueGroupInfoByUser($my_user_id);
        if($groupInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NO_GROUP);
        }

        return TTPublic::getResponse(TTCode::TT_SUCCESS, $groupInfo);
    }
}