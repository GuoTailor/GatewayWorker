<?php

namespace model;
use Common\TTCode;
use Common\TTDB;
use Common\TTDBConst;
use Common\TTDbFun;
use Common\TTDBLimit;
use Common\TTRedis;
use Exception;
use Socket\SocketConst;
use Socket\SocketGroup;
use Socket\SocketInd;

use Socket\SocketUser;
use ThirdParty\MySmsCode;

/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/6
 * Time: 下午1:19
 */
class TTUser
{
    public static function register($mobile, $password, $os) {

        // 检测校验码
        if(!TTRedis::getSmsVerify($mobile)) {
            return TTPublic::getResponse(TTCode::TT_VERIFY_FAILED);
        }

        // 检测用户是否存在
        $userInfo = TTDbFun::getInfoByMobile($mobile);
        if($userInfo != null) {
            return TTPublic::getResponse(TTCode::TT_EXIST_USER);
        }

        // 添加用户
        $ret = TTDbFun::addUser($mobile, $password, $os);
        if($ret != TTDBConst::OK) {
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function login($mobile, $password, $access_token, $device_token) {

        // 读取用户信息
        $fullUserInfo = TTDbFun::getFullUserInfoByMobile($mobile);
        if($fullUserInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NOT_USER);
        }

        if(!empty($password)) {

            // 检测密码是否正确
            if($fullUserInfo[TTDB::USER_PASSWORD] != $password) {
                return TTPublic::getResponse(TTCode::TT_PASSWORD_ERR);
            }

            // 执行登陆
            $ret = TTDbFun::updateManualLogin($mobile, "mobile", "0");

            // 检测执行结果
            if($ret != TTDBConst::OK) {
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

        } else if(!empty($access_token)) {

            // 检测之前的登录是否有效
            if($fullUserInfo[TTDB::USER_ACCESS_TOKEN] != $access_token) {
                return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
            }

            // 执行登陆
            $ret = TTDbFun::updateAutoLogin($mobile);

            // 检测执行结果
            if($ret != TTDBConst::OK) {
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

        } else {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        // 获取用户信息
        $userInfo = TTDbFun::getInfoByMobile($mobile, true, true);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NOT_USER);
        }

        $user_id = $userInfo[TTDB::USER_ID];
        $nick_name = $userInfo[TTDB::USER_NICK_NAME];

        TTRedis::setDeviceToken($user_id, $device_token);

        // 通知好友
        SocketInd::userLoginInd($user_id, $nick_name);

        $userInfo = self::addThirdInfo($userInfo, $user_id);

        return TTPublic::getResponse(TTCode::TT_SUCCESS, $userInfo);
    }

    public static function thirdLogin($third_type, $open_id, $nickname, $sex, $headimgurl, $device_token) {

        // 读取三方用户信息
        $thirdInfo = TTDbFun::getThirdInfo($third_type, $open_id);
        if($thirdInfo == null || $thirdInfo[TTDB::STATUS] != TTDBConst::STATUS_NORMAL) {
            return TTPublic::getResponse(TTCode::TT_NOT_USER);
        }

        // 获取绑定的user id
        $user_id = $thirdInfo[TTDB::USER_ID];
        if(empty($user_id)) {
            return TTPublic::getResponse(TTCode::TT_NOT_USER);
        }

        // 读取用户信息
        $mobileInfo = TTDbFun::getMobileById($user_id);
        if($mobileInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NOT_USER);
        }
        $mobile = $mobileInfo[TTDB::USER_MOBILE];

        // 执行登陆
        $ret = TTDbFun::updateManualLogin($mobile, $third_type, $open_id);
        if($ret != TTDBConst::OK) {
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

            // 再次获取最新用户信息
        $userInfo = TTDbFun::getInfoByMobile($mobile, true, true);
        if($userInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NOT_USER);
        }

        // 更新三方信息
        if($thirdInfo[TTDB::THIRD_NICKNAME] != $nickname
            || $thirdInfo[TTDB::THIRD_SEX] != $sex
            || $thirdInfo[TTDB::THIRD_HEADIMGURL] != $headimgurl) {
            TTDbFun::updateThird($user_id, $third_type, $open_id, $nickname, $sex, $headimgurl);
        }

        $user_id = $userInfo[TTDB::USER_ID];
        $nick_name = $userInfo[TTDB::USER_NICK_NAME];

        TTRedis::setDeviceToken($user_id, $device_token);

        // 通知好友
        SocketInd::userLoginInd($user_id, $nick_name);

        $userInfo = self::addThirdInfo($userInfo, $user_id);

        return TTPublic::getResponse(TTCode::TT_SUCCESS, $userInfo);
    }

    public static function thirdRegister($mobile, $verify_code, $third_type,
                                         $open_id, $nickname, $sex, $headimgurl,
                                         $os, $device_token) {

        // 检测校验码
        $status = MySmsCode::verify($mobile, $verify_code);
        if($status != MySmsCode::VERIFY_OK) {
            return array(
                SocketConst::RSP_CODE => TTCode::TT_VERIFY_FAILED,
                SocketConst::RSP_MESSAGE => MySmsCode::getMessage($status));
        }

        TTDbFun::sqlStart();
        try{

            // 检测用户是否存在，如果存在更新access_token,不存在就创建用户
            $userInfo = TTDbFun::getInfoByMobile($mobile);
            if($userInfo == null) {
                // 创建手机用户
                $access_token = TTPublic::makeAccessToken($mobile);
                $ret = TTDbFun::addThirdUser($mobile, $access_token, $os,
                    $third_type, $open_id, $nickname, $sex, $headimgurl);
            } else {
                // 更新access_token
                $ret = TTDbFun::updateManualLogin($mobile, $third_type, $open_id);
            }

            // 检测执行结果
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            // 读取用户Id
            $userInfo = TTDbFun::getInfoByMobile($mobile, true, true);
            if($userInfo == null) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            // 读取user id
            $user_id = $userInfo[TTDB::USER_ID];

            // 设置三方信息
            $ret = TTDbFun::setNewThirdInfo($user_id, $third_type, $open_id, $nickname, $sex, $headimgurl);

            // 检测执行结果
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

        } catch(Exception $ex) {
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        $user_id = $userInfo[TTDB::USER_ID];
        $nick_name = $userInfo[TTDB::USER_NICK_NAME];

        TTRedis::setDeviceToken($user_id, $device_token);

        // 通知好友
        SocketInd::userLoginInd($user_id, $nick_name);

        $userInfo = self::addThirdInfo($userInfo, $user_id);

        return TTPublic::getResponse(TTCode::TT_SUCCESS, $userInfo);
    }

    public static function resetPassword($mobile, $old_password, $password) {

        // 检测用户是否存在
        $passwordInfo = TTDbFun::getPasswordByMobile($mobile);
        if($passwordInfo == null) {
            return TTPublic::getResponse(TTCode::TT_NOT_USER);
        }

        $sys_password = $passwordInfo[TTDB::USER_PASSWORD];

        // 验证是否有修改密码的权限
        if(!empty($old_password)) {
            // 检测密码
            if($sys_password != $old_password) {
                return TTPublic::getResponse(TTCode::TT_PASSWORD_ERR);
            }
        } else {

            // 当设置了密码才需要检测校验码
            if(!empty($sys_password) && !TTRedis::getSmsVerify($mobile)) {
                return TTPublic::getResponse(TTCode::TT_VERIFY_FAILED);
            }
        }

        // 重置密码
        $ret = TTDbFun::updatePassword($mobile, $password);
        if($ret != TTDBConst::OK) {
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function logout($client_id, $access_token) {

        // 检测是否登陆
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 执行登出
        $ret = TTDbFun::clearAccessToken($access_token);
        if($ret != TTDBConst::OK) {
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        $user_id = $myInfo[TTDB::USER_ID];
        $nick_name = $myInfo[TTDB::USER_NICK_NAME];

        // 群组登出
        SocketGroup::logout($client_id, $user_id);

        // 用户登出
        SocketUser::logout($client_id, $user_id);
        TTRedis::setDeviceToken($user_id, null);

        // 通知好友
        SocketInd::userLogoutInd($user_id, $nick_name);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    private static function getThirdNickname($thirdInfo, $thirdType) {

        foreach ($thirdInfo as $item) {
            if($item[TTDB::THIRD_PLATFORM_TYPE] == $thirdType) {
                $nickname = $item[TTDB::THIRD_NICKNAME];
                return empty($nickname) ? $item[TTDB::THIRD_OPENID] : $nickname;
            }
        }

        return "";
    }

    private static function addThirdInfo($myInfo, $user_id) {
        $thirdInfo = TTDbFun::getThirdInfoByUser($user_id);

        $myInfo["third_".TTDBConst::THIRD_QQ] = self::getThirdNickname($thirdInfo, TTDBConst::THIRD_QQ);
        $myInfo["third_".TTDBConst::THIRD_WEIXIN] = self::getThirdNickname($thirdInfo, TTDBConst::THIRD_WEIXIN);
        $myInfo["third_".TTDBConst::THIRD_WEIBO] = self::getThirdNickname($thirdInfo, TTDBConst::THIRD_WEIBO);

        return $myInfo;
    }

    public static function getInformation($access_token) {

        $myInfo = TTDbFun::getInfoByAccessToken($access_token, true, true);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        $myInfo = self::addThirdInfo($myInfo, $myInfo[TTDB::USER_ID]);

        return TTPublic::getResponse(TTCode::TT_SUCCESS, $myInfo);
    }

    public static function modifyInformation($access_token, $information) {

        $jsonInfo = json_decode($information, true);

        // 检测参数
        if(empty($jsonInfo)) {
            return TTPublic::getResponse(TTCode::TT_INVALID_PARAM);
        }

        // 检测参数是否有效
        $nick_name = $jsonInfo[TTDB::USER_NICK_NAME];
        $club_name = $jsonInfo[TTDB::LOCAL_CLUB_NAME];
        $user_signature = $jsonInfo[TTDB::USER_USER_SIGNATURE];

        if((!empty($nick_name) && !TTDBLimit::isValidNickname($nick_name))
            || (!empty($club_name) && !TTDBLimit::isValidClub($club_name))
            || (!empty($user_signature) && !TTDBLimit::isValidSignal($user_signature))) {
            return TTPublic::getResponse(TTCode::TT_INVALID_DATA);
        }

        // 检测是否登陆
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取用户id
        $my_user_id = $myInfo[TTDB::USER_ID];

        // 禁止修改user_id, mobile, access_token
        $jsonInfo = TTPublic::clearArrayKey($jsonInfo,
            [TTDB::USER_ID, TTDB::USER_MOBILE, TTDB::USER_ACCESS_TOKEN]);

        // 检测是否有数据需要修改
        if(TTPublic::getRecordCount(array_keys($jsonInfo)) <= 0) {
            return TTPublic::getResponse(TTCode::TT_DB_NOT_CHANGE);
        }

        TTDbFun::sqlStart();
        try {

            // 检测昵称是否重复
            if(isset($jsonInfo[TTDB::USER_NICK_NAME])) {

                if(empty($jsonInfo[TTDB::USER_NICK_NAME])) {
                    TTDbFun::sqlCancel();
                    return TTPublic::getResponse(TTCode::TT_EMPTY_NICKNAME);
                }

                if(!TTDbFun::existNickName($my_user_id, $jsonInfo[TTDB::USER_NICK_NAME])) {
                    TTDbFun::sqlCancel();
                    return TTPublic::getResponse(TTCode::TT_EXIST_NICKNAME);
                }
            }

            // 添加俱乐部
            if(isset($jsonInfo[TTDB::LOCAL_CLUB_NAME])) {

                $club_name = $jsonInfo[TTDB::LOCAL_CLUB_NAME];
                $club_id = 0;
                if(!empty($club_name)) {

                    // 添加俱乐部
                    $club_id = TTDbFun::addClub($club_name, $my_user_id);

                    // 检测结果
                    if($club_id == null) {
                        TTDbFun::sqlCancel();
                        return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                    }
                }

                unset($jsonInfo[TTDB::LOCAL_CLUB_NAME]);
                $jsonInfo[TTDB::USER_CLUB_ID] = $club_id;
            }

            // 开始更新
            $ret = TTDbFun::updateInfo($access_token, $jsonInfo);
        } catch(Exception $ex) {
            $ret = TTDBConst::FAILED;
        }

        // 检测执行结果
        if($ret != TTDBConst::OK) {
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function verifySMSCode($mobile, $verify_code) {

        // 检测用户是否存在
        $userInfo = TTDbFun::getInfoByMobile($mobile);
        if($userInfo != null) {
            return TTPublic::getResponse(TTCode::TT_EXIST_USER);
        }

        // 检测校验码
        $status = MySmsCode::verify($mobile, $verify_code);
        if($status != MySmsCode::VERIFY_OK) {
            return array(
                SocketConst::RSP_CODE => TTCode::TT_VERIFY_FAILED,
                SocketConst::RSP_MESSAGE => MySmsCode::getMessage($status));
        }

        // 设置短信校验标志
        TTRedis::setSmsVerify($mobile);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function checkUser($mobile, $type) {

        // 获取用户信息
        $userInfo = TTDbFun::getInfoByMobile($mobile);

        if($type == TTDBConst::CHECK_USER_TYPE_REGISTER) {
            if($userInfo != null) {
                return TTPublic::getResponse(TTCode::TT_EXIST_USER);
            }
        } else if($type == TTDBConst::CHECK_USER_TYPE_RESET) {
            if ($userInfo == null) {
                return TTPublic::getResponse(TTCode::TT_NOT_USER);
            }
        } else if($type == TTDBConst::CHECK_USER_TYPE_THIRD_REGISTER) {
            return TTPublic::getResponse(TTCode::TT_SUCCESS);
        } else {
            return TTPublic::getResponse(TTCode::TT_INVALID_PARAM);
        }

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function findClub($access_token, $club_name) {
        // 获取用户信息
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 查找车友
        $clubNameList = TTDbFun::findClub($club_name);

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array("clubs" => $clubNameList));
    }

    public static function findUser($access_token, $user_id, $mobile,
                                    $nick_name, $moto_brand, $moto_model,
                                    $province, $city, $club_name) {
        // 获取用户信息
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        $my_user_id = $myInfo[TTDB::USER_ID];

        // 查找车友
        $userList = TTDbFun::findUser($my_user_id, $user_id, $mobile,
            $nick_name, $moto_brand, $moto_model,
            $province, $city, $club_name);

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array("users" => $userList));
    }

    public static function getUsers($access_token, $users) {
        // 获取用户信息
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        $userList = TTDbFun::getInfosByIds($users);

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array("users" => $userList));
    }

    public static function getMobileListNotFriend($access_token, $mobile_list) {

        // 获取用户信息
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取用户id
        $my_user_id = $myInfo[TTDB::USER_ID];

        // 检测数据是否有效
        if(count($mobile_list) <= 0) {
            return TTPublic::getResponse(TTCode::TT_INVALID_PARAM);
        }

        // 获取全部好友user id
        $friendIdList = TTDbFun::getFriendIdList($my_user_id);

        // 获取未添加好友的手机用户
        $retList = TTDbFun::getMobileListNotFriend($my_user_id, $mobile_list, $friendIdList);

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array("mobiles" => $retList));

    }

    public static function uploadDeviceToken($access_token, $device_token) {
        // 获取用户信息
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取用户id
        $my_user_id = $myInfo[TTDB::USER_ID];
        TTRedis::setDeviceToken($my_user_id, $device_token);

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function thirdBinding($access_token, $third_type, $open_id,
                                        $nickname, $sex, $headimgurl) {
        // 检测是否登陆
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        TTDbFun::sqlStart();
        try{

            // 读取user id
            $user_id = $myInfo[TTDB::USER_ID];

            // 设置三方信息
            $ret = TTDbFun::setNewThirdInfo($user_id, $third_type, $open_id, $nickname, $sex, $headimgurl);

            // 检测执行结果
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

        } catch(Exception $ex) {
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function thirdUnbinding($access_token, $third_type) {

        // 获取用户信息
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        $ret = TTDbFun::removeThird($myInfo[TTDB::USER_ID], $third_type);

        if($ret != TTDBConst::OK) {
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function existNickname($access_token, $nick_name) {

        // 获取用户信息
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        if(!TTDbFun::existNickName($myInfo[TTDB::USER_ID], $nick_name)) {
            return TTPublic::getResponse(TTCode::TT_EXIST_NICKNAME);
        }

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

}