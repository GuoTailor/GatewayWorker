<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/12/5
 * Time: 下午6:28
 */

namespace Common;

use Tools\PhpLog;

class TTDBLimit
{
    // 手机号
    const MOBILE_LENGTH = 11;

    // 密码
    const PASSWORD_LENGTH = 32;

    // 验证码
    const VERIFY_CODE_LENGTH = 4;

    // 昵称
    const NICKNAME_LENGTH = 10;

    // 备注
    const REMARK_LENGTH = 10;

    // 俱乐部
    const CLUB_LENGTH = 16;

    // 签名
    const SIGNAL_LENGTH = 30;

    // 公告
    const NOTICE_LENGTH = 100;

    // 入群口令
    const ACCESS_CODE_LENGTH = 6;

    private static function length($data) {
        return mb_strlen($data,'utf8');
    }

    /**
     * 检测手机号是否合法
     * @param $data
     * @return bool
     */
    public static function isValidMobile($data) {
        PhpLog::Log($data.":".self::length($data));
        return !empty($data) && self::length($data) == self::MOBILE_LENGTH;
    }

    /**
     * 检测密码是否合法
     * @param $data
     * @return bool
     */
    public static function isValidPassword($data) {
        PhpLog::Log($data.":".self::length($data));
        return !empty($data) && self::length($data) == self::PASSWORD_LENGTH;
    }

    /**
     * 检测短信验证码是否合法
     * @param $data
     * @return bool
     */
    public static function isValidVerifyCode($data) {
        PhpLog::Log($data.":".self::length($data));
        return !empty($data) && self::length($data) == self::VERIFY_CODE_LENGTH;
    }

    /**
     * 检测昵称是否合法
     * @param $data
     * @return bool
     */
    public static function isValidNickname($data) {
        PhpLog::Log($data.":".self::length($data));
        return !empty($data) && self::length($data) <= self::NICKNAME_LENGTH;
    }

    /**
     * 检测备注名称是否合法
     * @param $data
     * @return bool
     */
    public static function isValidRemark($data) {
        PhpLog::Log($data.":".self::length($data));
        return !empty($data) && self::length($data) <= self::REMARK_LENGTH;
    }

    /**
     * 检测俱乐部是否合法
     * @param $data
     * @return bool
     */
    public static function isValidClub($data) {
        PhpLog::Log($data.":".self::length($data));
        return !empty($data) && self::length($data) <= self::CLUB_LENGTH;
    }

    /**
     * 检测签名是否合法
     * @param $data
     * @return bool
     */
    public static function isValidSignal($data) {
        PhpLog::Log($data.":".self::length($data));
        return !empty($data) && self::length($data) <= self::SIGNAL_LENGTH;
    }

    /**
     * 检测公告是否合法
     * @param $data
     * @return bool
     */
    public static function isValidNotice($data) {
        PhpLog::Log($data.":".self::length($data));
        return !empty($data) && self::length($data) <= self::NOTICE_LENGTH;
    }

    /**
     * 检测AccessCode是否合法
     * @param $data
     * @return bool
     */
    public static function isValidAccessCode($data) {
        PhpLog::Log($data.":".self::length($data));
        return !empty($data) && self::length($data) == self::ACCESS_CODE_LENGTH;
    }
}