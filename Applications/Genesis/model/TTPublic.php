<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/8
 * Time: 上午9:45
 */

namespace model;

use Common\TTCode;
use Common\TTDB;
use Common\TTDBConst;
use Common\TTDBLimit;
use Common\TTProfile;
use Socket\SocketConst;
use Tools\PhpLog;

class TTPublic
{
    public static function getValue($data, $key) {

        if(!empty($data) && isset($data[$key])) {
            return $data[$key];
        }

        return null;
    }

    public static function makeAccessToken($mobile) {
        return md5(time().$mobile);
    }

    public static function makeTempFile($user_id) {
        return md5(time().$user_id);
    }

    public static function getRecordCount($result) {

        if(!isset($result)) {
            return 0;
        }

        return count($result);
    }

    public static function distance($lat1, $lon1, $lat2, $lon2, $radius = 6378.137)
    {
        $rad = doubleval(M_PI / 180.0);

        $lat1 = doubleval($lat1) * $rad;
        $lon1 = doubleval($lon1) * $rad;
        $lat2 = doubleval($lat2) * $rad;
        $lon2 = doubleval($lon2) * $rad;

        if(empty($lat1) || empty($lon1) || empty($lat2) || empty($lon2)) {
            return 0;
        }

        $theta = $lon2 - $lon1;

        $dist = acos(sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($theta));

        if ($dist < 0 ) {
            $dist += M_PI;
        }

        return ($dist * $radius * 1000);
    }

    public static function getResponse($code, $data = null) {

        $ret = array(
            SocketConst::RSP_CODE => $code,
            SocketConst::RSP_MESSAGE => TTCode::getReason($code)
        );

        if(!empty($data)) {
            $ret[ SocketConst::RSP_DATA] = $data;
        }

        return $ret;

//        return json_encode($ret);
    }

    public static function getAvatarUrl($file) {
        // 替换本地目录为HTTP目录

        PhpLog::Log("getAvatarUrl ".$file);

        return str_replace(TTProfile::AVATAR_FILE_PATH, TTProfile::AVATAR_HTTP_PATH, $file);
    }

    public static function getAvatarFile($url) {
        // 替换HTTP目录为本地目录
        return str_replace(TTProfile::AVATAR_HTTP_PATH, TTProfile::AVATAR_FILE_PATH, $url);
    }

    public static function getDateTime() {
        return date("Y-m-d H:i:s");
    }

    public static function getInstanceTime($start_date, $end_date) {
        return floor((strtotime($end_date)-strtotime($start_date)));
    }

    public static function getDoubleTime() {
        list($u_sec, $sec) = explode(" ", microtime());
        return ((double)$u_sec + (double)$sec);
    }

    public static function getTime() {
        return time();
    }

    public static function clearArrayKey($array, $keyList) {

        foreach ($keyList as $keyItem) {
            if(isset($array[$keyItem])) {
                unset($array[$keyItem]);
            }
        }

        return $array;
    }

    public static function isDataValid($userInfo) {

        if(isset($userInfo[TTDB::USER_PASSWORD])) {
            $passwordLen = strlen($userInfo[TTDB::USER_PASSWORD]);
            if($passwordLen != TTDBLimit::PASSWORD_LENGTH) {
                return false;
            }
        }

        if(isset($userInfo[TTDB::USER_MOBILE])) {
            $passwordLen = strlen($userInfo[TTDB::USER_MOBILE]);
            if($passwordLen != TTDBLimit::MOBILE_LENGTH) {
                return false;
            }
        }

        return true;
    }

    public static function getGroupMemberLimit($group_type) {

        $numberLimit = 0;

        switch ($group_type) {
            case TTDBConst::GROUP_TYPE_FRIEND:
                $numberLimit = TTDBConst::GROUP_FRIEND_LIMIT;
                break;

            case TTDBConst::GROUP_TYPE_CLUB:
                $numberLimit = TTDBConst::GROUP_CLUB_LIMIT;
                break;

            case TTDBConst::GROUP_TYPE_CRUISE:
                $numberLimit = TTDBConst::GROUP_CRUISE_LIMIT;
                break;

            case TTDBConst::GROUP_TYPE_INTERCOM:
                $numberLimit = TTDBConst::GROUP_INTERCOM_LIMIT;
                break;

            default:
                break;
        }

        return $numberLimit;
    }

    public static function isPosValid($latitude, $longitude) {
        return !empty(doubleval($latitude)) && !empty(doubleval($longitude));
    }

}