<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/12/20
 * Time: 下午3:16
 */

namespace Socket;

use Common\TTProfile;
use Tools\PhpLog;

class SocketFile
{
    const FILE_EXT = ".jpg";

    public static function getFile($key, $temp) {
        return TTProfile::AVATAR_FILE_PATH.$key."-".$temp."-hd".self::FILE_EXT;
    }

    private static function saveFile($buffer, $full_name) {
        PhpLog::Log("saveFile ".$full_name);
        file_put_contents($full_name, $buffer);
    }

    private static function getJsonLen($data) {

        if($data[0] == '{') {
            $flag = 1;

            for($i = 1; $i < strlen($data); $i++) {
                $item = substr($data, $i, 1);
                if($item == '{') {
                    $flag++;
                } else if($item == '}') {
                    $flag--;
                }

                if($flag == 0) {
                    return $i + 1;
                }
            }
        }

        return 0;
    }

    public static function handleUploadFile($client_id, $mr, $message) {

        $headSize = SocketHead::HEAD_SIZE;
        $bodySize = $mr[SocketHead::H6_BODY];

        $body = substr($message, $headSize, $bodySize);

        // 读取上传文件的参数
        $jsonLen = self::getJsonLen($body);
        $jsonStr = substr($body, 0, $jsonLen);
        $jsonObj = json_decode($jsonStr, true);

        $fileName = self::getFile("temp", $jsonObj[SocketConst::ACCESS_TOKEN]);
        $jsonObj["file"] = $fileName;

        // 保存文件
        $fileData = substr($body, $jsonLen);
        self::saveFile($fileData, $fileName);

        // 处理消息
        SocketEvent::handle($client_id, $mr, json_encode($jsonObj));
    }


}