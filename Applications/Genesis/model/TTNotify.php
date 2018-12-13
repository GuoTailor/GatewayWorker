<?php

namespace model;
use Common\TTCode;
use Common\TTDB;
use Common\TTDBConst;
use Common\TTDbFun;
use Common\TTRedis;
use Exception;
use Socket\SocketInd;

/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/6
 * Time: 下午1:19
 */
class TTNotify
{
    public static function addNotifyInvite($user_id, $friend_id, $add_type) {
        try {
            $inviteInfo = TTDbFun::getNotifyInviteBySender($user_id, $friend_id);
            if($inviteInfo == null) {
                // 添加好友申请
                $ret = TTDbFun::addNotifyInvite($user_id, $friend_id, $add_type);
                if($ret != TTDBConst::OK) {
                    return TTCode::TT_DB_FAILED;
                }
            } else {
                // 检测是否已经发出邀请
                if($inviteInfo[TTDB::STATUS] == TTDBConst::MSG_PROCESS_INIT) {
                    return TTCode::TT_ALREADY_INVITE;
                }

                // 更新邀请信息
                $ret = TTDbFun::updateNotifyInvite($user_id, $friend_id, $add_type);
                if($ret != TTDBConst::OK) {
                    return TTCode::TT_DB_FAILED;
                }
            }
        }catch(Exception $ex) {
            return TTCode::TT_DB_FAILED;
        }

        return TTCode::TT_SUCCESS;
    }

    public static function addNotifyGroup($user_id, $friend_id, $group_id) {
        try {
            $groupInfo = TTDbFun::getNotifyGroupBySender($user_id, $friend_id, $group_id);
            if($groupInfo == null) {
                // 添加好友申请
                $ret = TTDbFun::addNotifyGroup($user_id, $friend_id, $group_id);
                if($ret != TTDBConst::OK) {
                    return TTCode::TT_DB_FAILED;
                }
            } else {
                // 检测是否已经发出邀请
                if($groupInfo[TTDB::STATUS] == TTDBConst::MSG_PROCESS_INIT) {
                    TTCode::TT_ALREADY_INVITE;
                }

                // 更新邀请信息
                $ret = TTDbFun::updateNotifyGroup($user_id, $friend_id, $group_id);
                if($ret != TTDBConst::OK) {
                    return TTCode::TT_DB_FAILED;
                }
            }
        } catch(Exception $ex) {
            return TTCode::TT_DB_FAILED;
        }

        return TTCode::TT_SUCCESS;
    }

    public static function getNotify($access_token, $type, $update_time) {
        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];

        // 获取通知消息
        $notifyInfo = TTDbFun::getNotification($my_user_id, $type, $update_time);

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array("msgList" => $notifyInfo));
    }

    public static function setNotifyInvite($access_token, $msg_id, $status) {
        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 检测状态参数是否有效
        if($status != TTDBConst::MSG_PROCESS_AGREE
            && $status != TTDBConst::MSG_PROCESS_REFUSE
            && $status != TTDBConst::MSG_PROCESS_IGNORE) {
            return TTPublic::getResponse(TTCode::TT_INVALID_PARAM);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];
        $nick_name = $userInfo[TTDB::USER_NICK_NAME];

        // 获取到需要处理的消息id
        $inviteInfo = TTDbFun::getNotifyInviteById($msg_id);
        if($inviteInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NO_INVITE);
        }

        $user_id = $inviteInfo[TTDB::NOTIFY_SENDER_ID];
        $type = $inviteInfo[TTDB::NOTIFY_TYPE];
        $group_id = $inviteInfo[TTDB::GROUP_ID];

        TTDbFun::sqlStart();
        try{
            // 更新邀请状态
            $ret = TTDbFun::updateNotifyStatus($inviteInfo[TTDB::NOTIFY_ID], $status);
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            if($status == TTDBConst::MSG_PROCESS_AGREE) {
                if($type == TTDBConst::NOTIFICATION_ADD_FRIEND) {
                    // 添加好友
                    $add_type = $inviteInfo[TTDB::NOTIFY_ADD_TYPE];
                    $ret = TTDbFun::addFriend($my_user_id, $user_id, $add_type);
                    if($ret != TTDBConst::OK) {
                        TTDbFun::sqlCancel();
                        return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                    }
                } else {

                    // 检测群是否存在
                    $groupInfo = TTDbFun::getGroupFullInfo($group_id);
                    if($groupInfo == null) {
                        TTDbFun::sqlCancel();
                        return TTPublic::getResponse(TTCode::TT_NO_GROUP);
                    }

                    // 检测群的状态
                    $ridingStatus = $groupInfo[TTDB::GROUP_RIDING_STATUS];
                    if($ridingStatus == TTDBConst::RIDING_STATUS_END) {
                        TTDbFun::sqlOk(); // 骑行结束后更新邀请
                        return TTPublic::getResponse(TTCode::TT_GROUP_IS_END);
                    }

                    // 检测是否有正在骑行的群
                    $currentGroupInfo = TTDbFun::getGroupInfoByUser($my_user_id);
                    if($currentGroupInfo != null) {
                        TTDbFun::sqlCancel();
                        return TTPublic::getResponse(TTCode::TT_ALREADY_GROUP);
                    }

                    // 获取群的最大人数
                    $group_type = $groupInfo[TTDB::GROUP_GROUP_TYPE];
                    $numberLimit = TTPublic::getGroupMemberLimit($group_type);

                    // 检测群成员是否达到最大限制
                    $numberIdList = TTDbFun::getGroupMembersId($group_id);
                    if(TTPublic::getRecordCount($numberIdList) >= $numberLimit) {
                        TTDbFun::sqlCancel();
                        return TTPublic::getResponse(TTCode::TT_GROUP_IS_FULL);
                    }

                    // 添加群成员
                    $groupStatus = TTDbFun::getGroupMemberStatus($group_id, $my_user_id);
                    if($groupStatus == null) {
                        // 添加群成员
                        $ret = TTDbFun::addGroupMember($group_id, $my_user_id, $group_type);
                    } else if($groupStatus[TTDB::STATUS] == TTDBConst::STATUS_DELETE) {
                        // 更新群成员状态
                        $ret = TTDbFun::setGroupMemberStatus($group_id, $my_user_id,
                            TTDBConst::STATUS_NORMAL);
                    } else {
                        $ret = TTDBConst::OK;
                    }

                    if($ret != TTDBConst::OK) {
                        TTDbFun::sqlCancel();
                        return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                    }

                    // 骑行记录 - 开始
                    $ret = TTRidingRecord::begin($my_user_id, $group_id, $group_type);
                    if($ret != TTDBConst::OK) {
                        TTDbFun::sqlCancel();
                        return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                    }

                    // 检测群成员是否超过最大限制
                    $member_list = TTDbFun::getGroupMembersId($group_id);
                    if(TTPublic::getRecordCount($numberIdList) > $member_list) {
                        TTDbFun::sqlCancel();
                        return TTPublic::getResponse(TTCode::TT_GROUP_IS_FULL);
                    }

                    // 更新Redis群组信息
                    TTRedis::setGroupInfo($groupInfo, $member_list);
                }
            }
        } catch(Exception $ex) {
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        // 通知信息
//        if($status == TTDB::MSG_PROCESS_AGREE) {
        if($type == TTDBConst::NOTIFICATION_ADD_FRIEND) {
            // 通知XXX加为好友
            SocketInd::friendResponseInd($user_id, $my_user_id, $nick_name, $status);
        } else {
            // 通知XXX加入骑行
            SocketInd::groupResponseInd($group_id, $my_user_id, $nick_name, $status);
        }
//        }

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

}