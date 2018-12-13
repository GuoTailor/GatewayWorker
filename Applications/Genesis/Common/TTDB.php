<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/7
 * Time: 上午10:19
 */

namespace Common;

class TTDB
{
    // 数据表名称
    const TABLE_USER = "tt_user";
    const TABLE_THIRD = "tt_third_platform";
    const TABLE_CLUB = "tt_club";
//    const TABLE_DEVICE = "tt_devices";
    const TABLE_UR = "tt_user_relationship";
    const TABLE_NOTIFY = "tt_notification";
    const TABLE_UNI = "tt_user_nickname";
    const TABLE_GROUP = "tt_group";
    const TABLE_GR = "tt_group_relationship";
    const TABLE_RIDE = "tt_riding_record";
    const TABLE_USER_REMARK = "tt_user_remark";
    const TABLE_DEVICE_MAIN = "tt_device_main";
    const TABLE_DEVICE_CONTROLER = "tt_device_controler";
    const TABLE_DEVICE_HEADSET = "tt_device_headset";

    const TABLE_BLUE_GROUP = "tt_blue_group";
    const TABLE_BLUE_GR = "tt_blue_group_relationship";
    const TABLE_BLUE_NOTIFY = "tt_blue_group_notification";

    // 数据表公共字段
    const UPDATE_TIME = "update_time";
    const CREATE_TIME = "create_time";
    const USER_ID = "user_id"; // not change
    const GROUP_ID = "group_id";
    const STATUS = "status";

    // tt_user表字段
    const USER_MOBILE = "mobile"; // not change
    const USER_PASSWORD = "password";
    const USER_DEVICE_NAME = "device_name";
    const USER_NICK_NAME = "nick_name";
    const USER_SEX = "sex";
    const USER_AVATAR_URL = "avatar_url";
    const USER_BIRTHDAY = "birthday";
    const USER_ACCESS_TOKEN = "access_token";
    const USER_MOTO_BRAND = "moto_brand";
    const USER_MOTO_MODEL = "moto_model";
    const USER_PROVINCE = "province";
    const USER_CITY = "city";
    const USER_CLUB_ID = "club_id";
    const USER_USER_SIGNATURE = "user_signature";
    const USER_TOTAL_MILEAGE = "total_mileage";
    const USER_MONTH_MILEAGE = "month_mileage";
    const USER_PLATFORM = "platform";
    const USER_TERMINATOR = "terminator";
    const USER_CONTROLLER = "controller";
    const USER_HEADSET = "headset";
    const USER_LAST_LOGIN_TIME = "last_login_time";
    const USER_LAST_LOGIN_TYPE = "last_login_type";
    const USER_LAST_LOGIN_THIRD_OPEN_ID = "last_login_third_open_id";

    const LOCAL_CLUB_NAME = "club_name";
    const LOCAL_REMARK_NICKNAME = "remark_nickname";
    const LOCAL_REMARK_MOBILE = "remark_mobile";

    const LOCAL_INVITE_STATUS = "invite_status";
    const LOCAL_MSG_ID = "msg_id";
    const LOCAL_INVITE_USERS = "invite_users";
    const LOCAL_MEMBERS_COUNT = "members_count";
    const LOCAL_GROUP_MEMBERS = "group_members";
    const LOCAL_GROUP_MANAGES = "group_manages";
    const LOCAL_MANAGE = "manage";

    const LOCAL_INTERCOM_USER_ID = "intercom_user_id";
    const LOCAL_USER_VOICE_LIST = "user_voice_list";
    const LOCAL_DEVICE_CONTROL = "device_control";
    const LOCAL_VOICE_ID = "voice_id";
    const LOCAL_ACCESSORY = "accessory";
    const LOCAL_LOCATION = "location";
    const LOCAL_LOCATION_UPLOAD_PERIOD = "location_upload_period";
    const LOCAL_REAL_UPLOAD_PERIOD = "real_upload_period";
    const LOCAL_ALLOW_TALK = "allow_talk";
    const LOCAL_ALLOW_INTERCOM = "allow_intercom";
    const LOCAL_MAX_SPEED = "max_speed";
    const LOCAL_TOTAL_TIME = "total_time";
    const LOCAL_SIGNAL_PERIOD = "signal_period";
    const LOCAL_SIGNAL_COUNT = "signal_count";
    const LOCAL_SIGNAL_DELAY = "signal_delay";

    // tt_third
    const THIRD_ID="Id";
    const THIRD_OPENID="openid";
    const THIRD_NICKNAME="nickname";
    const THIRD_SEX="sex";
    const THIRD_PROVINCE="province";
    const THIRD_CITY="city";
    const THIRD_COUNTRY="country";
    const THIRD_HEADIMGURL="headimgurl";
    const THIRD_PLATFORM_TYPE="platform_type";

    // tt_club
    const CLUB_ID = "Id";
    const CLUB_NAME = "name";
    const CLUB_CREATOR = "creator";

    // tt_user_relationship
    const UR_ID = "Id";
    const UR_FRIEND_ID = "friend_id";
    const UR_ADD_TYPE = "add_type";
    const UR_LAST_OPERATOR = "last_operator";

    // tt_notification
    const NOTIFY_ID = "Id";
    const NOTIFY_SENDER_ID = "sender_id";
    const NOTIFY_RECEIVER_ID = "receiver_id";
    const NOTIFY_TYPE = "type";
    const NOTIFY_ADD_TYPE = "add_type";

    //tt_user_nickname
    const UNI_ID = "Id";
    const UNI_FRIEND_ID = "friend_id";
    const UNI_NICKNAME = "nickname";
    const UNI_MOBILE = "mobile";

//    // tt_device
//    const DEVICE_ID = "Id";
//    const UNIQUE_ID = "unique_id";
//    const DEVICE_BDADDR = "bdaddr";
//    const DEVICE_BDADDR1 = "bdaddr1";
//    const DEVICE_CATEGORY = "category";
//    const DEVICE_MODEL = "model";
//    const DEVICE_NAME = "name";
//    const DEVICE_FIRMWARE_VERSION = "firmware_version";
//    const DEVICE_HARDWARE_VERSION = "hardware_version";
//    const DEVICE_SERIAL_NUMBER = "serial_number";
//    const CREATOR = "creator";

    // tt_group
    const GROUP_GROUP_NAME = "group_name";
    const GROUP_AVATAR = "avatar";
    const GROUP_GROUP_TYPE = "group_type";
    const GROUP_MASTER = "master";
    const GROUP_INVITE_CODE = "invite_code";
    const GROUP_NOTICE = "notice";
    const GROUP_RIDING_STATUS = "riding_status";
    const GROUP_RIDING_START_TIME = "riding_start_time";
    const GROUP_RIDING_END_TIME = "riding_end_time";
    const GROUP_GROUP_STATUS = "group_status";
    const GROUP_LAST_ACTIVE_TIME = "last_active_time";
    const GROUP_CREATE_LNG = "create_lng";
    const GROUP_CREATE_LAT = "create_lat";
    const GROUP_ACCESS_CODE = "access_code";
    const GROUP_LEADER = "leader";
    const GROUP_RIDER1 = "rider1";
    const GROUP_RIDER2 = "rider2";
    const GROUP_RIDER3 = "rider3";
    const GROUP_ENDING = "ending";

    // tt_group_relationship
    const GR_ID = "Id";
    const GR_SHARE_LOCATION = "share_location";

    // tt_riding_record
    const RIDE_ID = "Id";
    const RIDE_GROUP_TYPE = "group_type";
    const RIDE_GROUP_AVATAR = "group_avatar";
    const RIDE_START_TIME = "start_time";
    const RIDE_END_TIME = "end_time";
    const RIDE_START_LAT = "start_lat";
    const RIDE_START_LNG = "start_lng";
    const RIDE_START_ADDR = "start_addr";
    const RIDE_END_LAT = "end_lat";
    const RIDE_END_LNG = "end_lng";
    const RIDE_END_ADDR = "end_addr";
    const RIDE_MAX_SPEED = "max_speed";
    const RIDE_TOTAL_TIME = "total_time";
    const RIDE_TOTAL_MILES = "total_miles";
    const RIDE_TOTAL_MEMBER = "total_member";
    const RIDE_LOCATION_URL = "location_url";
    const RIDE_RECORD_STATUS = "record_status";

    // tt_device_main,tt_device_controler,tt_device_headset
    const DEVICE_ID = "device_id";
    const DEVICE_ADDRESS = "address";
    const DEVICE_NAME = "name";
    const DEVICE_VERSION = "version";
    const DEVICE_SERIAL_NUMBER = "serial_number";
    const DEVICE_REMARK = "remark";

    // tt_blue_group
    const BLUE_GROUP_UNIQUE = "unique";
    const BLUE_GROUP_MASTER = "master";
    const BLUE_GROUP_USERS = "users";

}