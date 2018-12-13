<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2018/2/3
 * Time: 上午9:33
 */

namespace model;

use Tools\PhpLog;

class TTDeviceControl
{
    // 1. EXIT
    // 2. IDLE
    // 3. SELF
    // 4. OTHER_0
    // 5. OTHER_1
    public static function get($type, $manage) {

        PhpLog::Log("TTDeviceControl $type,$manage");

        $cmd = null;

        switch ($type) {

            // 对讲模式1，2，4
            case "1":
            case "2":
            case "4":
                if($manage) {
                    $cmd = '{"1": [0, 0],"2": [3, 0],"3": [3, 2],"4": [3, 0],"5": [3, 3]}';
                } else {
                    $cmd = '{"1": [0, 0],"2": [3, 0],"3": [3, 2],"4": [3, 1],"5": [3, 3]}';
                }
                break;

            // 对讲模式3
            case "3":
                if($manage) {
                    $cmd = '{"1": [0, 0],"2": [3, 0],"3": [3, 2],"4": [3, 1],"5": [3, 3]}';
                } else {
                    $cmd = '{"1": [0, 0],"2": [2, 0],"3": [2, 0],"4": [2, 0],"5": [2, 0]}';
                }
                break;

            default:
                break;
        }

        return empty($cmd) ? null : json_decode($cmd);
    }

}