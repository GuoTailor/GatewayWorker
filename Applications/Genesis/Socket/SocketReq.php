<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/16
 * Time: 下午8:22
 */

namespace Socket;

use Common\TTCode;
use Common\TTDB;
use Common\TTRedis;
use controller\Group;
use controller\User;
use model\TTPublic;
use Tools\MessageTools;
use Tools\PhpLog;

class SocketReq
{
    const USER_ID = "user_id";

    public static function response($client_id, $mr, $code) {
        $result = TTPublic::getResponse($code);
        MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);

        PhpLog::Log("SocketReq response ".json_encode($result));
    }

    private static function socketLogin($client_id, $result) {
        if($result[SocketConst::RSP_CODE] != TTCode::TT_SUCCESS) {
            return $result;
        }

        $data = $result[SocketConst::RSP_DATA];
        if(!empty($data)) {
            $user_id = $data[TTDB::USER_ID];
            $accessToken = $data[TTDB::USER_ACCESS_TOKEN];
            if(!empty($user_id) && !empty($accessToken)) {
                SocketUser::login($client_id, $user_id, $accessToken);
            } else {
                $result = TTPublic::getResponse(TTCode::TT_FAILED);
            }
        } else {
            $result = TTPublic::getResponse(TTCode::TT_FAILED);
        }

        return $result;
    }

    // 注册
    public static function registerReq($client_id, $mr, $bodyObj) {
        $result = User::register($bodyObj);
        MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
    }

    // 登录
    public static function loginReq($client_id, $mr, $bodyObj) {
        $result = User::login($bodyObj);
        $result = self::socketLogin($client_id, $result);
        MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
    }

    // 三方登录
    public static function thirdLoginReq($client_id, $mr, $bodyObj) {
        $result = User::thirdLogin($bodyObj);
        $result = self::socketLogin($client_id, $result);
        MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
    }

    // 三方注册
    public static function thirdRegisterReq($client_id, $mr, $bodyObj) {
        $result = User::thirdRegister($bodyObj);
        $result = self::socketLogin($client_id, $result);
        MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
    }

    // 进入群组
    public static function enterGroupReq($client_id, $mr, $bodyObj) {
        $result = Group::enterGroup($client_id, $mr[SocketHead::H2_ID], $bodyObj);
        MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
    }

    // 离开群组
    public static function leaveGroupReq($client_id, $mr, $bodyObj) {
        // 执行登出
        $result = Group::leaveGroup($mr[SocketHead::H2_ID], $bodyObj);
        if($result[SocketConst::RSP_CODE] == TTCode::TT_SUCCESS) {
            // Socket登出
            if (!SocketGroup::logout($client_id, TTRedis::getClientUser($client_id))) {
                $result = TTPublic::getResponse(TTCode::TT_FAILED);
            }
        }

        MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
    }

    // 请求对讲
    public static function acquireIntercomReq($client_id, $mr, $bodyObj) {

        $result = Group::acquireIntercom($client_id, $mr[SocketHead::H2_ID], $bodyObj);

        MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
    }

    public static function releaseIntercomReq($client_id, $mr, $bodyObj) {

        $result = Group::releaseIntercom($client_id, $mr[SocketHead::H2_ID], $bodyObj);

        MessageTools::sendMessageToClient($client_id, json_encode($result), $mr);
    }

}