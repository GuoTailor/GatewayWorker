<?php

namespace model;
use Common\TTCode;
use Common\TTDB;
use Common\TTDBConst;
use Common\TTDbFun;
use Common\TTProfile;
use Common\TTRedis;
use Exception;
use Socket\SocketGroup;
use Socket\SocketInd;
use Tools\PhpLog;

/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/6
 * Time: 下午1:19
 */
class TTGroup
{
    // 是否为管理员
    private static function isManage($groupInfo, $user_id) {
        $leader = $groupInfo[TTDB::GROUP_LEADER];
        $rider1 = $groupInfo[TTDB::GROUP_RIDER1];
        $rider2 = $groupInfo[TTDB::GROUP_RIDER2];
        $rider3 = $groupInfo[TTDB::GROUP_RIDER3];
        $ending = $groupInfo[TTDB::GROUP_ENDING];
        if($user_id == $leader
            || $user_id == $rider1
            || $user_id == $rider2
            || $user_id == $rider3
            || $user_id == $ending) {
            return true;
        }

        return false;
    }

    public static function createGroup($access_token, $group_name, $avatar_url, $type,
                                       $users, $notice, $longitude, $latitude,
                                       $leader, $rider1, $rider2, $rider3, $ending) {
        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];
        $nick_name = $userInfo[TTDB::USER_NICK_NAME];

        // 检测是否有正在骑行的群
        $myGroupInfo = TTDbFun::getGroupInfoByUser($my_user_id);
        if($myGroupInfo != null) {
            return TTPublic::getResponse(TTCode::TT_ALREADY_GROUP);
        }

        TTDbFun::sqlStart();
        try{
            // 读取当前时间
            $dateTime = TTPublic::getDateTime();

            // 创建群组
            $ret = TTDbFun::createGroup($my_user_id, $group_name, $avatar_url, $type,
                $notice, $longitude, $latitude,
                $leader, $rider1, $rider2, $rider3, $ending, $dateTime);

            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            // 读取刚刚添加的群组信息
            $groupInfo = TTDbFun::getLastInsertGroupInfo();
            if($groupInfo == null) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }
            $group_id = $groupInfo[TTDB::GROUP_ID];

            // 将自己添加到群组成员
            $group_type = $groupInfo[TTDB::GROUP_GROUP_TYPE];
            $ret = TTDbFun::addGroupMember($group_id, $my_user_id, $group_type);
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

            // 获取要添加的群成员ID
            if(!empty($users)) {
                $allUsers = json_decode($users, true);
            } else {
                $allUsers = [];
            }

            // 创建入群邀请
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

                    // 添加群成员邀请
                    $retCode = TTNotify::addNotifyGroup($my_user_id, $userItem, $group_id);
                    if($retCode != TTCode::TT_SUCCESS) {
                        TTDbFun::sqlCancel();
                        return TTPublic::getResponse($retCode);
                    }
                }
            }

            // 获取邀请用户ID列表
            $inviteUserIds = TTDbFun::getGroupInviteUserId($group_id);
            $groupInfo[TTDB::LOCAL_INVITE_USERS] = $inviteUserIds;

            // 通知邀请的群成员
            SocketInd::groupInviteInd($allUsers, $group_id, $my_user_id, $nick_name);

        } catch(Exception $ex) {
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        // 设置群主,类型和管理员
        $member_list = TTDbFun::getGroupMembersId($group_id, null);
        TTRedis::setGroupInfo($groupInfo, $member_list);

        return TTPublic::getResponse(TTCode::TT_SUCCESS, $groupInfo);
    }

    public static function deleteGroup($access_token, $group_id) {
        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];
        $nick_name = $userInfo[TTDB::USER_NICK_NAME];

        // 获取群信息
        $groupInfo = TTDbFun::getGroupFullInfo($group_id);
        if($groupInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NO_GROUP);
        }

        // 非群主无法删除群
        if($groupInfo[TTDB::GROUP_MASTER] != $my_user_id) {
            return TTPublic::getResponse(TTCode::TT_NOT_GROUP_MASTER);
        }

        TTDbFun::sqlStart();

        try {
            // 删除群
            $ret = TTDbFun::setGroupStatus($group_id, TTDBConst::STATUS_DELETE);
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            // 读取未处理的群邀请
            $inviteUsers = TTDbFun::getGroupInviteUserId($group_id);

            // 处理该群的邀请信息
            $ret = TTDbFun::removeGroupInvite($group_id);

            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            // 通知未处理群邀请的用户
            SocketInd::groupInviteInd($inviteUsers, $group_id, $my_user_id, $nick_name);

            // 骑行记录 - 删除
            $ret = TTRidingRecord::delete($my_user_id, $group_id);
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

        } catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        // 通知群组成员
        SocketInd::groupDeleteInd($group_id, $my_user_id, $nick_name);

        // 删除该群组的Redis
        TTRedis::deleteGroup($group_id);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function exitGroup($access_token, $group_id) {
        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];
        $nick_name = $userInfo[TTDB::USER_NICK_NAME];

        // 获取群信息
        $groupInfo = TTDbFun::getGroupFullInfo($group_id);
        if($groupInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NO_GROUP);
        }

        // 群主无法退出群
        if($groupInfo[TTDB::GROUP_MASTER] == $my_user_id) {
            return TTPublic::getResponse(TTCode::TT_IS_GROUP_MASTER);
        }

        TTDbFun::sqlStart();

        try {

            // 退出群
            $ret = TTDbFun::setGroupMemberStatus($group_id, $my_user_id, TTDBConst::STATUS_DELETE);
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            // 检测是否为管理员身份
            $update = null;
            switch ($my_user_id) {
                case $groupInfo[TTDB::GROUP_LEADER]:
                    $update[TTDB::GROUP_LEADER] = TTDBConst::NO_USER_ID;
                    break;

                case $groupInfo[TTDB::GROUP_RIDER1]:
                    $update[TTDB::GROUP_RIDER1] = TTDBConst::NO_USER_ID;
                    break;

                case $groupInfo[TTDB::GROUP_RIDER2]:
                    $update[TTDB::GROUP_RIDER2] = TTDBConst::NO_USER_ID;
                    break;

                case $groupInfo[TTDB::GROUP_RIDER3]:
                    $update[TTDB::GROUP_RIDER3] = TTDBConst::NO_USER_ID;
                    break;

                case $groupInfo[TTDB::GROUP_ENDING]:
                    $update[TTDB::GROUP_ENDING] = TTDBConst::NO_USER_ID;
                    break;

                default:
                    break;

            }

            // 如果是管理员，取消管理员身份
            if($update != null) {
                $ret = TTDbFun::updateGroupInfo($group_id, $update);
                if($ret != TTDBConst::OK) {
                    TTDbFun::sqlCancel();
                    return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                }
            }

            // 获取群组成员
            $member_list = TTDbFun::getGroupMembersId($group_id, null);

            // 骑行记录 - 结束
            $ret = TTRidingRecord::end($my_user_id, $group_id, $groupInfo[TTDB::GROUP_AVATAR],
                TTPublic::getRecordCount($member_list));
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            // 更新Redis群组信息
            TTRedis::setGroupInfo($groupInfo, $member_list);

            // 通知群组成员
            SocketInd::groupExitInd($group_id, $my_user_id, $nick_name,
                $update == null ? 0 : 1);

            TTDbFun::sqlOk();
        } catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function modifyGroup($client_id, $access_token, $group_id, $group_name,
                                       $notice, $riding_status,
                                       $leader, $rider1, $rider2, $rider3, $ending) {

        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];

        // 获取群信息
        $groupInfo = TTDbFun::getGroupFullInfo($group_id);
        if($groupInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NO_GROUP);
        }

        // 群主无法退出群
        if($groupInfo[TTDB::GROUP_MASTER] != $my_user_id) {
            return TTPublic::getResponse(TTCode::TT_NOT_GROUP_MASTER);
        }

        // 转换参数
        $updateList = [];

        if(isset($group_name)) {
            $updateList[TTDB::GROUP_GROUP_NAME] = $group_name;
        }

        if(isset($notice)) {
            $updateList[TTDB::GROUP_NOTICE] = $notice;
        }

        if(isset($riding_status)) {
            $updateList[TTDB::GROUP_RIDING_STATUS] = $riding_status;
        }

        if(isset($leader)) {
            $updateList[TTDB::GROUP_LEADER] = $leader;
        }

        if(isset($rider1)) {
            $updateList[TTDB::GROUP_RIDER1] = $rider1;
        }

        if(isset($rider2)) {
            $updateList[TTDB::GROUP_RIDER2] = $rider2;
        }

        if(isset($rider3)) {
            $updateList[TTDB::GROUP_RIDER3] = $rider3;
        }

        if(isset($ending)) {
            $updateList[TTDB::GROUP_ENDING] = $ending;
        }

        if(count($updateList) <= 0) {
            return TTPublic::getResponse(TTCode::TT_DB_NOT_CHANGE);
        }

        TTDbFun::sqlStart();

        try {
            // 修改群信息
            $ret = TTDbFun::updateGroupInfo($group_id, $updateList);
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            // 获取群信息
            $groupInfo = TTDbFun::getGroupFullInfo($group_id);
            if($groupInfo == null) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_NO_GROUP);
            }

            $new_status = $groupInfo[TTDB::GROUP_RIDING_STATUS];

            // 读取群组成员
            $member_list = TTDbFun::getGroupMembersId($group_id, null);

            // 处理该群的邀请信息
            if($new_status == TTDBConst::RIDING_STATUS_END) {

                // 读取未处理的群邀请
                $inviteUsers = TTDbFun::getGroupInviteUserId($group_id);

                $ret = TTDbFun::removeGroupInvite($group_id);
                if($ret != TTDBConst::OK) {
                    TTDbFun::sqlCancel();
                    return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                }

                // 骑行记录 - 结束骑行
                foreach ($member_list as $user_id) {
                    $ret = TTRidingRecord::end($user_id, $group_id, $groupInfo[TTDB::GROUP_AVATAR],
                        TTPublic::getRecordCount($member_list));
                    if($ret != TTDBConst::OK) {
                        TTDbFun::sqlCancel();
                        return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                    }
                }

                // 通知未处理群邀请的用户
                SocketInd::groupCancelInviteInd($inviteUsers, $group_id, $my_user_id,
                    $userInfo[TTDB::USER_NICK_NAME]);

            } else {
                // 更新本地Redis群信息
                TTRedis::setGroupInfo($groupInfo, $member_list);

                // 处理骑行记录
                if($new_status == TTDBConst::RIDING_STATUS_PAUSE) {
                    foreach ($member_list as $user_id) {
                        TTRidingRecord::leave($user_id, $group_id);
                    }
                }
            }

            // 检测状态是否变化
            SocketInd::groupChangedInd($group_id, $my_user_id, $updateList);
        } catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        // 获取正在对讲的用户
        $intercomUserId = TTRedis::getGroupIntercomUser($group_id);

        // 检测对讲用户是否取消抢麦权限
        if($intercomUserId != TTRedis::NO_USER_ID
            && !self::allowIntercom($group_id, $intercomUserId)) {

            // 关闭抢麦
            TTRedis::setGroupIntercomUser($group_id, TTRedis::NO_USER_ID);

            // 通知所有用户释放对讲
            SocketInd::releaseIntercomInd($client_id,
                $group_id, $my_user_id, $intercomUserId);
        }

        if($new_status == TTDBConst::RIDING_STATUS_END) {
            // 删除该群组的Redis
            TTRedis::deleteGroup($group_id);
        }

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            $groupInfo);
    }

    public static function addUsersToGroup($access_token, $group_id, $users) {
        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];
        $nick_name = $userInfo[TTDB::USER_NICK_NAME];

        // 检测群组是否存在
        $groupInfo = TTDbFun::getGroupInfo($group_id);
        if($groupInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NO_GROUP);
        }

        // 检测群组状态

        // 检测自己是否为群成员
        $groupStatus = TTDbFun::getGroupMemberStatus($group_id, $my_user_id);
        if($groupStatus == null || $groupStatus[TTDB::STATUS] != TTDBConst::STATUS_NORMAL) {
            return TTPublic::getResponse(TTCode::TT_NOT_GROUP_MEMBER);
        }

        TTDbFun::sqlStart();
        try{
            // 获取群成员ID
            if(!empty($users)) {
                $allUsers = json_decode($users, true);
            } else {
                $allUsers = [];
            }

            // 获取用户好友列表
            $friendIdList = TTDbFun::getFriendIdList($my_user_id);

            // 获取群成员
            $memberStatusList = TTDbFun::getGroupMembersStatus($group_id);

            // 添加群组成员
            foreach ($allUsers as $userItem) {

                // 检测是否为好友
                if(!in_array($userItem, $friendIdList)) {
                    TTDbFun::sqlCancel();
                    return TTPublic::getResponse(TTCode::TT_NOT_FRIEND);
                }

                // 检测是否为群成员
                if(isset($memberStatusList[$userItem])
                    && $memberStatusList[$userItem] == TTDBConst::STATUS_NORMAL) {
                    continue;
                }

                // 添加群成员邀请
                $retCode = TTNotify::addNotifyGroup($my_user_id, $userItem, $group_id);
                if($retCode != TTCode::TT_SUCCESS) {
                    TTDbFun::sqlCancel();
                    return TTPublic::getResponse($retCode);
                }
            }
        } catch(Exception $ex) {
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        // 通知用户加入群组
        SocketInd::groupInviteInd($allUsers, $group_id, $my_user_id, $nick_name);

        // 通知群组成员邀请了哪些用户
        SocketInd::groupInviteOtherInd($group_id, $my_user_id, $allUsers);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function getGroupInformation($access_token, $group_id) {
        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取群信息
        $groupInfo = TTDbFun::getGroupInfo($group_id);
        if($groupInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NO_GROUP);
        }

        // 获取群成员信息
        $group_members = TTDbFun::getMembersInGroup($group_id);
        $groupInfo[TTDB::LOCAL_GROUP_MEMBERS] = $group_members;
        $groupInfo[TTDB::LOCAL_MEMBERS_COUNT] = TTDbFun::getMembersCountInGroup($group_id);

        return TTPublic::getResponse(TTCode::TT_SUCCESS, $groupInfo);
    }

    public static function getRidingGroup($access_token) {
        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];

        // 检测是否有正在骑行的群
        $groupInfo = TTDbFun::getGroupInfoByUser($my_user_id);
        if($groupInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NO_GROUP);
        }

        $group_id = $groupInfo[TTDB::GROUP_ID];

        // 获取邀请用户ID列表
        $inviteUserIds = TTDbFun::getGroupInviteUserId($group_id);
        $groupInfo[TTDB::LOCAL_INVITE_USERS] = $inviteUserIds;

        $groupInfo[TTDB::LOCAL_ALLOW_TALK] = self::allowTalk($group_id, $my_user_id);
        $groupInfo[TTDB::LOCAL_ALLOW_INTERCOM] = self::allowIntercom($group_id, $my_user_id);

        return TTPublic::getResponse(TTCode::TT_SUCCESS, $groupInfo);
    }

    /**
     * 获取骑行记录
     * @param $access_token
     * @param $update_time
     * @return array
     */
    public static function getRidingRecord($access_token, $update_time) {
        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];

        $recordInfo = TTDbFun::getRecordInfo($my_user_id, $update_time);

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array("riding_records" => $recordInfo));
    }

    /**
     * 删除骑行记录
     * @param $access_token
     * @param $group_id
     * @return array
     */
    public static function delRidingRecord($access_token, $group_id) {
        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];

        // 骑行记录 - 删除
        $ret = TTRidingRecord::delete($my_user_id, $group_id);
        if($ret != TTDBConst::OK) {
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function getGroupMembers($access_token, $group_id, $update_time) {
        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取群成员信息
        $group_members = TTDbFun::getMembersInfo($group_id, $update_time);

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array(TTDB::LOCAL_GROUP_MEMBERS => $group_members));
    }

    public static function joinGroup($access_token, $access_code) {

        // 获取用户信息
        $userInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取自己的用户ID
        $my_user_id = $userInfo[TTDB::USER_ID];
        $nick_name = $userInfo[TTDB::USER_NICK_NAME];

        // 检测是否有正在骑行的群
        $groupInfo = TTDbFun::getGroupInfoByUser($my_user_id);
        if($groupInfo != null) {
            return TTPublic::getResponse(TTCode::TT_ALREADY_GROUP);
        }

        // 获取群ID
        $groupInfo = TTDbFun::getGroupFullInfoByAccessCode($access_code);
        if($groupInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NO_GROUP);
        }

        // 检测群的状态
        $ridingStatus = $groupInfo[TTDB::GROUP_RIDING_STATUS];
        if($ridingStatus == TTDBConst::RIDING_STATUS_END) {
            return TTPublic::getResponse(TTCode::TT_GROUP_IS_END);
        }

        $group_id = $groupInfo[TTDB::GROUP_ID];

        // 获取群的最大人数
        $group_type = $groupInfo[TTDB::GROUP_GROUP_TYPE];
        $numberLimit = TTPublic::getGroupMemberLimit($group_type);

        // 检测群成员是否达到最大限制
        $numberIdList = TTDbFun::getGroupMembersId($group_id);
        if(TTPublic::getRecordCount($numberIdList) >= $numberLimit) {
            return TTPublic::getResponse(TTCode::TT_GROUP_IS_FULL);
        }

        TTDbFun::sqlStart();

        try {
        	// 加入群
            $groupStatus = TTDbFun::getGroupMemberStatus($group_id, $my_user_id);
            if($groupStatus == null) {
                // 添加群成员
                $ret = TTDbFun::addGroupMember($group_id, $my_user_id, $group_type);
            } else if($groupStatus[TTDB::STATUS] == TTDBConst::STATUS_DELETE) {
                // 更新群成员状态
                $ret = TTDbFun::setGroupMemberStatus($group_id, $my_user_id, TTDBConst::STATUS_NORMAL);

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
            if(TTPublic::getRecordCount($member_list) > $numberLimit) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_GROUP_IS_FULL);
            }

            // 处理该群的邀请信息
            $ret = TTDbFun::removeGroupInvite($group_id, $my_user_id);

            // 更新Redis群组信息
            TTRedis::setGroupInfo($groupInfo, $member_list);

        } catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        if($ret != TTDBConst::OK) {
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        // 通知群组成员
        SocketInd::groupJoinInd($group_id, $my_user_id, $nick_name);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);

    }

    private static function allowTalk($group_id, $user_id) {

        $groupInfo = TTRedis::getGroupInfo($group_id);

        // 获取群组类型 1. 好友, 2.俱乐部, 3.巡逻.
        $group_type = $groupInfo[TTDB::GROUP_GROUP_TYPE];

        // 检测自己是否为管理角色
        $is_manage = self::isManage($groupInfo, $user_id);

        if(($group_type == TTDBConst::GROUP_TYPE_FRIEND)
            || ($group_type == TTDBConst::GROUP_TYPE_CLUB && $is_manage)
            || ($group_type == TTDBConst::GROUP_TYPE_CRUISE && $is_manage)) {
            return true;
        }

        return false;
    }

    private static function allowIntercom($group_id, $user_id) {

        PhpLog::Log("allowIntercom ".$group_id.",".$user_id);

        $groupInfo = TTRedis::getGroupInfo($group_id);

        // 获取群组类型 1. 好友, 2.俱乐部, 3.巡逻.
        $group_type = $groupInfo[TTDB::GROUP_GROUP_TYPE];

        // 检测自己是否为管理角色
        $is_manage = self::isManage($groupInfo, $user_id);

        PhpLog::Log("allowIntercom ".$group_type.",".$is_manage);

        if(($group_type == TTDBConst::GROUP_TYPE_FRIEND)
            || ($group_type == TTDBConst::GROUP_TYPE_CLUB)
            || ($group_type == TTDBConst::GROUP_TYPE_CRUISE && $is_manage)
            || ($group_type == TTDBConst::GROUP_TYPE_INTERCOM)) {
            PhpLog::Log("allowIntercom true");
            return true;
        }

        PhpLog::Log("allowIntercom false");
        return false;
    }

    private static function getUserVoiceList($group_id) {

        $user_voice_list = array();

        $groupInfo = TTRedis::getGroupInfo($group_id);
        if($groupInfo == null) {
            return $user_voice_list;
        }

        $groupMember = $groupInfo[TTDB::LOCAL_GROUP_MEMBERS];
        if(empty($groupMember) || TTPublic::getRecordCount($groupMember) <= 0) {
            return $user_voice_list;
        }

        PhpLog::Log("getUserVoiceList groupMember=".json_encode($groupMember));

        foreach ($groupMember as $item) {
            if(SocketGroup::isOnline($item, $group_id)) {
                $itemVoice = TTRedis::getUserVoice($item, $group_id);
                if(isset($itemVoice)) {
                    $user_voice_list[$item] = $itemVoice;
                }
            }
        }

        PhpLog::Log("getUserVoiceList user_voice_list=".json_encode($user_voice_list));

        return $user_voice_list;
    }

    private static function getUserLocationList($group_id) {

        $user_location_list = array();

        $groupInfo = TTRedis::getGroupInfo($group_id);
        if($groupInfo == null) {
            return $user_location_list;
        }

        $groupMember = $groupInfo[TTDB::LOCAL_GROUP_MEMBERS];
        if(empty($groupMember) || TTPublic::getRecordCount($groupMember) <= 0) {
            return $user_location_list;
        }

        PhpLog::Log("getUserLocationList groupMember=".json_encode($groupMember));

        foreach ($groupMember as $item) {
            if(SocketGroup::isOnline($item, $group_id)
                && TTRedis::getGroupShareLocation($item, $group_id) != TTDBConst::SHARE_LOCATION_OFF) {
                $location = TTLocation::getLastLocation($item);
                if(!empty($location)) {
                    $user_location_list[$item] = $location;
                }
            }
        }

        PhpLog::Log("getUserLocationList user_location_list=".json_encode($user_location_list));

        return $user_location_list;
    }

    // 进入群组
    public static function enterGroup($client_id, $user_id, $group_id, $voice_id, $location) {

        // 获取群组信息
        $groupInfo = TTDbFun::getGroupInfo($group_id);
        if($groupInfo == null) {
            return TTPublic::getResponse(TTCode::TT_FAILED);
        }

        // 获取在线成员
        $member_list = TTDbFun::getGroupMembersId($group_id);

        // 保存群组信息到Redis
        TTRedis::setGroupInfo($groupInfo, $member_list);

        // 登录群组
        SocketGroup::login($client_id, $user_id, $group_id, $voice_id);

        // 保存起始定位
        TTLocation::saveStartLocation($group_id, $user_id, $location);

        // 骑行记录 - 进入
        TTRidingRecord::enter($user_id, $group_id);

        // 获取设备控制信息
        $device_control = TTDeviceControl::get($groupInfo[TTDB::GROUP_GROUP_TYPE],
            self::isManage($groupInfo, $user_id));

        $intercom_user_id = TTRedis::getGroupIntercomUser($group_id);
        $accessory = TTRedis::getGroupIntercomAccessory($group_id);

        // 获取群组成员
        $user_voice_list = self::getUserVoiceList($group_id);

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array(TTDB::LOCAL_INTERCOM_USER_ID => $intercom_user_id,
                TTDB::LOCAL_ACCESSORY => $accessory,
                TTDB::LOCAL_LOCATION_UPLOAD_PERIOD => TTProfile::UPLOAD_LOCATION_PERIOD,
                TTDB::LOCAL_REAL_UPLOAD_PERIOD => TTProfile::UPLOAD_REAL_PERIOD,
                TTDB::LOCAL_USER_VOICE_LIST => $user_voice_list,
                TTDB::LOCAL_DEVICE_CONTROL => $device_control,
                TTDB::LOCAL_ALLOW_TALK => self::allowTalk($group_id, $user_id),
                TTDB::LOCAL_ALLOW_INTERCOM => self::allowIntercom($group_id, $user_id),
                TTDB::LOCAL_MAX_SPEED => TTRedis::getMaxSpeed($user_id, $group_id),
                TTDB::LOCAL_TOTAL_TIME => TTRedis::getTotalTime($user_id, $group_id),
                TTDB::LOCAL_SIGNAL_PERIOD => TTProfile::SIGNAL_PERIOD,
                TTDB::LOCAL_SIGNAL_COUNT => TTProfile::SIGNAL_COUNT,
                TTDB::LOCAL_SIGNAL_DELAY => TTProfile::SIGNAL_DELAY));
    }

    /**
     * 离开骑行
     * @param $user_id
     * @param $group_id
     * @return array
     */
    public static function leaveGroup($user_id, $group_id) {

        // 骑行记录 - 离开
        TTRidingRecord::leave($user_id, $group_id);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    // 抢麦
    public static function acquireIntercom($client_id, $user_id, $group_id, $accessory) {

        $groupInfo = TTRedis::getGroupInfo($group_id);

        // 获取群组类型 1. 好友, 2.俱乐部, 3.巡逻.
        $group_type = $groupInfo[TTDB::GROUP_GROUP_TYPE];

        // 检测自己是否为管理角色
        $isSelfManage = self::isManage($groupInfo, $user_id);

        // 读取当前抢到麦的用户Id
        $intercomUserId = TTRedis::getGroupIntercomUser($group_id);

        // 如果自己之前就抢到麦,先释放，再抢
        if($intercomUserId == $user_id) {
            TTRedis::setGroupIntercomUser($group_id, TTRedis::NO_USER_ID);
            $intercomUserId = TTRedis::getGroupIntercomUser($group_id);
        }

        // 检测是否有人抢到mic
        if(TTRedis::isValidId($intercomUserId)) { // 当前有人抢到mic

            // 管理员角色优先级高于普通成员
            if(!$isSelfManage || self::isManage($groupInfo, $intercomUserId)) {
                return TTPublic::getResponse(TTCode::TT_INTERCOM_HAS_USER);
            }
        } else { // 当前没有人抢到mic

            // 巡游模式下非管理员不能抢麦
            if($group_type == TTDBConst::GROUP_TYPE_CRUISE && !$isSelfManage) {
                return TTPublic::getResponse(TTCode::TT_INTERCOM_NO_AUTH);
            }
        }

        // 保存抢麦用户
        TTRedis::setGroupIntercomUser($group_id, $user_id);
        if(TTRedis::getGroupIntercomUser($group_id) != $user_id) {
            return TTPublic::getResponse(TTCode::TT_INTERCOM_FAILED);
        }

        // 保存抢麦accessory
        TTRedis::setGroupIntercomAccessory($group_id, $accessory);

        // 抢麦成功通知
        SocketInd::acquireIntercomInd($client_id, $group_id, $user_id, $accessory);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }


    public static function releaseIntercom($client_id, $user_id, $group_id) {

        // 读取当前抢到麦的用户Id
        $intercomUserId = TTRedis::getGroupIntercomUser($group_id);

        PhpLog::Log("releaseIntercom ".$intercomUserId.",".$user_id);

        // 检测自己是否抢到麦
        if($intercomUserId != TTRedis::NO_USER_ID && $intercomUserId != $user_id) {
            return TTPublic::getResponse(TTCode::TT_FAILED);
        }

        // 取消对讲用户
        TTRedis::setGroupIntercomUser($group_id, TTRedis::NO_USER_ID);

        // 通知群组其他用户
        SocketInd::releaseIntercomInd($client_id, $group_id, $user_id, TTRedis::NO_USER_ID);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    // 获取群组在线成员
    public static function getGroupOnline($client_id, $group_id) {

        // 获取群组成员
        $user_voice_list = self::getUserVoiceList($group_id);

        // 获取群组在线用户
        $location_list = self::getUserLocationList($group_id);

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array(TTDB::LOCAL_USER_VOICE_LIST => $user_voice_list,
                TTDB::LOCAL_LOCATION => $location_list));
    }

    // 上传定位
    public static function uploadLocation($client_id, $group_id, $user_id, $location, $max_speed) {

        list($latitude, $longitude, $bearing) = explode(",", $location);

        // 保存定位
        TTLocation::saveLocation($group_id, $user_id, $latitude, $longitude, $max_speed);

        // 通知实时对讲的用户
        SocketInd::locationChangeInd($client_id, $group_id, $user_id, $latitude, $longitude, $bearing);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    // 进入实时定位
    public static function enterRealLocation($client_id, $group_id, $user_id) {

        // 检测是否开启位置分享
        if(TTRedis::getGroupShareLocation($user_id, $group_id) == TTDBConst::SHARE_LOCATION_OFF) {
            return TTPublic::getResponse(TTCode::TT_GROUP_NOT_SHARE_LOCATION);
        }

        // 保存实时对讲状态
        TTRedis::addGroupRealLocation($group_id, $user_id);

        // 获取群组在线用户
        $location_list = self::getUserLocationList($group_id);

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array(TTDB::LOCAL_LOCATION => $location_list));
    }

    // 退出实时定位
    public static function exitRealLocation($client_id, $group_id, $user_id) {

        // 删除实时对讲状态
        TTRedis::removeGroupRealLocation($group_id, $user_id);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    // 上传语音ID
    public static function uploadVoiceId($client_id, $group_id, $user_id, $voice_id) {

        if(!SocketGroup::changeVoiceId($client_id, $group_id, $user_id, $voice_id)) {
            return TTPublic::getResponse(TTCode::TT_FAILED);
        }

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    /**
     * 设置共享位置参数
     * @param $client_id
     * @param $group_id
     * @param $user_id
     * @param $share_location
     * @return array
     */
    public static function setShareLocation($client_id, $group_id, $user_id, $share_location) {

        $ret = TTDbFun::setGroupShareLocation($user_id, $group_id, $share_location);
        if($ret != TTDBConst::OK) {
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        // 保存位置共享开关
        TTRedis::setGroupShareLocation($user_id, $group_id, $share_location);

        // 通知群组成员共享位置开关改变
        SocketInd::shareLocationChangeInd($client_id, $group_id, $user_id, $share_location);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

}