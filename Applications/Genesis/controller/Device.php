<?php

namespace controller;

use Common\TTCode;
use model\TTDevice;
use model\TTPublic;
use Tools\PhpLog;

class Device
{
    public static function deviceUploadMain($request) {
        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $address = TTPublic::getValue($request, "address");
        $name = TTPublic::getValue($request, "name");
        $version = TTPublic::getValue($request, "version");
        $serial_number = TTPublic::getValue($request, "serial_number");
        $remark = TTPublic::getValue($request, "remark");

        // 检测参数
        if(empty($access_token) || empty($address) || empty($name) || empty($version) || empty($serial_number)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTDevice::deviceUploadMain($access_token, $address, $name, $version, $serial_number, $remark);
    }

    public static function deviceUploadControler($request) {
        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $address = TTPublic::getValue($request, "address");
        $name = TTPublic::getValue($request, "name");
        $version = TTPublic::getValue($request, "version");
        $serial_number = TTPublic::getValue($request, "serial_number");
        $remark = TTPublic::getValue($request, "remark");

        // 检测参数
        if(empty($access_token) || empty($address) || empty($name) || empty($version)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTDevice::deviceUploadControler($access_token, $address, $name, $version, $serial_number, $remark);
    }

    public static function deviceUploadHeadset($request) {
        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $address = TTPublic::getValue($request, "address");
        $name = TTPublic::getValue($request, "name");
        $version = TTPublic::getValue($request, "version");
        $serial_number = TTPublic::getValue($request, "serial_number");
        $remark = TTPublic::getValue($request, "remark");

        // 检测参数
        if(empty($access_token) || empty($address) || empty($name)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }
    
        return TTDevice::deviceUploadHeadset($access_token, $address, $name, $version, $serial_number, $remark);
    }

    public static function deviceGetMainList($request) {
        // 读取参数
        $access_token = TTPublic::getValue($request, "access_token");
        $address_json = TTPublic::getValue($request, "address_json");

        // 检测参数
        if(empty($access_token) || empty($address_json)) {
            return TTPublic::getResponse(TTCode::TT_PARA_ERR);
        }

        return TTDevice::deviceGetMainList($access_token, $address_json);
    }
}
