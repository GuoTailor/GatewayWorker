<?php

namespace model;
use Common\TTCode;
use Common\TTDB;
use Common\TTDBConst;
use Common\TTDbFun;
use Exception;

/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/6
 * Time: 下午1:19
 */
class TTDevice
{
    public static function deviceUploadMain($access_token, $address, $name, $version, $serial_number, $remark) {
        // 检测是否登陆
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        TTDbFun::sqlStart();
        try{

            // 读取user id
            $user_id = $myInfo[TTDB::USER_ID];

            // 删除用户设备
            $ret = TTDbFun::removeMainDevice($user_id);
            // 检测执行结果
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            // 读取主设备信息
            $deviceInfo = TTDbFun::getMainDeviceInfo($address);

            // 检测该三方账号是否绑定了其他手机,如何有，直接替换三方信息中的用户ID
            if(!empty($deviceInfo)) {

                // 更新主设备信息
                $ret = TTDbFun::setMainDeviceInfo($address, $user_id, $name, $version, $serial_number, $remark);

                // 检测执行结果
                if($ret != TTDBConst::OK) {
                    TTDbFun::sqlCancel();
                    return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                }
            } else {

                // 添加主设备信息
                $ret = TTDbFun::addMainDeviceInfo($address, $user_id, $name, $version, $serial_number, $remark);

                // 检测执行结果
                if($ret != TTDBConst::OK) {
                    TTDbFun::sqlCancel();
                    return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                }
            }
        } catch(Exception $ex) {
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function deviceUploadControler($access_token, $address, $name, $version, $serial_number, $remark) {
        // 检测是否登陆
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        TTDbFun::sqlStart();
        try{

            // 读取user id
            $user_id = $myInfo[TTDB::USER_ID];

            // 删除用户设备
            $ret = TTDbFun::removeControlerDevice($user_id);
            // 检测执行结果
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            // 读取主设备信息
            $deviceInfo = TTDbFun::getControlerDeviceInfo($address);

            // 检测该三方账号是否绑定了其他手机,如何有，直接替换三方信息中的用户ID
            if(!empty($deviceInfo)) {

                // 更新主设备信息
                $ret = TTDbFun::setControlerDeviceInfo($address, $user_id, $name, $version, $serial_number, $remark);

                // 检测执行结果
                if($ret != TTDBConst::OK) {
                    TTDbFun::sqlCancel();
                    return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                }
            } else {

                // 添加主设备信息
                $ret = TTDbFun::addControlerDeviceInfo($address, $user_id, $name, $version, $serial_number, $remark);

                // 检测执行结果
                if($ret != TTDBConst::OK) {
                    TTDbFun::sqlCancel();
                    return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                }
            }
        } catch(Exception $ex) {
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function deviceUploadHeadset($access_token, $address, $name, $version, $serial_number, $remark) {
        // 检测是否登陆
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        TTDbFun::sqlStart();
        try{

            // 读取user id
            $user_id = $myInfo[TTDB::USER_ID];

            // 删除用户设备
            $ret = TTDbFun::removeHeadsetDevice($user_id);
            // 检测执行结果
            if($ret != TTDBConst::OK) {
                TTDbFun::sqlCancel();
                return TTPublic::getResponse(TTCode::TT_DB_FAILED);
            }

            // 读取主设备信息
            $deviceInfo = TTDbFun::getHeadsetDeviceInfo($address, $user_id);

            // 检测该三方账号是否绑定了其他手机,如何有，直接替换三方信息中的用户ID
            if(!empty($deviceInfo)) {

                // 更新主设备信息
                $ret = TTDbFun::setHeadsetDeviceInfo($address, $user_id, $name, $version, $serial_number, $remark);

                // 检测执行结果
                if($ret != TTDBConst::OK) {
                    TTDbFun::sqlCancel();
                    return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                }
            } else {

                // 添加主设备信息
                $ret = TTDbFun::addHeadsetDeviceInfo($address, $user_id, $name, $version, $serial_number, $remark);

                // 检测执行结果
                if($ret != TTDBConst::OK) {
                    TTDbFun::sqlCancel();
                    return TTPublic::getResponse(TTCode::TT_DB_FAILED);
                }
            }
        } catch(Exception $ex) {
            TTDbFun::sqlCancel();
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        TTDbFun::sqlOk();

        return TTPublic::getResponse(TTCode::TT_SUCCESS);
    }

    public static function deviceGetMainList($access_token, $address_json) {

        // 检测是否登陆
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        $address_list = json_decode($address_json);
        // 检测参数
        if(empty($address_list)) {
            return TTPublic::getResponse(TTCode::TT_INVALID_PARAM);
        }

        $deviceInfoList = TTDbFun::getMainDeviceList($address_list);

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array("deviceList" => $deviceInfoList));
    }

}