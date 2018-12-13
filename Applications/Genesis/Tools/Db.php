<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/12/21
 * Time: 上午9:11
 */

namespace Tools;

use Workerman\MySQL\Connection;

class Db
{
    public static function inst() {

        static $connect;

        if($connect == null) {
            $connect = new Connection("127.0.0.1", 3306,
                "root", "Motalk2016", "tt_genesis");
        }

        return $connect;
    }
}