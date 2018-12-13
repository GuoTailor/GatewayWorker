<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2018/3/29
 * Time: 下午6:26
 */

namespace ThirdParty;

use Common\TTRedis;
use Tools\PhpLog;

class MyLocationAddress
{
    public static function getAddress($latitude, $longitude) {

        $location = $latitude.",".$longitude;

        // 从Redis读取地址
        $address = TTRedis::getAddress($location);
        if(!empty($address)) {
            return $address;
        }

        // 从网上获取地址
        $url = "http://restapi.amap.com/v3/geocode/regeo?output=json&location="
            .$longitude.",".$latitude."&key=50d3f5569023ff8ab0f05134fb689869&radius=100&extensions=base";

        PhpLog::Task("getAddress request ".$url);

        $html = file_get_contents($url);

        PhpLog::Task("getAddress response ".$html);

        $addressInfo = json_decode($html, true);
        if(empty($addressInfo) || $addressInfo["status"] != 1) {
            return null;
        }

        $regeocode = $addressInfo["regeocode"];
        if(empty($regeocode)) {
            return null;
        }

        $formatted_address = $regeocode["formatted_address"];
        if(empty($formatted_address)) {
            return null;
        }

        // 保存地址
        TTRedis::setAddress($location, $formatted_address);

        return $formatted_address;
    }

}