<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/12/21
 * Time: 上午8:32
 */

namespace Socket;

use Common\TTCode;
use Common\TTRedis;
use controller\Device;
use controller\Friend;
use controller\Group;
use controller\User;
use controller\BlueGroup;
use model\TTPublic;
use Tools\MessageTools;
use Tools\PhpLog;

class SocketEvent
{
    public static function handle($client_id, $mr, $body) {

        PhpLog::Log("Request[Body 1]: ".$body);
        $bodyObj = json_decode($body, true);
//        PhpLog::Log("Request[Body 2]: ".json_encode($bodyObj));

        $sendor = $mr[SocketHead::H2_ID];
        $msgCode = $bodyObj[SocketConst::REQ_MSG_CODE];

        // 检测是否已经登录
        if($msgCode > SocketCode::IN_LOGIN_REQ_BEGIN
            || $msgCode > SocketCode::IN_LOGIN_REQ_END) {

            $access_token = $bodyObj[SocketConst::ACCESS_TOKEN];

            // 检测参数
            if(empty($sendor) || empty($access_token)) {
                SocketReq::response($client_id, $mr, TTCode::TT_NOT_LOGIN);
                return;
            }

            $server_access_token = TTRedis::getAccessToken($sendor);

            // 检测登录是否失效
            if(empty($server_access_token)) {
                SocketReq::response($client_id, $mr, TTCode::TT_LOGIN_INVALID);
            } else if($server_access_token != $access_token) {
                SocketReq::response($client_id, $mr, TTCode::TT_OTHER_DEVICE_LOGIN);
                return;
            }

            // 绑定用户
            SocketUser::bindUserAndClient($client_id, $sendor);
        }

        // 检测是否需要在群组
        if($msgCode > SocketCode::IN_GROUP_REQ_BEGIN
            && $msgCode < SocketCode::IN_GROUP_REQ_END) {

            $group_id = $bodyObj[SocketConst::GROUP_ID];

            // 检测参数
            if(empty($group_id)) {
                SocketReq::response($client_id, $mr, TTCode::TT_PARA_ERR);
                return;
            }

            // 检测群组用户是否在线
            if(!SocketGroup::isOnline(TTRedis::getClientUser($client_id), $group_id)) {
                SocketReq::response($client_id, $mr, TTCode::TT_GROUP_IS_OUT);
                return;
            }
        }

        // 检测是否需要进入群组
        switch ($msgCode) {

            case SocketCode::USER_REGISTER_REQ: // 注册
                SocketReq::registerReq($client_id, $mr, $bodyObj);
                break;

            case SocketCode::USER_LOGIN_REQ: // 登录
                SocketReq::loginReq($client_id, $mr, $bodyObj);
                break;

            case SocketCode::USER_THIRD_LOGIN_REQ: // 三方登录
                SocketReq::thirdLoginReq($client_id, $mr, $bodyObj);
                break;

            case SocketCode::USER_THIRD_REGISTER_REQ: // 三方注册
                SocketReq::thirdRegisterReq($client_id, $mr, $bodyObj);
                break;

            case SocketCode::USER_RESET_PASSWORD_REQ: // 重置密码
                $result = User::resetPassword($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_LOGOUT_REQ: // 登出
                $result = User::logout($client_id, $bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_UPLOADAVATAR_REQ: // 上传个人头像
                $result = User::uploadAvatar($mr[SocketHead::H2_ID], $bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_GET_INFORMATION_REQ: // 获取个人信息
                $result = User::getInformation($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_MODIFY_INFORMATION_REQ: // 修改个人信息
                $result = User::modifyInformation($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_VERIFY_SMSCODE_REQ: // 检测短信验证码
                $result = User::verifySMSCode($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_CHECK_USER_REQ: // 检测用户
                $result = User::checkUser($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_UPLOAD_DEVICE_TOKEN: // 上传设备Token
                $result = User::uploadDeviceToken($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_THIRD_BINDING: // 绑定三方
                $result = User::thirdBinding($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_THIRD_UNBINDING: // 取消三方绑定
                $result = User::thirdUnbinding($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_FIND_CLUB_REQ: // 查找俱乐部
                $result = User::findClub($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_FIND_USER_REQ: // 查找用户
                $result = User::findUser($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_GET_USERS_REQ: // 获取用户信息
                $result = User::getUsers($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_GET_MOBILE_LIST_NOT_FRIEND_REQ: // 获取未添加好友的手机用户
                $result = User::getMobileListNotFriend($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::USER_EXIST_NICKNAME_REQ:
                $result = User::existNickname($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::FRIEND_ADD_FRIEND_REQ: // 添加好友
                $result = Friend::addFriend($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::FRIEND_DELETE_FRIEND_REQ: // 删除好友
                $result = Friend::deleteFriend($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::FRIEND_GET_FRIEND_LIST_REQ: // 获取好友列表
                $result = Friend::getFriendList($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::FRIEND_GET_INVITE_REQ: // 获取邀请信息
                $result = Friend::getNotificationMessage($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::FRIEND_RESPONSE_INVITE_REQ: // 响应邀请
                $result = Friend::responseFriendInvite($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::FRIEND_REMARK_USER_INFO_REQ: // 备注用户
                $result = Friend::remarkUserInfo($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_CREATE_REQ: // 创建群组
                $result = Group::createGroup($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_DELETE_REQ: // 删除群组
                $result = Group::deleteGroup($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_EXIT_REQ: // 退出群组
                $result = Group::exitGroup($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_MODIFY_REQ: // 修改群组
                $result = Group::modifyGroup($client_id, $bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_ADD_USERS_REQ: // 添加用户到群组
                $result = Group::addUsersToGroup($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_GET_INFORMATION_REQ: // 获取群组信息
                $result = Group::getGroupInformation($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_GET_RIDING_GROUP_REQ: // 获取当前骑行群组信息
                $result = Group::getRidingGroup($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_GET_RIDING_RECORD_REQ: // 获取骑行记录
                $result = Group::getRidingRecord($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_DEL_RIDING_RECORD_REQ: // 获取骑行记录
                $result = Group::delRidingRecord($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_GET_INVITE_REQ: // 获取邀请信息
                $result = Group::getNotificationMessage($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_RESPONSE_INVITE_REQ: // 响应邀请
                $result = Group::responseGroupInvite($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_JOIN_GROUP_REQ: // 输入群组口令加入群
                $result = Group::joinGroup($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_ENTER_REQ: // 进入群组
                SocketReq::enterGroupReq($client_id, $mr, $bodyObj);
                break;

            case SocketCode::GROUP_LEAVE_REQ: // 离开群组
                SocketReq::leaveGroupReq($client_id, $mr, $bodyObj);
                break;

            case SocketCode::GROUP_ACQUIRE_INTERCOM_REQ: // 申请对讲
                SocketReq::acquireIntercomReq($client_id, $mr, $bodyObj);
                break;

            case SocketCode::GROUP_RELEASE_INTERCOM_REQ: // 释放对讲
                SocketReq::releaseIntercomReq($client_id, $mr, $bodyObj);
                break;

            case SocketCode::GROUP_GET_MEMBERS_REQ: //获取群组成员
                $result = Group::getGroupMembers($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_UPLOAD_AVATAR_REQ: // 上传群头像
                $result = Group::uploadAvatar($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_GET_ONLINE_REQ: // 获取群组在线成员
                $result = Group::getGroupOnline($client_id, $mr[SocketHead::H2_ID], $bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_UPLOAD_LOCATION: // 上传最新定位
                $result = Group::uploadLocation($client_id, $mr[SocketHead::H2_ID], $bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_ENTER_REAL_LOCATION: // 进入实时定位
                $result = Group::enterRealLocation($client_id, $mr[SocketHead::H2_ID], $bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_EXIT_REAL_LOCATION: // 退出实时定位
                $result = Group::exitRealLocation($client_id, $mr[SocketHead::H2_ID], $bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_UPLOAD_VOICE_ID: // 上传语音ID
                $result = Group::uploadVoiceId($client_id, $mr[SocketHead::H2_ID], $bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::GROUP_SET_SHARE_LOCATION_REQ:
                $result = Group::setShareLocation($client_id, $mr[SocketHead::H2_ID], $bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::DEVICE_UPLOAD_MAIN: // 上传主设备
                $result = Device::deviceUploadMain($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::DEVICE_UPLOAD_CONTROLER: // 上传控制器
                $result = Device::deviceUploadControler($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::DEVICE_UPLOAD_HEADSET: // 上传耳机
                $result = Device::deviceUploadHeadset($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::DEVICE_GET_MAIN_LIST: // 获取主设备信息列表
                $result = Device::deviceGetMainList($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::BLUE_GROUP_CREATE_REQ: // 创建蓝牙对讲群组
                $result = BlueGroup::create($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::BLUE_GROUP_GET_INVITE_REQ: // 获取蓝牙对讲群组邀请
                $result = BlueGroup::getInvite($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::BLUE_GROUP_SET_INVITE_REQ: // 处理蓝牙对讲群组邀请
                $result = BlueGroup::setInvite($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            case SocketCode::BLUE_GROUP_GET_GROUP_REQ: // 获取当前蓝牙对讲群组
                $result = BlueGroup::getGroup($bodyObj);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                break;

            default:
                $result = TTPublic::getResponse(TTCode::TT_NOT_SUPPORT);
                MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
                PhpLog::Log("Request code ".$msgCode.", not support!");
                break;

        }

    }
}