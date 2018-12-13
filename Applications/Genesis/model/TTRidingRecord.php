<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2018/3/28
 * Time: 上午8:23
 */

namespace model;


use Common\TTDBConst;
use Common\TTDbFun;
use Common\TTRedis;
use ThirdParty\MyLocationAddress;
use Tools\PhpLog;

class TTRidingRecord
{

    /**
     * 开始骑行
     * @param $user_id
     * @param $group_id
     * @param $group_type
     * @return int|mixed
     */
    public static function begin($user_id, $group_id, $group_type) {
        return TTDbFun::startRidingRecord($user_id, $group_id, $group_type);
    }

    /**
     * 进入骑行 设置进入时间，状态
     * @param $user_id
     * @param $group_id
     */

    public static function enter($user_id, $group_id) {
        self::leave($user_id, $group_id);
        TTRedis::setEnterTime($user_id, $group_id, TTPublic::getDateTime());
    }

    /**
     * 离开骑行 统计骑行时长，清除进入时间，设置状态
     * @param $user_id
     * @param $group_id
     */
    public static function leave($user_id, $group_id) {

        // 获取进入群组的时间
        $enter_time = TTRedis::getEnterTime($user_id, $group_id);
        if(empty($enter_time)) {
            return;
        }

        // 累计总骑行时间
        $total_time = TTRedis::getTotalTime($user_id, $group_id);
        $riding_time = TTPublic::getInstanceTime($enter_time,
            TTPublic::getDateTime());
        if(empty($total_time)) {
            $total_time = $riding_time;
        } else {
            $total_time += $riding_time;
        }

        // 设置总时间
        TTRedis::setTotalTime($user_id, $group_id, $total_time);

        // 清除进入骑行时间
        TTRedis::setEnterTime($user_id, $group_id, null);
    }

    /**
     * 删除骑行记录
     * @param $user_id
     * @param $group_id
     * @return int|mixed
     */
    public static function delete($user_id, $group_id) {
        $ret = TTDbFun::deleteRidingRecord($user_id, $group_id);
        if($ret == TTDBConst::OK) {
            self::clearRidingRedis($user_id, $group_id);
        }

        return $ret;
    }

    /**
     * 获取第一个有效定位点
     * @param $locations
     * @return string
     */
    public static function getStartLocation($locations) {

        $locations_count = count($locations);

        if($locations_count > 0) {
            for($i = 0; $i < $locations_count; $i++) {

                if(empty($locations[$i])) {
                    continue;
                }

                list($latitude, $longitude) = explode(",", $locations[$i]);
                if(TTPublic::isPosValid($latitude, $longitude)) {
                    return $latitude.",".$longitude;
                }
            }
        }

        return "0,0";
    }

    /**
     * 获取最后一个有效定位点
     * @param $locations
     * @return string
     */
    public static function getEndLocation($locations) {

        $locations_count = count($locations);

        if($locations_count > 0) {
            for ($i = $locations_count - 1; $i >= 0; $i--) {

                if(empty($locations[$i])) {
                    continue;
                }

                list($latitude, $longitude) = explode(",", $locations[$i]);
                if (TTPublic::isPosValid($latitude, $longitude)) {
                    return $latitude . "," . $longitude;
                }
            }
        }

        return "0,0";
    }

    public static function getLocationInstance($locations) {

        $locations_count = count($locations);

        if($locations_count <= 0) {
            return 0;
        }

        $total_instance = 0;

        $last_lat = 0;
        $last_lnt = 0;
        for ($i = 0; $i < $locations_count; $i++) {

            if(empty($locations[$i])) {
                continue;
            }

            list($latitude, $longitude) = explode(",", $locations[$i]);

            // 检测是否为单次骑行结束点
            if (!TTPublic::isPosValid($latitude, $longitude)) {
                $last_lat = 0;
                $last_lnt = 0;
                continue;
            }

            // 如果是第一个点，先设置
            if(!TTPublic::isPosValid($last_lat, $last_lnt)) {
                $last_lat = $latitude;
                $last_lnt = $longitude;
                continue;
            }

            // 计算距离
            $total_instance += TTPublic::distance($last_lat, $last_lnt, $latitude, $longitude);

            // 保存最近的点
            $last_lat = $latitude;
            $last_lnt = $longitude;
        }

        return $total_instance;
    }

    /**
     * 结束骑行 - 通知骑行地址和距离
     * @param $user_id
     * @param $group_id
     * @return bool
     */
    public static function endLocation($user_id, $group_id) {

        $locations = TTLocation::getSaveLocation($group_id, $user_id);

        $start_location = self::getStartLocation($locations);
        list($start_lat, $start_lng) = explode(",", $start_location);

        $end_location = self::getEndLocation($locations);
        list($end_lat, $end_lng) = explode(",", $end_location);

        // 转换定位信息
        if(empty($start_lat) || empty($start_lng) || empty($end_lat) || empty($end_lng)) {
            return false;
        }

        // 获取里程
        $instance = self::getLocationInstance($locations);

        // 获取起始地址
        $start_addr = MyLocationAddress::getAddress($start_lat, $start_lng);

        // 获取结束地址
        $end_addr = MyLocationAddress::getAddress($end_lat, $end_lng);

        // 定位转地址，并同步到数据库
        $ret = TTDbFun::finishRidingRecord($user_id, $group_id, $start_lat, $start_lng, $start_addr,
            $end_lat, $end_lng, $end_addr, $instance);

        return ($ret == TTDBConst::OK);
    }

    /**
     * 结束骑行
     * @param $user_id
     * @param $group_id
     * @param $group_avatar
     * @param $member_count
     * @return int|mixed
     */
    public static function end($user_id, $group_id, $group_avatar, $member_count) {

        // 离开骑行，统计时长
        self::leave($user_id, $group_id);

        $ret = TTDbFun::endRidingRecord($user_id, $group_id, $group_avatar,
            TTRedis::getMaxSpeed($user_id, $group_id),
            TTRedis::getTotalTime($user_id, $group_id),
            $member_count);

        PhpLog::Task("endRidingRecord ret=".$ret);

        if($ret == TTDBConst::OK) {
            self::clearRidingRedis($user_id, $group_id);

            PhpLog::Task("pushRidingRecord ".$group_id.",".$user_id);
            TTRedis::pushRidingRecord($group_id.",".$user_id);
        }

        return $ret;
    }

    private static function clearRidingRedis($user_id, $group_id) {
        TTRedis::setEnterTime($user_id, $group_id, null);
        TTRedis::setTotalTime($user_id, $group_id, null);
        TTRedis::setMaxSpeed($user_id, $group_id, null);
        TTRedis::setUserVoice($user_id, $group_id, null);
    }
}