<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2018/2/23
 * Time: 下午4:54
 */

namespace model;


use Common\TTProfile;
use Common\TTRedis;
use Tools\PhpLog;

class TTLocation
{
    const FILE_EXT = ".pos";

    // 转换文件名

    /**
     * 获取定位文件名
     * @param $group_id -> 群组ID
     * @param $user_id -> 用户ID
     * @return string -> 定位文件名
     */
    private static function getFile($group_id, $user_id) {

        $index = ceil($group_id / TTProfile::DIR_MAX_NUM);
        $index_path = TTProfile::LOCATION_FILE_PATH.$index;
        // 创建索引目录
        if(!file_exists($index_path)) {
            mkdir($index_path);
        }

        // 创建群组目录
        $group_path = $index_path."/".$group_id;
        if(!file_exists($group_path)) {
            mkdir($group_path);
        }

        // 返回该群组的定位文件
        return $group_path."/".$user_id.self::FILE_EXT;
    }

    public static function getUrl($group_id, $user_id) {
        $index = ceil($group_id / TTProfile::DIR_MAX_NUM);
        return TTProfile::LOCATION_HTTP_PATH.$index."/".$group_id."/".$user_id.self::FILE_EXT;
    }

    /**
     * 保存开始坐标
     * @param $group_id -> 群组ID
     * @param $user_id -> 用户ID
     * @param $location -> 定位坐标
     */
    public static function saveStartLocation($group_id, $user_id, $location) {

        PhpLog::Log("saveLocation ".$group_id.",".$user_id.",".$location);

        $save_data = "0,0;";

        if(!empty($location)) {
            list($latitude, $longitude, $bearing) = explode(",", $location);
            if(TTPublic::isPosValid($latitude, $longitude)) {
                $save_data = $save_data.$latitude.",".$longitude.";";
                TTRedis::setLocation($user_id, $latitude.",".$longitude.",0");
            }
        }

        $full_name = self::getFile($group_id, $user_id);
        file_put_contents($full_name, $save_data, FILE_APPEND);
    }

    /**
     * 保存坐标
     * @param $group_id -> 群组ID
     * @param $user_id -> 用户ID
     * @param $latitude -> 纬度
     * @param $longitude -> 经度
     * @param $max_speed -> 最高速度
     */
    public static function saveLocation($group_id, $user_id, $latitude, $longitude, $max_speed) {

        PhpLog::Log("saveLocation "
            .$group_id.",".$user_id.",".$latitude.",".$longitude.",".$max_speed);

        // 保存最新的最高速度
        if(!empty($max_speed)) {
            $last_max_speed = TTRedis::getMaxSpeed($user_id, $group_id);
            PhpLog::Log("saveLocation max_speed ".$last_max_speed."=>".$max_speed);
            if($max_speed > $last_max_speed) {
                TTRedis::setMaxSpeed($user_id, $group_id, $max_speed);
            }
        }

        // 检测定位是否合法
        if(!TTPublic::isPosValid($latitude, $longitude)) {
            return;
        }

        $new_pos = $latitude.",".$longitude.",0";

        // 检测定位是否发生变化
        $last_pos = TTRedis::getLocation($user_id);
        if($new_pos == $last_pos) {
            return;
        }

        // 保存最新定位到redis
        TTRedis::setLocation($user_id, $new_pos);

        // 保存定位到轨迹文件
        $full_name = self::getFile($group_id, $user_id);
        file_put_contents($full_name, $latitude.",".$longitude.";", FILE_APPEND);
    }

    /**
     * 获取保存的骑行记录
     * @param $group_id
     * @param $user_id
     * @return null
     */
    public static function getSaveLocation($group_id, $user_id) {

        // 获取定位文件数据
        $full_name = self::getFile($group_id, $user_id);
        $allLocation = file_get_contents($full_name);

        return explode(";", $allLocation);
    }

    /**
     * 获取最新定位
     * @param $user_id -> 用户ID
     * @return string -> 最新坐标(角度固定为0)
     */
    public static function getLastLocation($user_id) {
        return TTRedis::getLocation($user_id);
    }
}