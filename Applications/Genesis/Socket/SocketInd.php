<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/12/20
 * Time: 上午10:19
 */

namespace Socket;

use Common\TTDB;
use Common\TTDBConst;
use Common\TTDbFun;
use Common\TTRedis;
use GatewayWorker\Lib\Gateway;
use model\TTLocation;
use model\TTPublic;
use ThirdParty\IOSPush;
use Tools\MessageTools;
use Tools\PhpLog;

class SocketInd
{
    const GROUP_LEADER = "leader";
    const GROUP_RIDER1 = "rider1";
    const GROUP_RIDER2 = "rider2";
    const GROUP_RIDER3 = "rider3";
    const GROUP_ENDING = "ending";

    const CODE = "code";
    const MESSAGE = "message";

    // 用户登入
    public static function userLoginInd($user_id, $nick_name) {

        PhpLog::Log("userLoginInd ".$user_id);

        // 读取好友列表
        $friend_id_list = TTDbFun::getFriendIdList($user_id);
        if(TTPublic::getRecordCount($friend_id_list) <= 0) {
            return;
        }

        //  通知好友
        $msgRsp = array(TTDB::USER_ID => $user_id,
            TTDB::USER_NICK_NAME => $nick_name);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::USER_LOGIN_IND,
            SocketConst::IND_DATA => $msgRsp);

        MessageTools::sendMessageToUserId($friend_id_list, json_encode($retBody));
    }

    // 用户登出
    public static function userLogoutInd($user_id, $nick_name) {

        PhpLog::Log("userLogoutInd ".$user_id);

        // 读取好友列表
        $friend_id_list = TTDbFun::getFriendIdList($user_id);
        if(TTPublic::getRecordCount($friend_id_list) <= 0) {
            return;
        }

        //  通知好友
        $msgRsp = array(TTDB::USER_ID => $user_id,
            TTDB::USER_NICK_NAME => $nick_name);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::USER_LOGOUT_IND,
            SocketConst::IND_DATA => $msgRsp);

        MessageTools::sendMessageToUserId($friend_id_list, json_encode($retBody));
    }

    // 在其他设备登录
    public static function userOtherDeviceLoginInd($user_id, $client_id, $device_name) {

        PhpLog::Log("userOtherDeviceLoginInd ".$user_id.",".$client_id.",".$device_name);

        //  通知好友
        $msgRsp = array(TTDB::USER_DEVICE_NAME => $device_name);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::USER_OTHER_DEVICE_LOGIN_IND,
            SocketConst::IND_DATA => $msgRsp);
        PhpLog::println("nmka", json_encode($retBody));
        MessageTools::sendMessageToClient($client_id, json_encode($retBody));
    }

    // 通知用户 受邀为好友
    public static function friendInviteInd($receiver, $user_id, $nick_name) {

        PhpLog::Log("friendInviteInd ".$receiver.",".$user_id);

        $msgRsp = array(TTDB::USER_ID => $user_id,
            TTDB::USER_NICK_NAME => $nick_name);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::FRIEND_INVITE_IND,
            SocketConst::IND_DATA => $msgRsp);

        MessageTools::sendMessageToUserId($receiver, json_encode($retBody));

        $show_name = IOSPush::getShowName($user_id, $nick_name);
        IOSPush::push($receiver, "好友邀请", "[$show_name] 要添加你为好友", $retBody);
    }

    // 通知用户 受邀应答
    public static function friendResponseInd($receiver, $user_id, $nick_name, $status) {

        PhpLog::Log("friendResponseInd ".$receiver.",".$user_id.",".$status);

        $msgRsp = array(TTDB::USER_ID => $user_id,
            TTDB::USER_NICK_NAME => $nick_name,
            TTDB::STATUS => $status);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::FRIEND_RESPONSE_IND,
            SocketConst::IND_DATA => $msgRsp);

        MessageTools::sendMessageToUserId($receiver, json_encode($retBody));
    }

    // 通知用户 被好友删除
    public static function friendDeleteInd($receiver, $user_id, $nick_name) {

        PhpLog::Log("friendDeleteInd ".$receiver.",".$user_id);

        $msgRsp = array(TTDB::USER_ID => $user_id,
            TTDB::USER_NICK_NAME => $nick_name);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::FRIEND_DELETE_IND,
            SocketConst::IND_DATA => $msgRsp);

        MessageTools::sendMessageToUserId($receiver, json_encode($retBody));
    }

    // 用户加入群组
    public static function groupJoinInd($group_id, $user_id, $nick_name) {

        PhpLog::Log("groupJoinInd ".$group_id.",".$user_id);

        $msgRsp = array(TTDB::GROUP_ID => $group_id,
            TTDB::USER_ID => $user_id,
            TTDB::USER_NICK_NAME => $nick_name);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_JOIN_IND,
            SocketConst::IND_DATA => $msgRsp);

        $client_id = TTRedis::getUserClient($user_id);

        MessageTools::sendMessageToGroup($group_id, json_encode($retBody), $client_id);
    }

    // 邀请用户加入群组
    public static function groupInviteInd($receiver_list, $group_id, $user_id, $nick_name) {

        PhpLog::Log("groupInviteInd ".json_encode($receiver_list).",".$group_id.",".$user_id);

        $msgRsp = array(TTDB::GROUP_ID => $group_id,
            TTDB::USER_ID => $user_id,
            TTDB::USER_NICK_NAME => $nick_name);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_INVITE_IND,
            SocketConst::IND_DATA => $msgRsp);

        MessageTools::sendMessageToUserId($receiver_list, json_encode($retBody));

        $show_name = IOSPush::getShowName($user_id, $nick_name);
        IOSPush::pushToUsers($receiver_list, "邀请入群", "[$show_name] 邀请你入群",
            $retBody);
    }

    // 取消邀请用户加入群组
    public static function groupCancelInviteInd($receiver_list, $group_id, $user_id, $nick_name) {

        PhpLog::Log("groupCancelInviteInd ".json_encode($receiver_list).",".$group_id.",".$user_id);

        // 检测接收信息的人是否为空
        if(TTPublic::getRecordCount($receiver_list) <= 0) {
            return;
        }

        $msgRsp = array(TTDB::GROUP_ID => $group_id,
            TTDB::USER_ID => $user_id,
            TTDB::USER_NICK_NAME => $nick_name);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_CANCEL_INVITE_IND,
            SocketConst::IND_DATA => $msgRsp);

        MessageTools::sendMessageToUserId($receiver_list, json_encode($retBody));
    }

    // 通知组员邀请了哪些用户
    public static function groupInviteOtherInd($group_id, $user_id, $allUsers) {

        PhpLog::Log("groupInviteOtherInd ".$group_id.",".$user_id.",".json_encode($allUsers));

        $msgRsp = array(TTDB::GROUP_ID => $group_id,
            TTDB::LOCAL_INVITE_USERS => json_encode($allUsers));

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_INVITE_OTHER_IND,
            SocketConst::IND_DATA => $msgRsp);

        $client_id = TTRedis::getUserClient($user_id);

        MessageTools::sendMessageToGroup($group_id, json_encode($retBody), $client_id);
    }

    // 通知群组成员 某某某加入群组
    public static function groupResponseInd($group_id, $user_id, $nick_name, $status) {

        PhpLog::Log("groupResponseInd ".$group_id.",".$user_id.",".$status);

        $msgRsp = array(TTDB::GROUP_ID => $group_id,
            TTDB::USER_ID => $user_id,
            TTDB::USER_NICK_NAME => $nick_name,
            TTDB::STATUS => $status);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_RESPONSE_IND,
            SocketConst::IND_DATA => $msgRsp);

        $client_id = TTRedis::getUserClient($user_id);

        MessageTools::sendMessageToGroup($group_id, json_encode($retBody), $client_id);
    }

    // 某某某退出群组
    public static function groupExitInd($group_id, $user_id, $nick_name, $manage) {

        PhpLog::Log("groupExitInd ".$group_id.",".$user_id);

        $msgRsp = array(TTDB::GROUP_ID => $group_id,
            TTDB::USER_ID => $user_id,
            TTDB::USER_NICK_NAME => $nick_name,
            TTDB::LOCAL_MANAGE => $manage);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_EXIT_IND,
            SocketConst::IND_DATA => $msgRsp);

        $client_id = TTRedis::getUserClient($user_id);

        MessageTools::sendMessageToGroup($group_id, json_encode($retBody), $client_id);
    }

    // 群主删除群组
    public static function groupDeleteInd($group_id, $user_id, $nick_name) {

        PhpLog::Log("groupDeleteInd ".$group_id.",".$user_id);

        $msgRsp = array(TTDB::GROUP_ID => $group_id,
            TTDB::USER_ID => $user_id,
            TTDB::USER_NICK_NAME => $nick_name);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_DELETE_IND,
            SocketConst::IND_DATA => $msgRsp);

        $client_id = TTRedis::getUserClient($user_id);

        MessageTools::sendMessageToGroup($group_id, json_encode($retBody), $client_id);
    }

    // 通知群组成员 群组信息变更
    public static function groupChangedInd($group_id, $user_id, $updateList) {

        PhpLog::Log("groupChangedInd ".$group_id.",".$user_id.",".json_encode($updateList));

        $updateList[TTDB::GROUP_ID] = $group_id;

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_CHANGED_IND,
            SocketConst::IND_DATA => $updateList);

        $client_id = TTRedis::getUserClient($user_id);

        MessageTools::sendMessageToGroup($group_id, json_encode($retBody), $client_id);
    }

    // 进入群组
    public static function enterGroupInd($client_id, $group_id, $user_id, $voice_id) {

        PhpLog::Log("enterGroupInd ".$client_id.",".$group_id.",".$user_id.",".$voice_id);

        $location = "";
        if(TTRedis::getGroupShareLocation($user_id, $group_id) != TTDBConst::SHARE_LOCATION_OFF) {
            $location = TTLocation::getLastLocation($user_id);
        }

        // 数据为group_id
        $msgRsp = array(TTDB::USER_ID => $user_id,
            TTDB::GROUP_ID => $group_id,
            TTDB::LOCAL_VOICE_ID => $voice_id,
            TTDB::LOCAL_LOCATION => $location);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_ENTER_IND,
            SocketConst::IND_DATA => $msgRsp);

        PhpLog::Log("[$group_id]sendToGroup: ".json_encode($retBody));

        MessageTools::sendMessageToGroup($group_id, json_encode($retBody), $client_id, false);
    }

    // 离开群组
    public static function leaveGroupInd($client_id, $group_id, $user_id) {

        PhpLog::Log("leaveGroupInd ".$client_id.",".$group_id.",".$user_id);

        // 数据为group_id
        $msgRsp = array(TTDB::USER_ID => $user_id,
            TTDB::GROUP_ID => $group_id);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_LEAVE_IND,
            SocketConst::IND_DATA => $msgRsp);

        MessageTools::sendMessageToGroup($group_id, json_encode($retBody), $client_id, false);
    }

    // 申请对讲
    public static function acquireIntercomInd($client_id, $group_id, $user_id, $accessory) {

        PhpLog::Log("acquireIntercomInd ".$client_id.",".$group_id.",".$user_id);

        $msgRsp = array(TTDB::GROUP_ID => $group_id,
            TTDB::USER_ID => $user_id,
            TTDB::LOCAL_ACCESSORY => $accessory);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_ACQUIRE_INTERCOM_IND,
            SocketConst::IND_DATA => $msgRsp);

        MessageTools::sendMessageToGroup($group_id, json_encode($retBody), null, false);
    }

    // 释放对讲
    public static function releaseIntercomInd($client_id, $group_id, $user_id, $use_user_id) {

        PhpLog::Log("releaseIntercomInd ".$client_id.",".$group_id.",".$user_id.",".$use_user_id);

        $msgRsp = array(TTDB::GROUP_ID => $group_id,
            TTDB::USER_ID => $use_user_id);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_RELEASE_INTERCOM_IND,
            SocketConst::IND_DATA => $msgRsp);

        MessageTools::sendMessageToGroup($group_id, json_encode($retBody), null, false);
    }

    // 定位改变
    public static function locationChangeInd($client_id, $group_id, $user_id,
                                             $latitude, $longitude, $bearing) {

        // 检测是否开启位置分享
        if(TTRedis::getGroupShareLocation($user_id, $group_id) == TTDBConst::SHARE_LOCATION_OFF) {
            return;
        }

        if(empty($bearing)) {
            $bearing = 0;
        }

        PhpLog::Log("locationChangeInd $client_id,$group_id,$user_id,$latitude,$longitude,$bearing");

        // 准备要发送的数据
        $msgRsp = array(TTDB::GROUP_ID => $group_id,
            TTDB::USER_ID => $user_id,
            TTDB::LOCAL_LOCATION => "$latitude,$longitude,$bearing");

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_LOCATION_CHANGE_IND,
            SocketConst::IND_DATA => $msgRsp);

        $sendMsg = json_encode($retBody);

        // 获取群组用户
        $groupInfo = TTRedis::getGroupInfo($group_id);
        $groupMember = $groupInfo[TTDB::LOCAL_GROUP_MEMBERS];
        if(empty($groupMember) || TTPublic::getRecordCount($groupMember) <= 0) {
            return;
        }

        PhpLog::Log("locationChangeInd groupMember=".json_encode($groupMember));

        // 获取进入实时定位的用户
        $real_location_users = TTRedis::getGroupRealLocation($group_id);
        $realLocation = json_decode($real_location_users, true);
        if(empty($realLocation) || TTPublic::getRecordCount($realLocation) <= 0) {
            return;
        }

        PhpLog::Log("locationChangeInd realLocation=".json_encode($realLocation));

        // 向实时定位用户发送定位改变消息
        foreach ($groupMember as $item) {

            // 排除自己
            if($item == $user_id) {
                continue;
            }

            // 检测是否在实时定位
            if(!in_array($item, $realLocation)) {
                continue;
            }

            // 获取client_id
            $client_id = TTRedis::getUserClient($item);
            if(empty($client_id)) {
                continue;
            }

            // 检测用户是否在线
            if(!Gateway::isOnline($client_id)) {
                continue;
            }

            // 发送消息
            MessageTools::sendMessageToClient($client_id, $sendMsg);
        }
    }

    // 群组成员语音ID变化
    public static function voiceIdChangeInd($client_id, $group_id, $user_id, $voice_id) {

        PhpLog::Log("voiceIdChangeInd ".$client_id.",".$group_id.",".$user_id.",".$voice_id);

        // 数据为group_id
        $msgRsp = array(TTDB::USER_ID => $user_id,
            TTDB::GROUP_ID => $group_id,
            TTDB::LOCAL_VOICE_ID => $voice_id);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_VOICE_ID_CHANGE_IND,
            SocketConst::IND_DATA => $msgRsp);

        PhpLog::Log("[$group_id]sendToGroup: ".json_encode($retBody));

        MessageTools::sendMessageToGroup($group_id, json_encode($retBody), $client_id, false);
    }

    // 新骑行记录
    public static function groupNewRecordInd($user_id) {

        PhpLog::Log("groupNewRecordInd ".$user_id);

        // 数据为group_id
        $msgRsp = array(TTDB::USER_ID => $user_id);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_NEW_RECORD_IND,
            SocketConst::IND_DATA => $msgRsp);

        MessageTools::sendMessageToUserId($user_id, json_encode($retBody));
    }

    // 位置共享开关改变
    public static function shareLocationChangeInd($client_id, $group_id, $user_id, $share_location) {

        PhpLog::Log("shareLocationChangeInd ".$client_id.",".$group_id.",".$user_id.",".$share_location);

        $location = "";
        if($share_location != TTDBConst::SHARE_LOCATION_OFF) {
            $location = TTLocation::getLastLocation($user_id);
        }

        // 数据为group_id
        $msgRsp = array(TTDB::USER_ID => $user_id,
            TTDB::GROUP_ID => $group_id,
            TTDB::GR_SHARE_LOCATION => $share_location,
            TTDB::LOCAL_LOCATION => $location);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::GROUP_SHARE_LOCATION_CHANGE_IND,
            SocketConst::IND_DATA => $msgRsp);

        PhpLog::Log("[$group_id]sendToGroup: ".json_encode($retBody));

        MessageTools::sendMessageToGroup($group_id, json_encode($retBody), $client_id, false);
    }

    // 邀请用户加入蓝牙群组
    public static function blueGroupInviteInd($receiver_list, $group_id, $user_id, $nick_name) {

        PhpLog::Log("blueGroupInviteInd ".json_encode($receiver_list).",".$group_id.",".$user_id);

        $msgRsp = array(TTDB::GROUP_ID => $group_id,
            TTDB::USER_ID => $user_id,
            TTDB::USER_NICK_NAME => $nick_name);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::BLUE_GROUP_INVITE_IND,
            SocketConst::IND_DATA => $msgRsp);

        MessageTools::sendMessageToUserId($receiver_list, json_encode($retBody));

        $show_name = IOSPush::getShowName($user_id, $nick_name);
        IOSPush::pushToUsers($receiver_list, "邀请蓝牙对讲", "[$show_name] 邀请你加入蓝牙对讲",
            $retBody);
    }

    // 通知群组成员 某某某同意或拒绝加入蓝牙群组
    public static function blueGroupResponseInd($group_id, $user_id, $nick_name, $status, $userIds) {

        PhpLog::Log("blueGroupResponseInd ".$group_id.",".$user_id.",".$status);

        $msgRsp = array(TTDB::GROUP_ID => $group_id,
            TTDB::USER_ID => $user_id,
            TTDB::USER_NICK_NAME => $nick_name,
            TTDB::STATUS => $status);

        $retBody = array(SocketConst::IND_SENDOR => $user_id,
            SocketConst::IND_MSG_CODE => SocketCode::BLUE_GROUP_INVITE_RESPONSE_IND,
            SocketConst::IND_DATA => $msgRsp);

        MessageTools::sendMessageToUserId($userIds, json_encode($retBody));
    }
}