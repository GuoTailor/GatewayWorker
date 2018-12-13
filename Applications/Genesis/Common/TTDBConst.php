<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/7
 * Time: 上午10:19
 */

namespace Common;

class TTDBConst
{
    const NO_USER_ID = 0; // 无用户的ID

    const SEX_SECRET = 0;
    const SEX_MAN = 1;
    const SEX_MALE = 2;

    // 结果
    const OK = 1;
    const FAILED = 0;

    // 三方登陆类型
    const THIRD_QQ = "qq";
    const THIRD_WEIXIN = "weixin";
    const THIRD_WEIBO = "weibo";

    // 检测用户类型
    const CHECK_USER_TYPE_REGISTER = "register"; // 注册
    const CHECK_USER_TYPE_RESET = "reset"; // 复位密码
    const CHECK_USER_TYPE_THIRD_REGISTER = "third_register"; // 三方注册

    // 消息处理状态
    const MSG_PROCESS_INIT = 1; // 初始状态
    const MSG_PROCESS_AGREE = 2; // 同意邀请
    const MSG_PROCESS_REFUSE = 3; // 拒绝邀请
    const MSG_PROCESS_IGNORE = 4; // 忽略邀请
    const MSG_PROCESS_REMOVE = 5; // 自动清除

    //1. 通讯录 2. 查找手机号 3. 查找昵称 4. 地区 5. 车型 6.俱乐部
    const ADD_TYPE_CONTACTS = 1;
    const ADD_TYPE_MOBILE = 2;
    const ADD_TYPE_NICK_NAME = 3;
    const ADD_TYPE_AREA = 4;
    const ADD_TYPE_CAR = 5;
    const ADD_TYPE_CLUB = 6;

    // 群组类型： 1、好友群组 2、俱乐部群组 3、巡游群组 4、对讲机骑行
    const GROUP_TYPE_FRIEND = 1;
    const GROUP_TYPE_CLUB = 2;
    const GROUP_TYPE_CRUISE = 3;
    const GROUP_TYPE_INTERCOM = 4;

    const GROUP_FRIEND_LIMIT = 8;
    const GROUP_CLUB_LIMIT = 20;
    const GROUP_CRUISE_LIMIT = 10000;
    const GROUP_INTERCOM_LIMIT = 10000;

    // 记录状态　
    const STATUS_DELETE = 0;
    const STATUS_NORMAL = 1;

    // 通知信息类型 tt_notification 1: 添加好友 2. 加入群组
    const NOTIFICATION_ADD_FRIEND = 1;
    const NOTIFICATION_JOIN_GROUP = 2;

    //骑行状态 0：未开始 1：骑行中 2：骑行暂停 3：骑行结束
    const RIDING_STATUS_INIT = 0;
    const RIDING_STATUS_START = 1;
    const RIDING_STATUS_PAUSE = 2;
    const RIDING_STATUS_END = 3;

    // 数据最大限制
    const RECORD_LIMIT = 50;

    // 记录状态　
    const RIDING_RECORD_STATUS_INIT = 0;
    const RIDING_RECORD_STATUS_START = 1; // 开始
    const RIDING_RECORD_STATUS_FINISH = 2; // 结束
    const RIDING_RECORD_STATUS_DELETE = 3; // 删除
    const RIDING_RECORD_STATUS_PAUSE = 4; // 暂停(用户离开骑行，群组暂停骑行)
    const RIDING_RECORD_STATUS_END = 5; // 完成

    // 是否分享定位
    const SHARE_LOCATION_ON = 1;
    const SHARE_LOCATION_OFF = 2;

}