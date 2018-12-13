<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/12/21
 * Time: 下午2:18
 */

namespace Socket;


class SocketConst
{
    // 请求主要参数
    const REQ_MSG_CODE = "msg_code";

    // 通知主要参数
    const IND_SENDOR = "sendor";
    const IND_MSG_CODE = "code";
    const IND_DATA = "data";

    // 响应主要参数
    const RSP_CODE = "code";
    const RSP_MESSAGE = "message";
    const RSP_DATA = "data";

    // 其他参数
    const GROUP_ID = "group_id";
    const ACCESS_TOKEN = "access_token";
    const LOCATION = "location";
    const MAX_SPEED = "max_speed";

}