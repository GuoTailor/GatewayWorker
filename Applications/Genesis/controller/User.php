<?php

namespace controller;

use Common\TTCode;
use Common\TTDBLimit;
use model\TTAvatar;
use model\TTPublic;
use model\TTUser;
use Socket\SocketConst;
use Tools\PhpLog;

class User
{
    // 1. 注册
    public static function register($request) {
        // 读取参数
        $mobile = TTPublic::getValue($request, "mobile");
        $password = TTPublic::getValue($request, "password");
        $os = TTPublic::getValue($request, "os");

        // 检测参数
        if(empty($mobile) || empty($password) || empty($os)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        // 检测参数是否有效
        if(!TTDBLimit::isValidMobile($mobile) || !TTDBLimit::isValidPassword($password)) {
            return TTPublic::getResponse(TTCode::TT_INVALID_DATA);
        }

        return TTUser::register($mobile, $password, $os);
    }

    // 2. 登陆
    public static function login($request) {

        // 读取参数
        $mobile = TTPublic::getValue($request, "mobile");
        $password = TTPublic::getValue($request, "password");
        $access_token = TTPublic::getValue($request, "access_token");
        $device_token = TTPublic::getValue($request, "device_token");

        // 检测参数
        if(empty($mobile) || (empty($password) && empty($access_token))) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        // 检测参数是否有效
        if(!TTDBLimit::isValidMobile($mobile)
            || (!empty($password) && !TTDBLimit::isValidPassword($password))) {
            return TTPublic::getResponse(TTCode::TT_INVALID_DATA);
        }
        $info_req = TTUser::login($mobile, $password, $access_token, $device_token);
        PhpLog::Log("登录info: ".$info_req);
        return $info_req;
    }

    // 3. 三方登陆
    public static function thirdLogin($request) {

        // 读取参数
        $third_type = TTPublic::getValue($request, "third_type");
        $open_id = TTPublic::getValue($request, "open_id");
        $nickname = TTPublic::getValue($request, "nickname");
        $sex = TTPublic::getValue($request, "sex");
        $headimgurl = TTPublic::getValue($request, "headimgurl");
        $device_token = TTPublic::getValue($request, "device_token");

        // 检测参数
        if(empty($third_type) || empty($open_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTUser::thirdLogin($third_type, $open_id, $nickname, $sex, $headimgurl, $device_token);
    }

    // 4. 三方注册登陆
    public static function thirdRegister($request) {

        // 读取参数
        $mobile = TTPublic::getValue($request, "mobile");
        $verify_code = TTPublic::getValue($request, "verify_code");
        $third_type = TTPublic::getValue($request, "third_type");
        $open_id = TTPublic::getValue($request, "open_id");
        $nickname = TTPublic::getValue($request, "nickname");
        $sex = TTPublic::getValue($request, "sex");
        $headimgurl = TTPublic::getValue($request, "headimgurl");
        $os = TTPublic::getValue($request, "os");
        $device_token = TTPublic::getValue($request, "device_token");

        // 检测参数
        if(empty($mobile) || empty($verify_code) || empty($third_type) || empty($open_id) || empty($os)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        // 检测参数是否有效
        if(!TTDBLimit::isValidMobile($mobile) || !TTDBLimit::isValidVerifyCode($verify_code)) {
            return TTPublic::getResponse(TTCode::TT_INVALID_DATA);
        }

        return TTUser::thirdRegister($mobile, $verify_code, $third_type,
            $open_id, $nickname, $sex, $headimgurl,
            $os, $device_token);
    }

    // 5. 重置密码
    public static function resetPassword($request) {

        // 读取参数
        $mobile = TTPublic::getValue($request, "mobile");
        $old_password = TTPublic::getValue($request, "old_password");
        $password = TTPublic::getValue($request, "password");

        // 检测参数
        if(empty($mobile) || empty($password)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        // 检测参数是否有效
        if(!TTDBLimit::isValidMobile($mobile) || !TTDBLimit::isValidPassword($password)) {
            return TTPublic::getResponse(TTCode::TT_INVALID_DATA);
        }

        return TTUser::resetPassword($mobile, $old_password, $password);
    }

    // 6. 登出
    public static function logout($client_id, $request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");

        // 检测参数
        if(empty($access_token)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTUser::logout($client_id, $access_token);
    }

    // 7. 上传头像
    public static function uploadAvatar($user_id, $request) {
        // 读取参数
        $access_token = TTPublic::getValue($request, SocketConst::ACCESS_TOKEN);
        $file = TTPublic::getValue($request, "file");

        // 检测参数
        if(empty($user_id) || empty($access_token) || empty($file)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        if(!file_exists($file)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTAvatar::uploadUserAvatar($user_id, $access_token, $file);
    }

    // 8. 获取用户信息
    public static function getInformation($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");

        // 检测参数
        if(empty($access_token)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTUser::getInformation($access_token);
    }

    // 9. 修改用户个人信息
    public static function modifyInformation($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $information = TTPublic::getValue($request, "information");

        // 检测参数
        if(empty($access_token) || empty($information)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTUser::modifyInformation($access_token, $information);
    }

    // 10. 短信校验接口
    public static function verifySMSCode($request) {

        // 读取参数
        $mobile = TTPublic::getValue($request, "mobile");
        $verify_code = TTPublic::getValue($request, "verify_code");

        // 检测参数
        if(empty($mobile) || empty($verify_code)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        // 检测参数是否有效
        if(!TTDBLimit::isValidMobile($mobile) || !TTDBLimit::isValidVerifyCode($verify_code)) {
            return TTPublic::getResponse(TTCode::TT_INVALID_DATA);
        }

        return TTUser::verifySMSCode($mobile, $verify_code);
    }

    // 11. 检测手机号是否注册
    public static function checkUser($request) {

        // 读取参数
        $mobile = TTPublic::getValue($request, "mobile");
        $type = TTPublic::getValue($request, "type");

        // 检测参数
        if(empty($mobile)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        // 检测参数是否有效
        if(!TTDBLimit::isValidMobile($mobile)) {
            return TTPublic::getResponse(TTCode::TT_INVALID_DATA);
        }

        return TTUser::checkUser($mobile, $type);
    }

    // 12. 查找俱乐部
    public static function findClub($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $club_name = TTPublic::getValue($request, "club_name");

        // 检测参数
        if(empty($access_token) || empty($club_name)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        // 检测参数是否有效
        if(!TTDBLimit::isValidClub($club_name)) {
            return TTPublic::getResponse(TTCode::TT_INVALID_DATA);
        }

        return TTUser::findClub($access_token, $club_name);
    }

    // 13. 查找用户
    public static function findUser($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $user_id = TTPublic::getValue($request, "user_id");
        $mobile = TTPublic::getValue($request, "mobile");
        $nick_name = TTPublic::getValue($request, "nick_name");
        $moto_brand = TTPublic::getValue($request, "moto_brand");
        $moto_model = TTPublic::getValue($request, "moto_model");
        $province = TTPublic::getValue($request, "province");
        $city = TTPublic::getValue($request, "city");
        $club_name = TTPublic::getValue($request, "club_name");

        // 检测参数
        if(empty($access_token)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        if(!isset($user_id) && !isset($mobile) && !isset($nick_name)
            && !isset($moto_brand) && !isset($moto_model) && !isset($province)
            && !isset($city) && !isset($club_name)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTUser::findUser($access_token, $user_id, $mobile,
            $nick_name, $moto_brand, $moto_model, $province, $city, $club_name);
    }

    // 14. 获取用户信息
    public static function getUsers($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $users = TTPublic::getValue($request, "users");

        // 检测参数
        if(empty($access_token) || empty($users)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTUser::getUsers($access_token, $users);

    }

    // 15. 获取未添加好友的手机号列表
    public static function getMobileListNotFriend($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $mobiles = TTPublic::getValue($request, "mobiles");

        // 检测参数
        if(empty($access_token) || empty($mobiles)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        $mobile_list = json_decode($mobiles, true);

        return TTUser::getMobileListNotFriend($access_token, $mobile_list);
    }

    // 16. 上传设备Token
    public static function uploadDeviceToken($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $device_token = TTPublic::getValue($request, "device_token");

        // 检测参数
        if(empty($access_token) || empty($device_token)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTUser::uploadDeviceToken($access_token, $device_token);
    }

    public static function thirdBinding($request) {
        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $third_type = TTPublic::getValue($request, "third_type");
        $open_id = TTPublic::getValue($request, "open_id");
        $nickname = TTPublic::getValue($request, "nickname");
        $sex = TTPublic::getValue($request, "sex");
        $headimgurl = TTPublic::getValue($request, "headimgurl");

        // 检测参数
        if(empty($access_token) || empty($third_type) || empty($open_id)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTUser::thirdBinding($access_token, $third_type,
            $open_id, $nickname, $sex, $headimgurl);
    }

    public static function thirdUnbinding($request) {
        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $third_type = TTPublic::getValue($request, "third_type");

        // 检测参数
        if(empty($access_token) || empty($third_type)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTUser::thirdUnbinding($access_token, $third_type);
    }

    public static function existNickname($request) {
        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $nick_name = TTPublic::getValue($request, "nick_name");

        // 检测参数
        if(empty($access_token) || empty($nick_name)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        // 检测参数是否有效
        if(!TTDBLimit::isValidNickname($nick_name)) {
            return TTPublic::getResponse(TTCode::TT_INVALID_DATA);
        }

        return TTUser::existNickname($access_token, $nick_name);
    }
}
