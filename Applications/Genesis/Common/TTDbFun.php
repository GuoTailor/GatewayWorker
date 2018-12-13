<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/30
 * Time: 上午9:00
 */

namespace Common;

use Exception;
use model\TTLocation;
use model\TTPublic;
use Tools\Db;
use Tools\PhpLog;

class TTDbFun
{
    public static function sqlStart() {
        // 启动事务
        Db::inst()->beginTrans();
    }

    public static function sqlCancel() {
        // 事务回滚
        Db::inst()->rollBackTrans();
    }

    public static function sqlOk() {
        // 执行事务
        Db::inst()->commitTrans();
    }

    private static function debugSql($result, $line) {
        PhpLog::Sql("[sql request $line] ".Db::inst()->lastSQL());
        PhpLog::Sql("[sql response $line] ".json_encode($result));
    }

    private static function linkWhere($where1, $link, $where2) {

        if(empty($where1)) {
            return $where2;
        }

        return "($where1 $link $where2)";
    }

    private static function where($key, $value, $condition="=") {
        return "$key $condition '$value'";
    }

    private static function whereLike($key, $value) {
        return "$key like '%$value%'";
    }

    private static function getSqlIn($values) {

        $sql = null;

        foreach ($values as $item) {
            if(empty($sql)) {
                $sql = "'".$item."'";
            } else {
                $sql = $sql.",'".$item."'";
            }
        }

        return $sql;
    }

    private static function whereIn($key, $values) {
        $inValue = self::getSqlIn($values);
        return "$key in ($inValue)";
    }

    private static function whereNotIn($key, $values) {
        $inValue = self::getSqlIn($values);
        return "$key not in ($inValue)";
    }

    private static function cols($colsList) {

        $ret = array();

        foreach ($colsList as $key => $val) {
            if(isset($val)) {
                $ret[$key] = $val;
            }
        }

        PhpLog::Sql("cols1".json_encode($colsList));
        PhpLog::Sql("cols2".json_encode($ret));

        return $ret;
    }

    //  获取好友信息的字段信息
    private static function _get_user_info_field($include_access_token, $include_mobile = false, $table = "") {

        $field = $table.TTDB::USER_ID
            .','.$table.TTDB::USER_NICK_NAME
            .','.$table.TTDB::USER_AVATAR_URL
            .','.$table.TTDB::USER_SEX
            .','.$table.TTDB::USER_MOTO_BRAND
            .','.$table.TTDB::USER_MOTO_MODEL
            .','.$table.TTDB::USER_PROVINCE
            .','.$table.TTDB::USER_CITY
            .','.$table.TTDB::USER_USER_SIGNATURE
            .','.$table.TTDB::USER_TOTAL_MILEAGE
            .','.$table.TTDB::USER_LAST_LOGIN_TIME
            .','.'length('.TTDB::USER_PASSWORD.') as passflag'
            .','.'date_format('.$table.TTDB::USER_BIRTHDAY.',"%Y-%m-%d") as '.TTDB::USER_BIRTHDAY
            .','.$table.TTDB::UPDATE_TIME
            .','.$table.TTDB::CREATE_TIME;

        if($include_access_token) {
            $field = $field.','.$table.TTDB::USER_ACCESS_TOKEN;
        }

        if($include_mobile) {
            $field = $field.','.$table.TTDB::USER_MOBILE;
        }

        return $field;
    }

    private static function _get_member_info_field($table = "") {

        $field = $table.TTDB::USER_ID
            .','.$table.TTDB::USER_NICK_NAME
            .','.$table.TTDB::USER_AVATAR_URL
            .','.$table.TTDB::USER_SEX
            .','.$table.TTDB::USER_MOTO_BRAND
            .','.$table.TTDB::USER_MOTO_MODEL
            .','.$table.TTDB::USER_PROVINCE
            .','.$table.TTDB::USER_CITY
            .','.$table.TTDB::USER_USER_SIGNATURE
            .','.$table.TTDB::USER_TOTAL_MILEAGE
            .','.$table.TTDB::USER_LAST_LOGIN_TIME
            .','.'date_format('.$table.TTDB::USER_BIRTHDAY.',"%Y-%m-%d") as '.TTDB::USER_BIRTHDAY
            .','.$table.TTDB::CREATE_TIME;

        return $field;
    }

    private static function _get_invite_member_info_field($table = "") {

        $field = $table.TTDB::USER_ID
            .','.$table.TTDB::USER_MOBILE
            .','.$table.TTDB::USER_NICK_NAME
            .','.$table.TTDB::USER_AVATAR_URL
            .','.$table.TTDB::USER_SEX
            .','.$table.TTDB::USER_MOTO_BRAND
            .','.$table.TTDB::USER_MOTO_MODEL
            .','.$table.TTDB::USER_PROVINCE
            .','.$table.TTDB::USER_CITY
            .','.$table.TTDB::USER_USER_SIGNATURE
            .','.$table.TTDB::USER_TOTAL_MILEAGE
            .','.$table.TTDB::USER_LAST_LOGIN_TIME
            .','.'date_format('.$table.TTDB::USER_BIRTHDAY.',"%Y-%m-%d") as '.TTDB::USER_BIRTHDAY;

        return $field;
    }

    private static function _get_invite_field($type, $table = "") {

        $field = $table.TTDB::NOTIFY_ID.' as '.TTDB::LOCAL_MSG_ID
            .','.$table.TTDB::NOTIFY_SENDER_ID
            .','.$table.TTDB::STATUS
            .','.$table.TTDB::CREATE_TIME;

        if($type == TTDBConst::NOTIFICATION_ADD_FRIEND) {
            $field = $field.','.$table.TTDB::NOTIFY_ADD_TYPE; // 好友添加方式
        } else if($type == TTDBConst::NOTIFICATION_JOIN_GROUP) {
            $field = $field.','.$table.TTDB::GROUP_ID; // 邀请加入群组的群组ID
        }

        return $field;
    }

    // 获取群组信息字段
    private static function _get_group_info_field($table = "") {
        $strField = $table.TTDB::GROUP_ID
            .','.$table.TTDB::GROUP_GROUP_NAME
            .','.$table.TTDB::GROUP_AVATAR
            .','.$table.TTDB::GROUP_GROUP_TYPE
            .','.$table.TTDB::GROUP_MASTER
            .','.$table.TTDB::GROUP_NOTICE
            .','.$table.TTDB::GROUP_RIDING_STATUS
            .','.$table.TTDB::GROUP_RIDING_START_TIME
            .','.$table.TTDB::GROUP_RIDING_END_TIME
            .','.$table.TTDB::GROUP_GROUP_STATUS
            .','.$table.TTDB::GROUP_LAST_ACTIVE_TIME
            .','.$table.TTDB::GROUP_CREATE_LNG
            .','.$table.TTDB::GROUP_CREATE_LAT
            .','.$table.TTDB::GROUP_ACCESS_CODE
            .','.$table.TTDB::GROUP_LEADER
            .','.$table.TTDB::GROUP_RIDER1
            .','.$table.TTDB::GROUP_RIDER2
            .','.$table.TTDB::GROUP_RIDER3
            .','.$table.TTDB::GROUP_ENDING
            .','.$table.TTDB::UPDATE_TIME
            .','.$table.TTDB::CREATE_TIME;

        return $strField;
    }

    public static function getUserIdList($group_id) {
        $ret = Db::inst()->select(TTDB::USER_ID)
            ->from(TTDB::TABLE_GR)
            ->where(self::where(TTDB::GROUP_ID, $group_id))
            ->where(self::where(TTDB::STATUS, TTDBConst::STATUS_NORMAL))
            ->column();

        self::debugSql($ret, __LINE__);

        return $ret;
    }

    /**
     * 通过手机号，密码创建用户
     * @param $mobile
     * @param $password
     * @param $os
     * @return int|mixed
     */
    public static function addUser($mobile, $password, $os) {

        $ret = TTDBConst::FAILED;

        if(!TTPublic::isDataValid([TTDB::USER_MOBILE => $mobile,
            TTDB::USER_PASSWORD => $password])) {
            return TTCode::TT_INVALID_DATA;
        }

        try {
            $ret = Db::inst()->insert(TTDB::TABLE_USER)
                ->cols(self::cols([
                    TTDB::USER_MOBILE => $mobile,
                    TTDB::USER_PASSWORD => $password,
                    TTDB::USER_PLATFORM => $os,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => TTPublic::getDateTime()
                ]))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
        }

        return $ret;
    }

    /**
     * 通过三方注册创建用户
     * @param $mobile
     * @param $access_token
     * @param $os
     * @param $third_type
     * @param $open_id
     * @return int|mixed
     */
    public static function addThirdUser($mobile, $access_token, $os, $third_type,
                                        $open_id, $nickname, $sex, $headimgurl) {

        $ret = TTDBConst::FAILED;

        if(!TTPublic::isDataValid([TTDB::USER_MOBILE => $mobile])) {
            return TTCode::TT_INVALID_DATA;
        }

        $new_sex = TTDBConst::SEX_SECRET;
        if($sex == 'm') {
            $new_sex = TTDBConst::SEX_MAN;
        } else if($sex == 'f') {
            $new_sex = TTDBConst::SEX_MALE;
        }

        try {
            $ret = Db::inst()->insert(TTDB::TABLE_USER)
                ->cols(self::cols([
                    TTDB::USER_MOBILE => $mobile,
                    TTDB::USER_ACCESS_TOKEN => $access_token,
                    TTDB::USER_PLATFORM => $os,
                    TTDB::USER_LAST_LOGIN_TYPE => $third_type,
                    TTDB::USER_LAST_LOGIN_THIRD_OPEN_ID => $open_id,
                    TTDB::USER_NICK_NAME => $nickname,
                    TTDB::USER_SEX => $new_sex,
                    TTDB::USER_AVATAR_URL => $headimgurl,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => TTPublic::getDateTime()
                ]))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
        }

        return $ret;
    }

    public static function updateInfo($access_token, $jsonInfo) {

        if(!TTPublic::isDataValid($jsonInfo)) {
            return TTCode::TT_INVALID_DATA;
        }

        $jsonInfo[TTDB::UPDATE_TIME] = TTPublic::getDoubleTime();

        try {
            $ret = Db::inst()->update(TTDB::TABLE_USER)
                ->cols(self::cols($jsonInfo))
                ->where(self::where(TTDB::USER_ACCESS_TOKEN, $access_token))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[" . __LINE__ . "]" . $ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function updatePassword($mobile, $password) {

        if(!TTPublic::isDataValid([TTDB::USER_PASSWORD => $password])) {
            return TTDBConst::FAILED;
        }

        try {
            // 不改变update_time时间戳
            $ret = Db::inst()->update(TTDB::TABLE_USER)
                ->cols(self::cols([
                    TTDB::USER_PASSWORD => $password
                ]))
                ->where(self::where(TTDB::USER_MOBILE, $mobile))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = TTDBConst::OK;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    /**
     * 自动登录，更新登录时间
     * @param $mobile
     * @return int|mixed
     */
    public static function updateAutoLogin($mobile) {

        try {

            // 不改变update_time时间戳
            $ret = Db::inst()->update(TTDB::TABLE_USER)
                ->cols(self::cols([
                    TTDB::USER_LAST_LOGIN_TIME => TTPublic::getDateTime()]))
                ->where(self::where(TTDB::USER_MOBILE, $mobile))
                ->query();

            self::debugSql($ret, __LINE__);

            PhpLog::Log(Db::inst()->lastSQL());

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    /**
     * 手动登录系统，更新登录信息
     * @param $mobile
     * @param $login_type
     * @param $third_open_id
     * @return int|mixed
     */
    public static function updateManualLogin($mobile, $login_type, $third_open_id) {

        try {

            // 不改变update_time时间戳
            $ret = Db::inst()->update(TTDB::TABLE_USER)
                ->cols(self::cols([
                    TTDB::USER_ACCESS_TOKEN => TTPublic::makeAccessToken($mobile),
                    TTDB::USER_LAST_LOGIN_TIME => TTPublic::getDateTime(),
                    TTDB::USER_LAST_LOGIN_TYPE => $login_type,
                    TTDB::USER_LAST_LOGIN_THIRD_OPEN_ID => $third_open_id]))
                ->where(self::where(TTDB::USER_MOBILE, $mobile))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    // 清除access_token
    public static function clearAccessToken($access_token) {

        try {
            // 不改变update_time时间戳
            $ret = Db::inst()->update(TTDB::TABLE_USER)
                ->cols(self::cols([
                    TTDB::USER_ACCESS_TOKEN => ""
                ]))
                ->where(self::where(TTDB::USER_ACCESS_TOKEN, $access_token))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function uploadUserAvatar($access_token, $avatarUrl) {

        try {
            $ret = Db::inst()->update(TTDB::TABLE_USER)
                ->cols(self::cols([
                    TTDB::USER_AVATAR_URL => $avatarUrl,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()
                ]))
                ->where(self::where(TTDB::USER_ACCESS_TOKEN, $access_token))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function uploadGroupAvatar($group_id, $user_id, $avatarUrl) {

        try {
            $ret = Db::inst()->update(TTDB::TABLE_GROUP)
                ->cols(self::cols([
                    TTDB::GROUP_AVATAR => $avatarUrl,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()
                ]))
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->where(self::where(TTDB::GROUP_MASTER, $user_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getMobileById($user_id) {
        $mobileList = Db::inst()->select(TTDB::USER_MOBILE)
            ->from(TTDB::TABLE_USER)
            ->where(self::where(TTDB::USER_ID, $user_id))
            ->query();

        self::debugSql($mobileList, __LINE__);

        if(TTPublic::getRecordCount($mobileList) <= 0) {
            return null;
        }

        return $mobileList[0];
    }

    public static function getFullUserInfoByMobile($mobile) {

        $userInfo = Db::inst()->select()
            ->from(TTDB::TABLE_USER)
            ->where(self::where(TTDB::USER_MOBILE, $mobile))
            ->query();

        self::debugSql($userInfo, __LINE__);

        if(TTPublic::getRecordCount($userInfo) <= 0) {
            return null;
        }

        return $userInfo[0];
    }

    public static function getInfoByMobile($mobile,
                                           $include_access_token = false,
                                           $include_club_name = false) {

        if($include_club_name) {

            $userInfoField = self::_get_user_info_field($include_access_token,
                true, "a.");

            $sql = 'select '.$userInfoField
                .self::getDeviceClubField()
                .' from tt_user as a'
                .self::getDeviceClubLeftJoin("a")
                .' where '.self::where("a.".TTDB::USER_MOBILE, $mobile);

            $userInfo = Db::inst()->query($sql);

            self::debugSql($userInfo, __LINE__);

        } else {
            $userInfoField = self::_get_user_info_field($include_access_token);

            $userInfo = Db::inst()->select($userInfoField)
                ->from(TTDB::TABLE_USER)
                ->where(self::where(TTDB::USER_MOBILE, $mobile))
                ->query();

            self::debugSql($userInfo, __LINE__);
        }

        if(TTPublic::getRecordCount($userInfo) <= 0) {
            return null;
        }

        return $userInfo[0];
    }

    public static function getInfoById($user_id,
                                       $include_access_token = false,
                                       $include_club_name = false) {
        if($include_club_name) {
            $userInfoField = self::_get_user_info_field($include_access_token,
                false, "a.");

            $sql = 'select '.$userInfoField
                .self::getDeviceClubField()
                .' from tt_user as a'
                .self::getDeviceClubLeftJoin("a")
                .' where '.self::where("a.".TTDB::USER_ID, $user_id);

            $userInfo = Db::inst()->query($sql);

            self::debugSql($userInfo, __LINE__);
        } else {
            $userInfoField = self::_get_user_info_field($include_access_token);

            $userInfo = Db::inst()->select($userInfoField)
                ->from(TTDB::TABLE_USER)
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->query();

            self::debugSql($userInfo, __LINE__);
        }

        if(TTPublic::getRecordCount($userInfo) <= 0) {
            return null;
        }

        return $userInfo[0];
    }

    public static function getInfosByIds($users) {

        // 转换要查询的用户id列表[1,2]
        $user_list = json_decode($users, true);
        if(TTPublic::getRecordCount($user_list) <= 0) {
            return null;
        }

        $userInfoField = self::_get_user_info_field(false,
            false, "a.");

        $sql = 'select '.$userInfoField
            .self::getDeviceClubField()
            .' from tt_user as a'
            .self::getDeviceClubLeftJoin("a")
            .' where '.self::whereIn("a.".TTDB::USER_ID, $user_list);

        $userInfo = Db::inst()->query($sql);

        self::debugSql($userInfo, __LINE__);

        return $userInfo;
    }

    public static function getInfoByAccessToken($access_token,
                                                $include_access_token = false,
                                                $include_club_name = false) {

        if($include_club_name) {
            $userInfoField = self::_get_user_info_field($include_access_token,
                false, "a.");

            $sql = 'select '.$userInfoField
                .self::getDeviceClubField()
                .' from tt_user as a'
                .self::getDeviceClubLeftJoin("a")
                .' where '.self::where("a.".TTDB::USER_ACCESS_TOKEN, $access_token);

            $userInfo = Db::inst()->query($sql);

            self::debugSql($userInfo, __LINE__);
        } else {
            $userInfoField = self::_get_user_info_field($include_access_token);

            $userInfo = Db::inst()->select($userInfoField)
                ->from(TTDB::TABLE_USER)
                ->where(self::where(TTDB::USER_ACCESS_TOKEN, $access_token))
                ->query();

            self::debugSql($userInfo, __LINE__);
        }

        if(TTPublic::getRecordCount($userInfo) <= 0) {
            return null;
        }

        return $userInfo[0];
    }

    /**
     * 读取指定类型，指定open id的三方信息
     * @param $third_type
     * @param $open_id
     * @return mixed|null
     */
    public static function getThirdInfo($third_type, $open_id) {

        $thirdInfo = Db::inst()->select()
            ->from(TTDB::TABLE_THIRD)
            ->where(self::where(TTDB::THIRD_OPENID, $open_id))
            ->where(self::where(TTDB::THIRD_PLATFORM_TYPE, $third_type))
            ->query();

        self::debugSql($thirdInfo, __LINE__);

        if(TTPublic::getRecordCount($thirdInfo) <= 0) {
            return null;
        }

        $thirdInfo = $thirdInfo[0];

        return $thirdInfo;
    }

    /**
     * 更新三方信息
     * @param $user_id
     * @param $third_type
     * @param $open_id
     * @param $nickname
     * @param $sex
     * @param $headimgurl
     * @return int|mixed
     */
    public static function updateThird($user_id, $third_type, $open_id, $nickname, $sex, $headimgurl) {

        $updateCols = self::cols([
            TTDB::THIRD_OPENID => $open_id,
            TTDB::UPDATE_TIME => TTPublic::getDoubleTime()
        ]);

        if(!empty($nickname)) {
            $updateCols[TTDB::THIRD_NICKNAME] = $nickname;
        }

        if(!empty($sex)) {
            $updateCols[TTDB::THIRD_SEX] = $sex;
        }

        if(!empty($headimgurl)) {
            $updateCols[TTDB::THIRD_HEADIMGURL] = $headimgurl;
        }

        try {
            $ret = Db::inst()->update(TTDB::TABLE_THIRD)
                ->cols($updateCols)
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->where(self::where(TTDB::THIRD_PLATFORM_TYPE, $third_type))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function removeThird($user_id, $third_type) {

        try {
            $ret = Db::inst()->update(TTDB::TABLE_THIRD)
                ->cols([TTDB::STATUS => TTDBConst::STATUS_DELETE])
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->where(self::where(TTDB::THIRD_PLATFORM_TYPE, $third_type))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret >= 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    /**
     * 添加三方信息
     * @param $user_id
     * @param $third_type
     * @param $open_id
     * @param $nickname
     * @param $sex
     * @param $headimgurl
     * @return int|mixed
     */
    private static function addThirdInfo($third_type, $open_id, $user_id, $nickname, $sex, $headimgurl) {

        $ret = TTDBConst::FAILED;

        try {
            $ret = Db::inst()->insert(TTDB::TABLE_THIRD)
                ->cols(self::cols([
                    TTDB::USER_ID => $user_id,
                    TTDB::THIRD_PLATFORM_TYPE => $third_type,
                    TTDB::THIRD_OPENID => $open_id,
                    TTDB::THIRD_NICKNAME => $nickname,
                    TTDB::THIRD_SEX => $sex,
                    TTDB::THIRD_HEADIMGURL => $headimgurl,
                    TTDB::STATUS => TTDBConst::STATUS_NORMAL,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => TTPublic::getDateTime()
                ]))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
        }

        return $ret;
    }

    /**
     * 设置三方信息为新的用户
     * @param $third_type
     * @param $open_id
     * @param $user_id
     * @param $nickname
     * @param $sex
     * @param $headimgurl
     * @return int|mixed
     */
    private static function setThirdInfo($third_type, $open_id, $user_id, $nickname, $sex, $headimgurl) {
        try {
            $ret = Db::inst()->update(TTDB::TABLE_THIRD)
                ->cols([TTDB::USER_ID => $user_id,
                    TTDB::THIRD_NICKNAME => $nickname,
                    TTDB::THIRD_SEX => $sex,
                    TTDB::THIRD_HEADIMGURL => $headimgurl,
                    TTDB::STATUS => TTDBConst::STATUS_NORMAL,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()])
                ->where(self::where(TTDB::THIRD_PLATFORM_TYPE, $third_type))
                ->where(self::where(TTDB::THIRD_OPENID, $open_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function setNewThirdInfo($user_id, $third_type, $open_id, $nickname, $sex, $headimgurl) {

        // 移除该类型的三方绑定
        if(self::removeThird($user_id, $third_type) != TTDBConst::OK) {
            return TTDBConst::FAILED;
        }

        // 读取三方注册信息
        $thirdInfo = self::getThirdInfo($third_type, $open_id);

        // 检测该三方账号是否绑定了其他手机,如何有，直接替换三方信息中的用户ID
        if(!empty($thirdInfo)) {

            // 更新三方信息的归宿用户及新的信息
            if(self::setThirdInfo($third_type, $open_id, $user_id, $nickname, $sex, $headimgurl) != TTDBConst::OK) {
                return TTDBConst::FAILED;
            }

        } else {

            // 添加注册
            if(self::addThirdInfo($third_type, $open_id, $user_id, $nickname, $sex, $headimgurl) != TTDBConst::OK) {
                return TTDBConst::FAILED;
            }
        }

        return TTDBConst::OK;
    }

    /**
     * 读取用户的三方信息
     * @param $user_id
     * @return mixed
     */
    public static function getThirdInfoByUser($user_id) {

        $thirdTypeInfo = Db::inst()->select()
            ->from(TTDB::TABLE_THIRD)
            ->where(self::where(TTDB::USER_ID, $user_id))
            ->where(self::where(TTDB::STATUS, TTDBConst::STATUS_NORMAL))
            ->query();

        self::debugSql($thirdTypeInfo, __LINE__);

        return $thirdTypeInfo;
    }

    // 添加俱乐部返回俱乐部id
    // 如果俱乐部存在，返回俱乐部id
    // 如果执行失败，返回null
    public static function addClub($club_name, $user_id) {

        // 读取俱乐部id
        $clubIdList = Db::inst()->select(TTDB::CLUB_ID)
            ->from(TTDB::TABLE_CLUB)
            ->where(self::where(TTDB::CLUB_NAME, $club_name))
            ->column();

        self::debugSql($clubIdList, __LINE__);

        try {
            if (TTPublic::getRecordCount($clubIdList) <= 0) {

                // 添加注册
                $ret = Db::inst()->insert(TTDB::TABLE_CLUB)
                    ->cols(self::cols([
                        TTDB::CLUB_NAME => $club_name,
                        TTDB::CLUB_CREATOR => $user_id,
                        TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                        TTDB::CREATE_TIME => TTPublic::getDateTime()
                    ]))
                    ->query();

                self::debugSql($ret, __LINE__);

                $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;

                // 检测执行结果
                if ($ret != TTDBConst::OK) {
                    return null;
                }

                // 读取俱乐部id
                $clubIdList = Db::inst()->select(TTDB::CLUB_ID)
                    ->from(TTDB::TABLE_CLUB)
                    ->where(self::where(TTDB::CLUB_NAME, $club_name))
                    ->column();

                self::debugSql($clubIdList, __LINE__);

                if (TTPublic::getRecordCount($clubIdList) <= 0) {
                    return null;
                }
            }

            $clubId = $clubIdList[0];

        } catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            return null;
        }

        return $clubId;
    }

    public static function findClub($club_name) {
        // 查找车友
//        $clubNameList = Db::inst()->select(TTDB::CLUB_NAME)
//            ->from(TTDB::TABLE_CLUB)
//            ->where(self::whereLike(TTDB::CLUB_NAME, $club_name))
//            ->limit(TTProfile::FIND_CLUB_LIMIT)
//            ->column();

        $sql = "select a.".TTDB::CLUB_NAME." from ".TTDB::TABLE_CLUB." as a"
            ." left join ".TTDB::TABLE_USER." b on(a.Id = b.club_id)"
            ." where b.club_id is not null and "
            .self::whereLike("a.".TTDB::CLUB_NAME, $club_name)
            ." group by a.".TTDB::CLUB_NAME." LIMIT ".TTProfile::FIND_CLUB_LIMIT;

        $clubNameList = Db::inst()->column($sql);
        self::debugSql($clubNameList, __LINE__);

        return $clubNameList;
    }

    // 获取好友的条件
    private static function _get_friend_where($user_id, $friend_id) {

        $friendWhere1 = self::linkWhere(self::where(TTDB::USER_ID, $user_id),
            "and", self::where(TTDB::UR_FRIEND_ID, $friend_id));

        $friendWhere2 = self::linkWhere(self::where(TTDB::USER_ID, $friend_id),
            "and", self::where(TTDB::UR_FRIEND_ID, $user_id));

        $friendWhere = self::linkWhere($friendWhere1, "or", $friendWhere2);

        $ret = self::linkWhere($friendWhere, "and",
            self::where(TTDB::STATUS, TTDBConst::STATUS_NORMAL));

        return $ret;
    }

    // 获取全部好友条件
    private static function _get_all_friend_where($user_id, $update_time = 0) {

        $friendWhere = self::linkWhere(self::where(TTDB::USER_ID, $user_id),
            "or", self::where(TTDB::UR_FRIEND_ID, $user_id));

        $where = self::linkWhere($friendWhere, "and",
            self::where(TTDB::STATUS, TTDBConst::STATUS_NORMAL));

        $where = self::linkWhere($where, "and",
            self::where(TTDB::UPDATE_TIME, $update_time, ">"));

        return $where;
    }

    // 获取全部好友条件（包括删除的）
    private static function _get_all_friend_include_delete_where($user_id, $update_time = 0) {

        $friendWhere = self::linkWhere(self::where(TTDB::USER_ID, $user_id),
            "or", self::where(TTDB::UR_FRIEND_ID, $user_id));

        $where = self::linkWhere($friendWhere, "and",
            self::where(TTDB::UPDATE_TIME, $update_time, ">"));

        return $where;
    }

    // 获取所有的朋友列表
    public static function getFriendIdList($user_id) {

        $addTypeList = self::getFriendAddTypeList($user_id);
        if(TTPublic::getRecordCount($addTypeList) <= 0) {
            return [];
        }

        return array_keys($addTypeList);
    }

    public static function getFriendAddTypeList($user_id) {

        // 查找全部好友
        $friendList = Db::inst()->select(TTDB::USER_ID
            .",".TTDB::UR_FRIEND_ID
            .",".TTDB::UR_ADD_TYPE)
            ->from(TTDB::TABLE_UR)
            ->where(self::_get_all_friend_where($user_id))
            ->query();

        self::debugSql($friendList, __LINE__);

        // 获取好友数量
        $total = TTPublic::getRecordCount($friendList);

        $showMobileList = array();

        // 读取好友Id
        for($i = 0; $i < $total; $i++) {
            $friendItem = $friendList[$i];
            if($friendItem[TTDB::USER_ID] != $user_id) {
                $showMobileList[$friendItem[TTDB::USER_ID]] = $friendItem[TTDB::UR_ADD_TYPE];
            } else if($friendItem[TTDB::UR_FRIEND_ID] != $user_id) {
                $showMobileList[$friendItem[TTDB::UR_FRIEND_ID]] = $friendItem[TTDB::UR_ADD_TYPE];
            }
        }

        return $showMobileList;
    }

    // 获取新关系的好友(包括曾经的好友)
    public static function getFriendIdListIncludeDelete($user_id, $update_time) {

        // 查找全部好友
        $friendList = Db::inst()->select(TTDB::USER_ID
            .",".TTDB::UR_FRIEND_ID)
            ->from(TTDB::TABLE_UR)
            ->where(self::_get_all_friend_include_delete_where($user_id, $update_time))
            ->query();

        self::debugSql($friendList, __LINE__);

        // 获取好友数量
        $total = TTPublic::getRecordCount($friendList);

        $userIdList = array();

        // 读取好友Id
        for($i = 0; $i < $total; $i++) {
            $friendItem = $friendList[$i];
            if($friendItem[TTDB::USER_ID] != $user_id) {
                $userIdList[] = $friendItem[TTDB::USER_ID];
            } else if($friendItem[TTDB::UR_FRIEND_ID] != $user_id) {
                $userIdList[] = $friendItem[TTDB::UR_FRIEND_ID];
            }
        }

        $userIdList = array_unique($userIdList);

        return $userIdList;
    }

    // 是否为好友
    public static function isFriend($user_id, $friend_id) {
        $ret = Db::inst()->select()
            ->from(TTDB::TABLE_UR)
            ->where(self::_get_friend_where($user_id, $friend_id))
            ->query();

        self::debugSql($ret, __LINE__);

        return TTPublic::getRecordCount($ret) > 0 ? true : false;
    }

    // 获取全部邀请消息（好友或群组）
    public static function getNotification($receiver_id, $type, $update_time) {

        // 转换条件
        $where = self::linkWhere(self::where("a.".TTDB::NOTIFY_RECEIVER_ID, $receiver_id),
        "and", self::where("a.".TTDB::NOTIFY_TYPE, $type));

//        $whereUpdate = self::linkWhere(self::where("a.".TTDB::UPDATE_TIME, $update_time, ">"),
//            "or", self::where("b.".TTDB::UPDATE_TIME, $update_time, ">"));
        $whereUpdate = self::where("a.".TTDB::UPDATE_TIME, $update_time, ">");

        $where = self::linkWhere($where, "and", $whereUpdate);

        // 获取sql指令
        $sql = 'select '.self::_get_invite_field($type, "a.")
//            .','.'case when a.update_time > b.update_time then a.update_time else b.update_time end as update_time'
            .','.'a.'.TTDB::UPDATE_TIME
            .','.self::_get_invite_member_info_field("b.");

        if($type == TTDBConst::NOTIFICATION_JOIN_GROUP) {
            $sql .= ','.'c.group_name,c.group_type';
        }

        $sql .= self::getDeviceClubField();

        $sql .= ' from tt_notification as a'
            .' left join tt_user b on (a.sender_id = b.user_id)';

        if($type == TTDBConst::NOTIFICATION_JOIN_GROUP) {
            $sql .= ' left join tt_group c on (a.group_id = c.group_id)';
        }

        $sql .= self::getDeviceClubLeftJoin("b");
        $sql .= ' where '.$where;
        $sql .= " order by a.update_time asc limit ".TTDBConst::RECORD_LIMIT;

        $notifyInfo = Db::inst()->query($sql);

        self::debugSql($notifyInfo, __LINE__);

        // 不是通过手机号码添加的好友，去掉手机号
        for($i = 0; $i < TTPublic::getRecordCount($notifyInfo); $i++) {
            $addType = $notifyInfo[$i][TTDB::NOTIFY_ADD_TYPE];
            if($addType != TTDBConst::ADD_TYPE_CONTACTS && $addType != TTDBConst::ADD_TYPE_MOBILE) {
                $notifyInfo[$i][TTDB::USER_MOBILE] = "";
            }
        }

        return $notifyInfo;
    }

    // 更新邀请状态
    public static function updateNotifyStatus($inviteId, $status) {

        try {
            $ret = Db::inst()->update(TTDB::TABLE_NOTIFY)
                ->cols(self::cols([
                    TTDB::STATUS => $status,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()
                ]))
                ->where(self::where(TTDB::NOTIFY_ID, $inviteId))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    // 添加好友邀请
    public static function addNotifyInvite($user_id, $friend_id, $add_type) {

        try {
            $ret = Db::inst()->insert(TTDB::TABLE_NOTIFY)
                ->cols(self::cols([
                    TTDB::NOTIFY_SENDER_ID => $user_id,
                    TTDB::NOTIFY_RECEIVER_ID => $friend_id,
                    TTDB::NOTIFY_TYPE => TTDBConst::NOTIFICATION_ADD_FRIEND,
                    TTDB::NOTIFY_ADD_TYPE => $add_type,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => TTPublic::getDateTime()
                ]))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    // 添加加群邀请
    public static function addNotifyGroup($user_id, $friend_id, $group_id) {

        try {
            $ret = Db::inst()->insert(TTDB::TABLE_NOTIFY)
                ->cols(self::cols([
                    TTDB::NOTIFY_SENDER_ID => $user_id,
                    TTDB::NOTIFY_RECEIVER_ID => $friend_id,
                    TTDB::NOTIFY_TYPE => TTDBConst::NOTIFICATION_JOIN_GROUP,
                    TTDB::GROUP_ID => $group_id,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => TTPublic::getDateTime()
                ]))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    // 更新好友邀请
    public static function updateNotifyInvite($user_id, $friend_id, $add_type) {

        try {
            $ret = Db::inst()->update(TTDB::TABLE_NOTIFY)
                ->cols(self::cols([
                    TTDB::NOTIFY_ADD_TYPE => $add_type,
                    TTDB::STATUS => TTDBConst::MSG_PROCESS_INIT,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()
                ]))
                ->where(self::where(TTDB::NOTIFY_SENDER_ID, $user_id))
                ->where(self::where(TTDB::NOTIFY_RECEIVER_ID, $friend_id))
                ->where(self::where(TTDB::NOTIFY_TYPE, TTDBConst::NOTIFICATION_ADD_FRIEND))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = TTDBConst::OK;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    // 更新加群邀请
    public static function updateNotifyGroup($user_id, $friend_id, $group_id) {

        try {
            $ret = Db::inst()->update(TTDB::TABLE_NOTIFY)
                ->cols(self::cols([
                    TTDB::STATUS => TTDBConst::MSG_PROCESS_INIT,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()
                ]))
                ->where(self::where(TTDB::NOTIFY_SENDER_ID, $user_id))
                ->where(self::where(TTDB::NOTIFY_RECEIVER_ID, $friend_id))
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->where(self::where(TTDB::NOTIFY_TYPE, TTDBConst::NOTIFICATION_JOIN_GROUP))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = TTDBConst::OK;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    // 获取指定发送者的好友邀请
    public static function getNotifyInviteBySender($sender_id, $receiver_id) {

        $inviteInfoList = Db::inst()->select(TTDB::NOTIFY_ID
            .",".TTDB::NOTIFY_ADD_TYPE
            .",".TTDB::STATUS)
            ->from(TTDB::TABLE_NOTIFY)
            ->where(self::where(TTDB::NOTIFY_SENDER_ID, $sender_id))
            ->where(self::where(TTDB::NOTIFY_RECEIVER_ID, $receiver_id))
            ->where(self::where(TTDB::NOTIFY_TYPE, TTDBConst::NOTIFICATION_ADD_FRIEND))
            ->query();

        self::debugSql($inviteInfoList, __LINE__);

        if(TTPublic::getRecordCount($inviteInfoList) <= 0) {
            return null;
        }

        return $inviteInfoList[0];
    }

    // 获取指定发送者的好友邀请
    public static function getNotifyInviteById($msg_id) {

        $inviteInfoList = Db::inst()->select()
            ->from(TTDB::TABLE_NOTIFY)
            ->where(self::where(TTDB::NOTIFY_ID, $msg_id))
            ->query();

        self::debugSql($inviteInfoList, __LINE__);

        if(TTPublic::getRecordCount($inviteInfoList) <= 0) {
            return null;
        }

        return $inviteInfoList[0];
    }

    // 获取指定发送者的加群邀请
    public static function getNotifyGroupBySender($sender_id, $receiver_id, $group_id) {

        $groupInfoList = Db::inst()->select(TTDB::NOTIFY_ID
            .",".TTDB::NOTIFY_ADD_TYPE
            .",".TTDB::STATUS)
            ->from(TTDB::TABLE_NOTIFY)
            ->where(self::where(TTDB::NOTIFY_SENDER_ID, $sender_id))
            ->where(self::where(TTDB::NOTIFY_RECEIVER_ID, $receiver_id))
            ->where(self::where(TTDB::GROUP_ID, $group_id))
            ->where(self::where(TTDB::NOTIFY_TYPE, TTDBConst::NOTIFICATION_JOIN_GROUP))
            ->query();

        self::debugSql($groupInfoList, __LINE__);

        if(TTPublic::getRecordCount($groupInfoList) <= 0) {
            return null;
        }

        return $groupInfoList[0];
    }

    // 删除好友
    public static function deleteFriend($user_id, $friend_id) {

        $ret = TTDBConst::FAILED;

        try {
            $ret = Db::inst()->update(TTDB::TABLE_UR)
                ->cols(self::cols([
                    TTDB::UR_LAST_OPERATOR => $user_id,
                    TTDB::STATUS => TTDBConst::STATUS_DELETE,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()
                ]))
                ->where(self::_get_friend_where($user_id, $friend_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
        }

        return $ret;
    }

    // 查找好友
    public static function findUser($my_user_id, $user_id, $mobile,
                                    $nick_name, $moto_brand, $moto_model,
                                    $province, $city, $club_name) {

        $where = "";

        if(isset($user_id)) {
            $where = self::linkWhere($where, "and",
                self::where("a.".TTDB::USER_ID, $user_id));
        }

        if(isset($mobile)) {
            $where = self::linkWhere($where, "and",
                self::where("a.".TTDB::USER_MOBILE, $mobile));
        }

        if(isset($nick_name)) {
            $where = self::linkWhere($where, "and",
                self::whereLike("a.".TTDB::USER_NICK_NAME, $nick_name));
        }

        if(isset($moto_brand)) {
            $where = self::linkWhere($where, "and",
                self::where("a.".TTDB::USER_MOTO_BRAND, $moto_brand));
        }

        if(isset($moto_model)) {
            $where = self::linkWhere($where, "and",
                self::where("a.".TTDB::USER_MOTO_MODEL, $moto_model));
        }

        if(isset($province)) {
            $where = self::linkWhere($where, "and",
                self::where("a.".TTDB::USER_PROVINCE, $province));
        }

        if(isset($city)) {
            $where = self::linkWhere($where, "and",
                self::where("a.".TTDB::USER_CITY, $city));
        }

        if(isset($club_name)) {
            $where = self::linkWhere($where, "and",
                self::where("g.".TTDB::CLUB_NAME, $club_name));
        }

        if(empty($where)) {
            return [];
        }

        $userInfoField = self::_get_user_info_field(false, false, "a.");

        $sql = 'select '.$userInfoField
            .',c.add_type,c.status as '.TTDB::LOCAL_INVITE_STATUS
            .self::getDeviceClubField()
            .' from tt_user as a'
            .' left join tt_notification c'
            .' on (a.user_id = c.receiver_id and c.sender_id = '.$my_user_id
            .' and c.status = '.TTDBConst::MSG_PROCESS_INIT
            .' and c.type = '.TTDBConst::NOTIFICATION_ADD_FRIEND.')'
            .self::getDeviceClubLeftJoin("a")
            .' where '.$where." limit ".TTProfile::FIND_FRIEND_LIMIT;

        $userInfo = Db::inst()->query($sql);

        self::debugSql($userInfo, __LINE__);

        return $userInfo;
    }

    private static function isShowMobile($addTypeList, $user_id) {
        $addType = $addTypeList[$user_id];
        return isset($addType) &&
            ($addType == TTDBConst::ADD_TYPE_MOBILE || $addType == TTDBConst::ADD_TYPE_CONTACTS);
    }

    private static function getDeviceClubField() {
        return ',concat(d.name, d.remark) as '.TTDB::USER_TERMINATOR
        .',concat(e.name, e.remark) as '.TTDB::USER_CONTROLLER
        .',concat(f.name, f.remark) as '.TTDB::USER_HEADSET
        .',g.name as '.TTDB::LOCAL_CLUB_NAME;
    }

    private static function getDeviceClubLeftJoin($user_table) {
        return ' left join tt_device_main d on ('.$user_table.'.user_id = d.user_id and d.status = '.TTDBConst::STATUS_NORMAL.')'
        .' left join tt_device_controler e on ('.$user_table.'.user_id = e.user_id and e.status = '.TTDBConst::STATUS_NORMAL.')'
        .' left join tt_device_headset f on ('.$user_table.'.user_id = f.user_id and f.status = '.TTDBConst::STATUS_NORMAL.')'
        .' left join tt_club g on ('.$user_table.'.club_id = g.Id)';
    }

    public static function getFriendUpdateList($my_user_id, $addTypeList, $updateIdList, $update_time) {

        // 检测是否有好友
        if(TTPublic::getRecordCount($addTypeList) <= 0) {
            return [];
        }

        // 获取查询条件
        $friendIdList = array_keys($addTypeList);
        $sqlWhere = '';
        if(TTPublic::getRecordCount($friendIdList) > 0) {
            $sqlWhere = self::linkWhere(self::where("a.".TTDB::UPDATE_TIME, $update_time, ">"),
                "and", self::whereIn("a.".TTDB::USER_ID, $friendIdList));
        }

        if(TTPublic::getRecordCount($updateIdList) > 0) {
            $sqlWhere = self::linkWhere($sqlWhere,
                "or ", self::whereIn("a.".TTDB::USER_ID, $updateIdList));
        }

        // 如果没有需要更新的用户，直接返回
        if(empty($sqlWhere)) {
            return [];
        }

        // 用户信息字段
        $userInfoField = self::_get_user_info_field(false, true, "a.");

        // 执行查询
        $sql = 'select '.$userInfoField
            .',c.nickname as '.TTDB::LOCAL_REMARK_NICKNAME
            .',c.mobile as '.TTDB::LOCAL_REMARK_MOBILE
            .self::getDeviceClubField()
            .' from tt_user as a'
            .' left join tt_user_remark c on (a.user_id = c.friend_id and c.user_id = '.$my_user_id.')'
            .self::getDeviceClubLeftJoin("a")
            .' where '.$sqlWhere.' order by a.update_time asc limit '.TTDBConst::RECORD_LIMIT;

        $userInfo = Db::inst()->query($sql);

        self::debugSql($userInfo, __LINE__);

        if(TTPublic::getRecordCount($userInfo) <= 0) {
            return [];
        }

        // 填充状态
        for($i = 0; $i < TTPublic::getRecordCount($userInfo); $i++) {
            $user_id = $userInfo[$i][TTDB::USER_ID];

            // 转换状态
            if(in_array($user_id, $friendIdList)) {
                $status = TTDBConst::STATUS_NORMAL;
            } else {
                $status = TTDBConst::STATUS_DELETE;
            }
            $userInfo[$i][TTDB::STATUS] = $status;

            // 获取是否需要显示手机号
            if(!self::isShowMobile($addTypeList, $user_id)) {
                $userInfo[$i][TTDB::USER_MOBILE] = "";
            }

            $userInfo[$i][TTDB::UR_ADD_TYPE] = $addTypeList[$user_id];
        }

        return $userInfo;
    }

    public static function addFriend($user_id, $friend_id, $add_type) {

        $ret = TTDBConst::OK;

        if(!TTDbFun::isFriend($user_id, $friend_id)) {

            // 添加好友
            $ret = Db::inst()->insert(TTDB::TABLE_UR)
                ->cols(self::cols([
                    TTDB::USER_ID => $friend_id,
                    TTDB::UR_FRIEND_ID => $user_id,
                    TTDB::UR_ADD_TYPE => $add_type,
                    TTDB::UR_LAST_OPERATOR => $user_id,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => TTPublic::getDateTime()
                ]))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getRemarkInfo($user_id, $friend_id) {

        $remarkInfo = Db::inst()->select()
            ->from(TTDB::TABLE_USER_REMARK)
            ->where(self::where(TTDB::USER_ID, $user_id))
            ->where(self::where(TTDB::UNI_FRIEND_ID, $friend_id))
            ->query();

        self::debugSql($remarkInfo, __LINE__);

        if(TTPublic::getRecordCount($remarkInfo) <= 0) {
            return null;
        }

        $remarkInfo = $remarkInfo[0];

        return $remarkInfo;

    }

    public static function setRemarkInfo($user_id, $friend_id, $nickname, $mobile) {

        $ret = TTDBConst::FAILED;

        try {
            // 获取备注信息
            $remarkInfo = TTDbFun::getRemarkInfo($user_id, $friend_id);
            if($remarkInfo == null) {

                // 添加备注
                $ret = Db::inst()->insert(TTDB::TABLE_USER_REMARK)
                    ->cols(self::cols([
                        TTDB::USER_ID => $user_id,
                        TTDB::UNI_FRIEND_ID => $friend_id,
                        TTDB::UNI_NICKNAME => isset($nickname) ? $nickname : null,
                        TTDB::UNI_MOBILE => isset($mobile) ? $mobile : null,
                        TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                        TTDB::CREATE_TIME => TTPublic::getDateTime()
                    ]))
                    ->query();

                self::debugSql($ret, __LINE__);

                $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;

            } else {

                // 更新备注
                $ret = Db::inst()->update(TTDB::TABLE_USER_REMARK)
                    ->cols(self::cols([TTDB::UNI_MOBILE => $mobile,
                        TTDB::UNI_NICKNAME => $nickname,
                        TTDB::UPDATE_TIME => TTPublic::getDoubleTime()]))
                    ->where(self::where(TTDB::USER_ID, $user_id))
                    ->where(self::where(TTDB::UNI_FRIEND_ID, $friend_id))
                    ->query();

                self::debugSql($ret, __LINE__);

                $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
            }
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
        }

        return $ret;
    }

    public static function getMobileListNotFriend($user_id, $mobile_list, $friend_id_list) {

        if(TTPublic::getRecordCount($mobile_list) <= 0) {
            return [];
        }

        // 执行条件
        $where = "";
        if(TTPublic::getRecordCount($mobile_list) > 0) {
            $where = self::whereIn("a.".TTDB::USER_MOBILE, $mobile_list);
        }

        if(TTPublic::getRecordCount($friend_id_list) > 0) {
            $where = self::linkWhere($where,
                "and", self::whereNotIn("a.".TTDB::USER_ID, $friend_id_list));
        }

        // 执行查询
        $sql = "select a.mobile,a.user_id,a.avatar_url,b.add_type,b.status"
            ." from tt_user as a"
            ." left join tt_notification b"
            ." on (a.user_id = b.receiver_id and b.sender_id = ".$user_id
            ." and b.status = ".TTDBConst::MSG_PROCESS_INIT
            ." and b.type = ".TTDBConst::NOTIFICATION_ADD_FRIEND.")"
            ." where true=true and ".$where;

        $retList = Db::inst()->query($sql);

        self::debugSql($retList, __LINE__);

        return $retList;
    }

    public static function createGroup($user_id, $group_name, $avatar_url, $type,
                                       $notice, $longitude, $latitude,
                                       $leader, $rider1, $rider2, $rider3, $ending, $dateTime) {

        // 获取群组access_code
        $where = TTDB::GROUP_GROUP_STATUS." = ".TTDBConst::STATUS_NORMAL
            ." and (".TTDB::GROUP_RIDING_STATUS." <> ".TTDBConst::RIDING_STATUS_END
            ." or DATEDIFF(\"".TTPublic::getDateTime()."\", ".TTDB::CREATE_TIME.") <= 90)";

        $sql = "select access_code"
            ." from tt_group"
            ." where ".$where
            ." group by access_code";

        $access_code_list = Db::inst()->column($sql);

        self::debugSql($access_code_list, __LINE__);

        // 创建唯一access_code
        srand(TTPublic::getTime());
        $access_code = str_pad(rand(0, 999999),6,"0",STR_PAD_LEFT);
        if(TTPublic::getRecordCount($access_code_list) > 0) {
            while(in_array($access_code, $access_code_list)) {
                PhpLog::Error("access_code error ".$access_code);
                $access_code = str_pad(rand(0, 999999),6,"0",STR_PAD_LEFT);
            }
        }

        // 添加群组
        try {
            $ret = Db::inst()->insert(TTDB::TABLE_GROUP)
                ->cols(self::cols([TTDB::GROUP_MASTER => $user_id,
                    TTDB::GROUP_GROUP_NAME => $group_name,
                    TTDB::GROUP_AVATAR => $avatar_url,
                    TTDB::GROUP_GROUP_TYPE => $type,
                    TTDB::GROUP_NOTICE => $notice,
                    TTDB::GROUP_CREATE_LNG => $longitude,
                    TTDB::GROUP_CREATE_LAT => $latitude,
                    TTDB::GROUP_ACCESS_CODE => $access_code,
                    TTDB::GROUP_LAST_ACTIVE_TIME => $dateTime,
                    TTDB::GROUP_LEADER => $leader,
                    TTDB::GROUP_RIDER1 => $rider1,
                    TTDB::GROUP_RIDER2 => $rider2,
                    TTDB::GROUP_RIDER3 => $rider3,
                    TTDB::GROUP_ENDING => $ending,
                    TTDB::GROUP_RIDING_STATUS => TTDBConst::RIDING_STATUS_START,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => $dateTime]))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getLastInsertGroupInfo() {
        $groupInfo  = Db::inst()->select()
            ->from(TTDB::TABLE_GROUP)
            ->where(TTDB::GROUP_ID." = LAST_INSERT_ID()")
            ->query();

        self::debugSql($groupInfo, __LINE__);

        if(TTPublic::getRecordCount($groupInfo) <= 0) {
            return null;
        }

        return $groupInfo[0];
    }

    public static function getGroupMemberStatus($group_id, $user_id) {
        $groupRalation  = Db::inst()->select()
            ->from(TTDB::TABLE_GR)
            ->where(self::where(TTDB::GROUP_ID, $group_id))
            ->where(self::where(TTDB::USER_ID, $user_id))
            ->query();

        self::debugSql($groupRalation, __LINE__);

        if(TTPublic::getRecordCount($groupRalation) <= 0) {
            return null;
        }

        return $groupRalation[0];
    }

    // [user_id => status,...]
    public static function getGroupMembersStatus($group_id) {
        $gr_info  = Db::inst()->select(TTDB::USER_ID.",".TTDB::STATUS)
            ->from(TTDB::TABLE_GR)
            ->where(self::where(TTDB::GROUP_ID, $group_id))
            ->query();

        self::debugSql($gr_info, __LINE__);

        $total = TTPublic::getRecordCount($gr_info);

        $groupMembers = array();

        // 读取好友Id
        for($i = 0; $i < $total; $i++) {
            $info = $gr_info[$i];
            $groupMembers[$info[TTDB::USER_ID]] = $info[TTDB::STATUS];
        }

        return $groupMembers;
    }

    public static function getGroupMembersId($group_id, $except_user_id = null) {

        $whereSql = self::where(TTDB::GROUP_ID, $group_id)
            ." and ".self::where(TTDB::STATUS, TTDBConst::STATUS_NORMAL);
        if(!empty($except_user_id)) {
            $whereSql .= " and ".self::where(TTDB::USER_ID, $except_user_id, "<>");
        }

        $userIdList  = Db::inst()->select(TTDB::USER_ID)
            ->from(TTDB::TABLE_GR)
            ->where($whereSql)
            ->column();

        self::debugSql($userIdList, __LINE__);

        return $userIdList;
    }

    public static function setGroupMemberStatus($group_id, $user_id, $status) {
        try {
            $ret = Db::inst()->update(TTDB::TABLE_GR)
                ->cols(self::cols([
                    TTDB::STATUS => $status,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()
                ]))
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function addGroupMember($group_id, $user_id, $group_type) {

        try {
            $ret = Db::inst()->insert(TTDB::TABLE_GR)
                ->cols(self::cols([
                    TTDB::GROUP_ID => $group_id,
                    TTDB::USER_ID => $user_id,
                    TTDB::STATUS => TTDBConst::STATUS_NORMAL,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => TTPublic::getDateTime()
                ]))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getGroupInfoByUser($user_id) {

        $sql = "select a.share_location,".self::_get_group_info_field("b.")
            ." from tt_group_relationship as a"
            ." left join tt_group b on (a.group_id = b.group_id)"
            ." where ".self::where("b.".TTDB::GROUP_GROUP_STATUS, TTDBConst::STATUS_NORMAL)
            ." and ".self::where("a.".TTDB::USER_ID, $user_id)
            ." and ".self::where("a.".TTDB::STATUS, TTDBConst::STATUS_NORMAL)
            ." and ".self::where("b.".TTDB::GROUP_RIDING_STATUS,
                TTDBConst::RIDING_STATUS_END, "<>");

        $groupInfo = Db::inst()->query($sql);

        self::debugSql($groupInfo, __LINE__);

        if(TTPublic::getRecordCount($groupInfo) <= 0) {
            return null;
        }

        return $groupInfo[0];
    }

    public static function getGroupFullInfoByAccessCode($access_code) {
        $groupInfo = Db::inst()->select()
            ->from(TTDB::TABLE_GROUP)
            ->where(self::where(TTDB::GROUP_ACCESS_CODE, $access_code))
            ->where(self::where(TTDB::GROUP_GROUP_STATUS, TTDBConst::STATUS_NORMAL))
            ->where(self::where(TTDB::GROUP_RIDING_STATUS, TTDBConst::RIDING_STATUS_END, "<>"))
            ->query();

        self::debugSql($groupInfo, __LINE__);

        if(TTPublic::getRecordCount($groupInfo) <= 0) {
            return null;
        }

        return $groupInfo[0];
    }

    public static function getGroupFullInfo($group_id) {
        $groupInfo = Db::inst()->select()
            ->from(TTDB::TABLE_GROUP)
            ->where(self::where(TTDB::GROUP_ID, $group_id))
//            ->where(self::where(TTDB::GROUP_GROUP_STATUS, TTDBConst::STATUS_NORMAL))
//            ->where(self::where(TTDB::GROUP_RIDING_STATUS, TTDBConst::RIDING_STATUS_END, "<>"))
            ->query();

        self::debugSql($groupInfo, __LINE__);

        if(TTPublic::getRecordCount($groupInfo) <= 0) {
            return null;
        }

        return $groupInfo[0];
    }

    public static function getGroupInfo($group_id) {
        $groupInfo = Db::inst()->select(self::_get_group_info_field())
            ->from(TTDB::TABLE_GROUP)
            ->where(self::where(TTDB::GROUP_ID, $group_id))
//            ->where(self::where(TTDB::GROUP_GROUP_STATUS, TTDBConst::STATUS_NORMAL))
//            ->where(self::where(TTDB::GROUP_RIDING_STATUS, TTDBConst::RIDING_STATUS_END, "<>"))
            ->query();

        self::debugSql($groupInfo, __LINE__);

        if(TTPublic::getRecordCount($groupInfo) <= 0) {
            return null;
        }

        return $groupInfo[0];
    }

    public static function setGroupStatus($group_id, $status) {
        try {
            $ret = Db::inst()->update(TTDB::TABLE_GROUP)
                ->cols(self::cols([TTDB::GROUP_GROUP_STATUS => $status,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()]))
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function updateGroupInfo($group_id, $updateList) {
        try {

            // 更新时间戳
            $updateList[TTDB::UPDATE_TIME] = TTPublic::getDoubleTime();

            $ret = Db::inst()->update(TTDB::TABLE_GROUP)
                ->cols(self::cols($updateList))
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->where(self::where(TTDB::GROUP_GROUP_STATUS, TTDBConst::STATUS_NORMAL))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getMembersCountInGroup($group_id) {

        $result = Db::inst()->select("count(*)")
            ->from(TTDB::TABLE_GR)
            ->where(self::where(TTDB::GROUP_ID, $group_id))
            ->where(self::where(TTDB::STATUS, TTDBConst::STATUS_NORMAL))
            ->column();

        self::debugSql($result, __LINE__);

        if(TTPublic::getRecordCount($result) <= 0) {
            return 0;
        }

        return $result[0];
    }

    public static function getMembersInGroup($group_id) {

        $member_field = self::_get_member_info_field("a.");

        // 执行条件
        $where = self::linkWhere(self::where("b.".TTDB::GROUP_ID, $group_id),
            "and", self::where("b.".TTDB::STATUS, TTDBConst::STATUS_NORMAL));

        // 执行语句
        $sql = "select ".$member_field
            .',case when a.update_time > b.update_time then a.update_time else b.update_time end as update_time'
            .",b.share_location,b.create_time,b.status"
            .self::getDeviceClubField()
            ." from tt_user as a"
            ." left join tt_group_relationship b on (b.user_id = a.user_id)"
            .self::getDeviceClubLeftJoin("a")
            ." where ".$where
            .' order by update_time asc limit '.TTProfile::GROUP_INVITE_MEMBER_LIMIT;

        $group_members = Db::inst()->query($sql);

        self::debugSql($group_members, __LINE__);

        return $group_members;
    }

    // 获取成员信息
    public static function getMembersInfo($group_id, $update_time) {

        $member_field = self::_get_member_info_field("a.");

        // 执行条件
        $whereUpdate = self::linkWhere(self::where("a.".TTDB::UPDATE_TIME, $update_time, ">"),
            "or", self::where("b.".TTDB::UPDATE_TIME, $update_time, ">"));

        $where = self::linkWhere(self::where("b.".TTDB::GROUP_ID, $group_id),
            "and", $whereUpdate);

        // 执行语句
        $sql = "select ".$member_field
            .',case when a.update_time > b.update_time then a.update_time else b.update_time end as update_time'
            .",b.share_location,b.create_time,b.status"
            .self::getDeviceClubField()
            ." from tt_user as a"
            ." left join tt_group_relationship b on (b.user_id = a.user_id)"
            .self::getDeviceClubLeftJoin("a")
            ." where ".$where
            .' order by update_time asc limit '.TTDBConst::RECORD_LIMIT;

        $group_members = Db::inst()->query($sql);

        self::debugSql($group_members, __LINE__);

        return $group_members;
    }

    public static function removeGroupInvite($group_id, $user_id = null) {

        $sqlWhere = self::where(TTDB::GROUP_ID, $group_id);
        if($user_id != null) {
            $sqlWhere = $sqlWhere." and ".self::where(TTDB::NOTIFY_RECEIVER_ID, $user_id);
        }

        try {
            $ret = Db::inst()->update(TTDB::TABLE_NOTIFY)
                ->cols(self::cols([
                    TTDB::STATUS => TTDBConst::MSG_PROCESS_REMOVE,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()]))
                ->where(self::where(TTDB::STATUS, TTDBConst::MSG_PROCESS_INIT))
                ->where(self::where(TTDB::NOTIFY_TYPE, TTDBConst::NOTIFICATION_JOIN_GROUP))
                ->where($sqlWhere)
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = TTDBConst::OK; // 不管更新多少条记录，都认为成功
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;

    }

    public static function getGroupInviteUserId($group_id) {

        try {
            $userIds = Db::inst()->select(TTDB::NOTIFY_RECEIVER_ID)
                ->from(TTDB::TABLE_NOTIFY)
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->where(self::where(TTDB::STATUS, TTDBConst::MSG_PROCESS_INIT))
                ->where(self::where(TTDB::NOTIFY_TYPE, TTDBConst::NOTIFICATION_JOIN_GROUP))
                ->column();
        }catch (Exception $ex) {
            $userIds = [];
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
        }

        self::debugSql($userIds, __LINE__);

        return $userIds;
    }

    public static function getPasswordByMobile($mobile) {

        $userInfo = Db::inst()->select(TTDB::USER_PASSWORD)
            ->from(TTDB::TABLE_USER)
            ->where(self::where(TTDB::USER_MOBILE, $mobile))
            ->query();

        self::debugSql($userInfo, __LINE__);

        if(TTPublic::getRecordCount($userInfo) <= 0) {
            return null;
        }

        return $userInfo[0];
    }

    public static function startRidingRecord($user_id, $group_id, $group_type) {

        PhpLog::Log("startRidingRecord $user_id, $group_id, $group_type");

        try {

            $date_time = TTPublic::getDateTime();

            $recordInfo = Db::inst()->select(
                TTDB::RIDE_MAX_SPEED.",".TTDB::RIDE_TOTAL_TIME.",". TTDB::RIDE_RECORD_STATUS)
                ->from(TTDB::TABLE_RIDE)
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->query();

            self::debugSql($recordInfo, __LINE__);

            if(TTPublic::getRecordCount($recordInfo) <= 0) {
                $ret = Db::inst()->insert(TTDB::TABLE_RIDE)
                    ->cols(self::cols([
                        TTDB::USER_ID => $user_id,
                        TTDB::GROUP_ID => $group_id,
                        TTDB::RIDE_GROUP_TYPE => $group_type,
                        TTDB::RIDE_START_TIME => $date_time,
                        TTDB::RIDE_RECORD_STATUS => TTDBConst::RIDING_RECORD_STATUS_START,
                        TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                        TTDB::CREATE_TIME => $date_time
                    ]))
                    ->query();

                self::debugSql($ret, __LINE__);

            } else {
                $ret = Db::inst()->update(TTDB::TABLE_RIDE)
                    ->cols(self::cols([
                        TTDB::RIDE_GROUP_TYPE => $group_type,
                        TTDB::RIDE_RECORD_STATUS => TTDBConst::RIDING_RECORD_STATUS_START,
                        TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    ]))
                    ->where(self::where(TTDB::USER_ID, $user_id))
                    ->where(self::where(TTDB::GROUP_ID, $group_id))
                    ->query();

                self::debugSql($ret, __LINE__);

                if($ret > 0) {
                    TTRedis::setMaxSpeed($user_id, $group_id, $recordInfo[0][TTDB::RIDE_MAX_SPEED]);
                    TTRedis::setTotalTime($user_id, $group_id, $recordInfo[0][TTDB::RIDE_TOTAL_TIME]);
                }
            }

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function deleteRidingRecord($user_id, $group_id) {

        PhpLog::Log("deleteRidingRecord $user_id, $group_id");

        try {

            $date_time = TTPublic::getDateTime();

            $ret = Db::inst()->update(TTDB::TABLE_RIDE)
                ->cols(self::cols([
                    TTDB::RIDE_END_TIME => $date_time,
                    TTDB::RIDE_RECORD_STATUS => TTDBConst::RIDING_RECORD_STATUS_DELETE,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                ]))
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }


    public static function endRidingRecord($user_id, $group_id, $group_avatar,
                                           $max_speed, $total_time, $member_count) {

        PhpLog::Log("endRidingRecord $user_id, $group_id, $max_speed");

        try {

            $date_time = TTPublic::getDateTime();

            $ret = Db::inst()->update(TTDB::TABLE_RIDE)
                ->cols(self::cols([
                    TTDB::RIDE_GROUP_AVATAR => $group_avatar,
                    TTDB::RIDE_END_TIME => $date_time,
                    TTDB::RIDE_MAX_SPEED => $max_speed,
                    TTDB::RIDE_TOTAL_TIME => $total_time,
                    TTDB::RIDE_TOTAL_MEMBER => $member_count,
                    TTDB::RIDE_RECORD_STATUS => TTDBConst::RIDING_RECORD_STATUS_END,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                ]))
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getRecordInfo($user_id, $update_time = 0) {

        $recordInfo = Db::inst()->select(
            TTDB::GROUP_ID.",".TTDB::RIDE_GROUP_TYPE.",".TTDB::RIDE_GROUP_AVATAR
            .",".TTDB::RIDE_START_TIME.",".TTDB::RIDE_END_TIME
            .",".TTDB::RIDE_START_LNG.",".TTDB::RIDE_START_LAT.",".TTDB::RIDE_START_ADDR
            .",".TTDB::RIDE_END_LNG.",".TTDB::RIDE_END_LAT.",".TTDB::RIDE_END_ADDR
            .",".TTDB::RIDE_MAX_SPEED.",".TTDB::RIDE_TOTAL_TIME.",".TTDB::RIDE_TOTAL_MILES
            .",".TTDB::RIDE_TOTAL_MEMBER.",".TTDB::RIDE_RECORD_STATUS.",".TTDB::RIDE_LOCATION_URL
            .",".TTDB::UPDATE_TIME.",".TTDB::CREATE_TIME)
            ->from(TTDB::TABLE_RIDE)
            ->where(self::where(TTDB::USER_ID, $user_id))
            ->where(self::where(TTDB::UPDATE_TIME, $update_time, ">"))
            ->orderByASC([TTDB::UPDATE_TIME])
            ->limit(TTDBConst::RECORD_LIMIT)
            ->query();

        self::debugSql($recordInfo, __LINE__);

        return $recordInfo;
    }

    public static function finishRidingRecord($user_id, $group_id, $start_lat, $start_lng, $start_addr,
                                            $end_lat, $end_lng, $end_addr, $instance) {
        try {

            $ret = Db::inst()->update(TTDB::TABLE_RIDE)
                ->cols(self::cols([
                    TTDB::RIDE_START_LAT => empty($start_lat) ? 0 : $start_lat,
                    TTDB::RIDE_START_LNG => empty($start_lng) ? 0 : $start_lng,
                    TTDB::RIDE_START_ADDR => $start_addr,
                    TTDB::RIDE_END_LAT => empty($end_lat) ? 0 : $end_lat,
                    TTDB::RIDE_END_LNG => empty($end_lng) ? 0 : $end_lng,
                    TTDB::RIDE_END_ADDR => $end_addr,
                    TTDB::RIDE_TOTAL_MILES => $instance,
                    TTDB::RIDE_LOCATION_URL => TTLocation::getUrl($group_id, $user_id),
                    TTDB::RIDE_RECORD_STATUS => TTDBConst::RIDING_RECORD_STATUS_FINISH,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                ]))
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;

        } catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function removeMainDevice($user_id) {

        try {
            $ret = Db::inst()->update(TTDB::TABLE_DEVICE_MAIN)
                ->cols([TTDB::STATUS => TTDBConst::STATUS_DELETE])
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret >= 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getMainDeviceInfo($address) {

        $deviceInfo = Db::inst()->select()
            ->from(TTDB::TABLE_DEVICE_MAIN)
            ->where(self::where(TTDB::DEVICE_ADDRESS, $address))
            ->query();

        self::debugSql($deviceInfo, __LINE__);

        if(TTPublic::getRecordCount($deviceInfo) <= 0) {
            return null;
        }

        $deviceInfo = $deviceInfo[0];

        return $deviceInfo;
    }

    public static function setMainDeviceInfo($address, $user_id, $name, $version, $serial_number, $remark) {
        try {
            $ret = Db::inst()->update(TTDB::TABLE_DEVICE_MAIN)
                ->cols([TTDB::USER_ID => $user_id,
                    TTDB::DEVICE_NAME => $name,
                    TTDB::DEVICE_VERSION => $version,
                    TTDB::DEVICE_SERIAL_NUMBER => $serial_number,
                    TTDB::DEVICE_REMARK => $remark,
                    TTDB::STATUS => TTDBConst::STATUS_NORMAL,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()])
                ->where(self::where(TTDB::DEVICE_ADDRESS, $address))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function addMainDeviceInfo($address, $user_id, $name, $version, $serial_number, $remark) {
        try {
            $ret = Db::inst()->insert(TTDB::TABLE_DEVICE_MAIN)
                ->cols([TTDB::USER_ID => $user_id,
                    TTDB::DEVICE_ADDRESS => $address,
                    TTDB::DEVICE_NAME => $name,
                    TTDB::DEVICE_VERSION => $version,
                    TTDB::DEVICE_SERIAL_NUMBER => $serial_number,
                    TTDB::DEVICE_REMARK => $remark,
                    TTDB::STATUS => TTDBConst::STATUS_NORMAL,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => TTPublic::getDateTime()])
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function removeControlerDevice($user_id) {

        try {
            $ret = Db::inst()->update(TTDB::TABLE_DEVICE_CONTROLER)
                ->cols([TTDB::STATUS => TTDBConst::STATUS_DELETE])
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret >= 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getControlerDeviceInfo($address) {

        $deviceInfo = Db::inst()->select()
            ->from(TTDB::TABLE_DEVICE_CONTROLER)
            ->where(self::where(TTDB::DEVICE_ADDRESS, $address))
            ->query();

        self::debugSql($deviceInfo, __LINE__);

        if(TTPublic::getRecordCount($deviceInfo) <= 0) {
            return null;
        }

        $deviceInfo = $deviceInfo[0];

        return $deviceInfo;
    }

    public static function setControlerDeviceInfo($address, $user_id, $name, $version, $serial_number, $remark) {
        try {
            $ret = Db::inst()->update(TTDB::TABLE_DEVICE_CONTROLER)
                ->cols([TTDB::USER_ID => $user_id,
                    TTDB::DEVICE_NAME => $name,
                    TTDB::DEVICE_VERSION => $version,
                    TTDB::DEVICE_SERIAL_NUMBER => $serial_number,
                    TTDB::DEVICE_REMARK => $remark,
                    TTDB::STATUS => TTDBConst::STATUS_NORMAL,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()])
                ->where(self::where(TTDB::DEVICE_ADDRESS, $address))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function addControlerDeviceInfo($address, $user_id, $name, $version, $serial_number, $remark) {
        try {
            $ret = Db::inst()->insert(TTDB::TABLE_DEVICE_CONTROLER)
                ->cols([TTDB::USER_ID => $user_id,
                    TTDB::DEVICE_ADDRESS => $address,
                    TTDB::DEVICE_NAME => $name,
                    TTDB::DEVICE_VERSION => $version,
                    TTDB::DEVICE_SERIAL_NUMBER => $serial_number,
                    TTDB::DEVICE_REMARK => $remark,
                    TTDB::STATUS => TTDBConst::STATUS_NORMAL,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => TTPublic::getDateTime()])
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function removeHeadsetDevice($user_id) {

        try {
            $ret = Db::inst()->update(TTDB::TABLE_DEVICE_HEADSET)
                ->cols([TTDB::STATUS => TTDBConst::STATUS_DELETE])
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret >= 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getHeadsetDeviceInfo($address, $user_id) {

        $deviceInfo = Db::inst()->select()
            ->from(TTDB::TABLE_DEVICE_HEADSET)
            ->where(self::where(TTDB::DEVICE_ADDRESS, $address))
            ->where(self::where(TTDB::USER_ID, $user_id))
            ->query();

        self::debugSql($deviceInfo, __LINE__);

        if(TTPublic::getRecordCount($deviceInfo) <= 0) {
            return null;
        }

        $deviceInfo = $deviceInfo[0];

        return $deviceInfo;
    }

    public static function setHeadsetDeviceInfo($address, $user_id, $name, $version, $serial_number, $remark) {
        try {
            $ret = Db::inst()->update(TTDB::TABLE_DEVICE_HEADSET)
                ->cols([TTDB::DEVICE_NAME => $name,
                    TTDB::DEVICE_VERSION => $version,
                    TTDB::DEVICE_SERIAL_NUMBER => $serial_number,
                    TTDB::DEVICE_REMARK => $remark,
                    TTDB::STATUS => TTDBConst::STATUS_NORMAL,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()])
                ->where(self::where(TTDB::DEVICE_ADDRESS, $address))
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function addHeadsetDeviceInfo($address, $user_id, $name, $version, $serial_number, $remark) {
        try {
            $ret = Db::inst()->insert(TTDB::TABLE_DEVICE_HEADSET)
                ->cols([TTDB::USER_ID => $user_id,
                    TTDB::DEVICE_ADDRESS => $address,
                    TTDB::DEVICE_NAME => $name,
                    TTDB::DEVICE_VERSION => $version,
                    TTDB::DEVICE_SERIAL_NUMBER => $serial_number,
                    TTDB::DEVICE_REMARK => $remark,
                    TTDB::STATUS => TTDBConst::STATUS_NORMAL,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => TTPublic::getDateTime()])
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getMainDeviceList($address_list) {

        $sql = "select b.user_id,b.nick_name,b.avatar_url,a.name,a.remark,a.address"
                ." from tt_device_main as a"
                ." left join tt_user b on (a.user_id = b.user_id)"
                ." where ".self::whereIn("a.address", $address_list);

        $deviceList = Db::inst()->query($sql);

        self::debugSql($deviceList, __LINE__);

        return $deviceList;
    }

    public static function existNickName($user_id, $nick_name) {

        $userList = Db::inst()->select()
            ->from(TTDB::TABLE_USER)
            ->where(self::where(TTDB::USER_NICK_NAME, $nick_name))
            ->where(self::where(TTDB::USER_ID, $user_id, "<>"))
            ->query();

        self::debugSql($userList, __LINE__);

        return TTPublic::getRecordCount($userList) <= 0;
    }

    public static function setGroupShareLocation($user_id, $group_id, $share_location) {
        try {

            // 修改是否共享定位信息
            $ret = Db::inst()->update(TTDB::TABLE_GR)
                ->cols(self::cols([TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::GR_SHARE_LOCATION => $share_location]))
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        }catch (Exception $ex) {
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function createBlueGroup($user_id, $allUsers, $unique, $dateTime) {

        // 添加群组
        try {

            // 添加自己到群组成员中
            $allUsers[] = $user_id;

            // 获取用户信息
            $sql = 'select a.user_id,b.address'
                .' from '.TTDB::TABLE_USER.' as a'
                .' left join '.TTDB::TABLE_DEVICE_MAIN.' b'
                .' on(a.user_id = b.user_id and b.`status` = '.TTDBConst::STATUS_NORMAL.')'
                .' where '.self::whereIn('a.'.TTDB::USER_ID, $allUsers);

            $usersInfo = Db::inst()->query($sql);

            self::debugSql($usersInfo, __LINE__);

            if(TTPublic::getRecordCount($usersInfo) <= 0) {
                return null;
            }

            // 添加群组
            $ret = Db::inst()->insert(TTDB::TABLE_BLUE_GROUP)
                ->cols(self::cols([TTDB::GROUP_MASTER => $user_id,
                    TTDB::BLUE_GROUP_UNIQUE => $unique,
                    TTDB::BLUE_GROUP_USERS => json_encode($usersInfo),
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => $dateTime]))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getLastInsertBlueGroupInfo() {
        $groupInfo  = Db::inst()->select()
            ->from(TTDB::TABLE_BLUE_GROUP)
            ->where(TTDB::GROUP_ID." = LAST_INSERT_ID()")
            ->query();

        self::debugSql($groupInfo, __LINE__);

        if(TTPublic::getRecordCount($groupInfo) <= 0) {
            return null;
        }

        return $groupInfo[0];
    }

    public static function setBlueGroupMemberStatus($group_id, $user_id, $status) {
        try {
            $ret = Db::inst()->update(TTDB::TABLE_BLUE_GR)
                ->cols(self::cols([
                    TTDB::STATUS => $status,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()
                ]))
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->where(self::where(TTDB::USER_ID, $user_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = TTDBConst::OK;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function addBlueGroupMember($group_id, $user_id, $is_master) {

        try {

            // 管理员不需要确认
            $status = $is_master ? TTDBConst::STATUS_NORMAL : TTDBConst::STATUS_DELETE;

            // 插入群组成员
            $ret = Db::inst()->insert(TTDB::TABLE_BLUE_GR)
                ->cols(self::cols([
                    TTDB::GROUP_ID => $group_id,
                    TTDB::USER_ID => $user_id,
                    TTDB::STATUS => $status,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => TTPublic::getDateTime()
                ]))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getBlueNotifyGroupBySender($sender_id, $receiver_id, $group_id) {

        $groupInfoList = Db::inst()->select(TTDB::NOTIFY_ID.",".TTDB::STATUS)
            ->from(TTDB::TABLE_BLUE_NOTIFY)
            ->where(self::where(TTDB::NOTIFY_SENDER_ID, $sender_id))
            ->where(self::where(TTDB::NOTIFY_RECEIVER_ID, $receiver_id))
            ->where(self::where(TTDB::GROUP_ID, $group_id))
            ->query();

        self::debugSql($groupInfoList, __LINE__);

        if(TTPublic::getRecordCount($groupInfoList) <= 0) {
            return null;
        }

        return $groupInfoList[0];
    }

    public static function addBlueNotifyGroup($user_id, $friend_id, $group_id) {

        try {
            $ret = Db::inst()->insert(TTDB::TABLE_BLUE_NOTIFY)
                ->cols(self::cols([
                    TTDB::NOTIFY_SENDER_ID => $user_id,
                    TTDB::NOTIFY_RECEIVER_ID => $friend_id,
                    TTDB::GROUP_ID => $group_id,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime(),
                    TTDB::CREATE_TIME => TTPublic::getDateTime()
                ]))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function updateBlueNotifyGroup($user_id, $friend_id, $group_id) {

        try {
            $ret = Db::inst()->update(TTDB::TABLE_BLUE_NOTIFY)
                ->cols(self::cols([
                    TTDB::STATUS => TTDBConst::MSG_PROCESS_INIT,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()
                ]))
                ->where(self::where(TTDB::NOTIFY_SENDER_ID, $user_id))
                ->where(self::where(TTDB::NOTIFY_RECEIVER_ID, $friend_id))
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = TTDBConst::OK;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getBlueGroupInviteUserId($group_id) {

        try {
            $userIds = Db::inst()->select(TTDB::NOTIFY_RECEIVER_ID)
                ->from(TTDB::TABLE_BLUE_NOTIFY)
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->where(self::where(TTDB::STATUS, TTDBConst::MSG_PROCESS_INIT))
                ->column();
        }catch (Exception $ex) {
            $userIds = [];
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
        }

        self::debugSql($userIds, __LINE__);

        return $userIds;
    }

    // 获取全部蓝牙群组邀请消息
    public static function getBlueGroupInvite($receiver_id, $update_time) {

        if(empty($update_time)) {
            $update_time = 0;
        }

        $tableA = "a.";
        $tableB = "b.";
        $tableC = "c.";

        // 转换条件
        $where = self::linkWhere(self::where($tableA.TTDB::NOTIFY_RECEIVER_ID, $receiver_id),
            "and",
            self::where($tableA.TTDB::UPDATE_TIME, $update_time, ">"));

        // 获取sql指令
        $fieldA = $tableA.TTDB::NOTIFY_ID.' as '.TTDB::LOCAL_MSG_ID
            .','.$tableA.TTDB::GROUP_ID
            .','.$tableA.TTDB::NOTIFY_SENDER_ID." as ".TTDB::BLUE_GROUP_MASTER
            .','.$tableA.TTDB::STATUS
            .','.$tableA.TTDB::UPDATE_TIME
            .','.$tableA.TTDB::CREATE_TIME;

        $fieldB = $tableB.TTDB::USER_NICK_NAME;

        $fieldC = $tableC.TTDB::BLUE_GROUP_USERS." as ".TTDB::LOCAL_INVITE_USERS
            .','.$tableC.TTDB::BLUE_GROUP_UNIQUE;

        $sql = 'select '.$fieldA.','.$fieldB.','.$fieldC;

        $sql .= ' from '.TTDB::TABLE_BLUE_NOTIFY.' as a'
            .' left join '.TTDB::TABLE_USER.' b on (a.sender_id = b.user_id)'
            .' left join '.TTDB::TABLE_BLUE_GROUP.' c on (a.group_id = c.group_id)';

        $sql .= ' where '.$where;
        $sql .= " order by a.update_time asc limit ".TTDBConst::RECORD_LIMIT;

        $notifyInfo = Db::inst()->query($sql);

        self::debugSql($notifyInfo, __LINE__);

        return $notifyInfo;
    }

    // 获取指定发送者的蓝牙群组邀请
    public static function getBlueGroupInviteById($msg_id) {

        $inviteInfoList = Db::inst()->select()
            ->from(TTDB::TABLE_BLUE_NOTIFY)
            ->where(self::where(TTDB::NOTIFY_ID, $msg_id))
            ->query();

        self::debugSql($inviteInfoList, __LINE__);

        if(TTPublic::getRecordCount($inviteInfoList) <= 0) {
            return null;
        }

        return $inviteInfoList[0];
    }

    // 更新邀请状态
    public static function updateBlueGroupInviteStatus($inviteId, $status) {

        try {
            $ret = Db::inst()->update(TTDB::TABLE_BLUE_NOTIFY)
                ->cols(self::cols([
                    TTDB::STATUS => $status,
                    TTDB::UPDATE_TIME => TTPublic::getDoubleTime()
                ]))
                ->where(self::where(TTDB::NOTIFY_ID, $inviteId))
                ->query();

            self::debugSql($ret, __LINE__);

            $ret = $ret > 0 ? TTDBConst::OK : TTDBConst::FAILED;
        } catch(Exception $ex) {
            PhpLog::Log("Exception[".__LINE__."]".$ex->getMessage());
            $ret = TTDBConst::FAILED;
        }

        return $ret;
    }

    public static function getBlueGroupMemberIds($group_id, $except_user_id) {
        try {
            $userIds = Db::inst()->select(TTDB::USER_ID)
                ->from(TTDB::TABLE_BLUE_GR)
                ->where(self::where(TTDB::GROUP_ID, $group_id))
                ->where(self::where(TTDB::USER_ID, $except_user_id, "<>"))
                ->column();
        }catch (Exception $ex) {
            $userIds = [];
            PhpLog::Error("Exception[".__LINE__."]".$ex->getMessage());
        }

        self::debugSql($userIds, __LINE__);

        return $userIds;
    }

    public static function getBlueGroupInfoByUser($user_id) {

        $sql = "select b.group_id,b.unique,b.master,b.users,b.update_time,b.create_time"
            ." from ".TTDB::TABLE_BLUE_GR." as a"
            ." left join ".TTDB::TABLE_BLUE_GROUP." b on (a.group_id = b.group_id)"
            ." and ".self::where("a.".TTDB::USER_ID, $user_id)
            ." and ".self::where("a.".TTDB::STATUS, TTDBConst::STATUS_NORMAL)
            ." order by a.update_time desc limit 1";

        $groupInfo = Db::inst()->query($sql);

        self::debugSql($groupInfo, __LINE__);

        if(TTPublic::getRecordCount($groupInfo) <= 0) {
            return null;
        }

        return $groupInfo[0];
    }

}