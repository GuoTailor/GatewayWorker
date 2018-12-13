<?php

namespace controller;

use Common\TTCode;
use model\TTBlueGroup;
use model\TTPublic;

class BlueGroup
{
    // 1. 创建蓝牙对讲群组
    public static function create($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");

        $unique = TTPublic::getValue($request, "unique");
        $users = TTPublic::getValue($request, "users");

        // 检测参数
        if(empty($access_token) || empty($unique) || empty($users)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTBlueGroup::create($access_token, $unique, $users);
    }

    // 2. 获取蓝牙对讲群组邀请
    public static function getInvite($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $update_time = TTPublic::getValue($request, "update_time");

        // 检测参数
        if(empty($access_token)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTBlueGroup::getInvite($access_token, $update_time);

    }

    // 3. 处理蓝牙对讲群组邀请
    public static function setInvite($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $msg_id = TTPublic::getValue($request, "msg_id");
        $status = TTPublic::getValue($request, "status");

        // 检测参数
        if(empty($access_token) || empty($msg_id) || empty($status)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTBlueGroup::setInvite($access_token, $msg_id, $status);
    }

    // 4. 获取当前蓝牙对讲群组
    public static function getGroup($request) {

        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");

        // 检测参数
        if(empty($access_token)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTBlueGroup::getGroup($access_token);
    }

}
