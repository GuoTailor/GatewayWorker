<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/7
 * Time: 下午5:25
 */

namespace Common;

class TTProfile
{
    const FILE_PATH = "/data/wwwroot/data/genesis/";
    const HTTP_PATH = "http://data.trantor.top/genesis/";

    const AVATAR_FILE_PATH = self::FILE_PATH."avatar/";
    const AVATAR_HTTP_PATH = self::HTTP_PATH."avatar/";

    const FIND_FRIEND_LIMIT = 50; // 查找好友最大数量

    const FIND_CLUB_LIMIT = 100; // 查找俱乐部最大数量

    const GROUP_INVITE_MEMBER_LIMIT = 50; // 群组邀请成员最大数量

    const NICK_NAME_DEFAULT_PREFIX = "VIMOTO_";

    const LOCATION_FILE_PATH = self::FILE_PATH."location/";
    const LOCATION_HTTP_PATH = self::HTTP_PATH."location/";

    const UPLOAD_LOCATION_PERIOD = 2000; // 定位上传周期：单位（毫秒）
    const UPLOAD_REAL_PERIOD = 200; // 实时定位上传周期：单位（毫秒）

    const DIR_MAX_NUM = 1000;

    const SIGNAL_PERIOD = 30; // ping周期(单位：秒)
    const SIGNAL_COUNT = 5; // 每个周期发送数据包次数
    const SIGNAL_DELAY = 500; // 信号弱的延时值(单位：毫秒)
}