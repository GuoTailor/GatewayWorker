<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/12/27
 * Time: 下午4:14
 */

namespace Socket;

use Common\TTProfile;
use Tools\PhpLog;

class SocketCache
{
    private static function getCacheFile($cache_name) {

        $path = TTProfile::AVATAR_FILE_PATH."temp/";

        if(!file_exists($path)) {
            mkdir($path);
        }

        return $path.$cache_name;
    }

    private static function getHead($cache_file) {
        $headMessage = file_get_contents($cache_file, false, null,
            0, SocketHead::HEAD_SIZE);

        return SocketHead::unpack($headMessage);
    }

    private static function getFileSize($cache_file) {

        PhpLog::Log("getFileSize 1: ".$cache_file);

        if(!file_exists($cache_file)) {
            PhpLog::Log("getFileSize 2");
            return 0;
        }

        $fp = fopen($cache_file, "r");
        if($fp == null) {
            PhpLog::Log("getFileSize 3");
            return 0;
        }

        fseek($fp, 0, SEEK_END);
        $fileSize = ftell($fp);
        fclose($fp);

        PhpLog::Log("getFileSize 9: ".$fileSize);

        return $fileSize;
    }

    // 非空： 表示数据已经接收完整
    public static function write($cache_name, $buffer, $append = false) {

        $cache_file = self::getCacheFile($cache_name);

        PhpLog::Log("write 1: ".$cache_file);

        if($append) {
            $headArray = self::getHead($cache_file);
            PhpLog::Log("write 2: ".json_encode($headArray));
            if($headArray != null) {
                file_put_contents($cache_file, $buffer, FILE_APPEND);
                $fileSize = self::getFileSize($cache_file);

                PhpLog::Log("write 3: ".$fileSize);
                if($fileSize >= $headArray[SocketHead::H6_BODY] + SocketHead::HEAD_SIZE) {
                    PhpLog::Log("write 4");
                    return file_get_contents($cache_file);
                }
            }
        } else {
            file_put_contents($cache_file, $buffer);
        }

        return null;
    }

    public static function read($cache_name) {

        $cache_file = self::getCacheFile($cache_name);

        return file_get_contents($cache_file);
    }

    public static function delete($cache_name) {

        $cache_file = self::getCacheFile($cache_name);

        unlink($cache_file);
    }

}