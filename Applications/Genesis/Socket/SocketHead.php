<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/12/27
 * Time: 下午3:38
 */

namespace Socket;


use Data\BaseConstants;

class SocketHead
{
    const HEAD_SIZE = 24;
    const VERSION = 200;

    const H1_HEAD = "head"; // 头大小
    const H2_ID = "id"; // open_id
    const H3_VER = "ver"; // 版本
    const H4_CMD = "cmd"; // 命令ID
    const H5_REQ = "req"; // 请求ID
    const H6_BODY = "body"; // 数据大小

    public static function pack($headArray) {
        $headMessage = pack("NNNNNN", $headArray[self::H1_HEAD],
            $headArray[self::H2_ID], $headArray[self::H3_VER],
            $headArray[self::H4_CMD], $headArray[self::H5_REQ], $headArray[self::H6_BODY]);

        return $headMessage;
    }

    public static function getDefault() {
        return [SocketHead::H1_HEAD => SocketHead::HEAD_SIZE,
            SocketHead::H2_ID => 0,
            SocketHead::H3_VER => SocketHead::VERSION,
            SocketHead::H4_CMD => BaseConstants::PUSHMSG_CMDID,
            SocketHead::H5_REQ => 0,
            SocketHead::H6_BODY => 0];
    }

    public static function unpack($headMessage) {
        $headArray = unpack("N".self::H1_HEAD."/N".self::H2_ID."/N".self::H3_VER
            ."/N".self::H4_CMD."/N".self::H5_REQ."/N".self::H6_BODY, $headMessage);

        if(!isset($headArray[self::H1_HEAD]) || $headArray[self::H1_HEAD] != self::HEAD_SIZE
            || !isset($headArray[self::H3_VER]) || $headArray[self::H3_VER] != self::VERSION) {

            return null;
        }

        return $headArray;
    }

}