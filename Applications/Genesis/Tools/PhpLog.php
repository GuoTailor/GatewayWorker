<?php

/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/1
 * Time: 下午7:16
 */

namespace Tools;

class PhpLog
{
    private static function LogFile($log, $file) {
        $time = date("Y-m-d H:i:s");
        file_put_contents($file, $time." ".$log."\r\n\r\n",FILE_APPEND);
    }

    public static function println($massage ,$log) {
        $time = date("Y-m-d H:i:s");
        print_r($time." ".$massage.": ");
        print_r($log);
        print_r("\r\n");
    }

    public static function Log($log) {
        self::LogFile($log, "log/log".date("Ymd").".txt");
    }

    public static function Sql($log) {
        self::LogFile($log, "log/sql".date("Ymd").".txt");
    }

    public static function Task($log) {
        self::LogFile($log, "log/task".date("Ymd").".txt");
    }

    public static function Redis($log) {
        self::LogFile($log, "log/redis".date("Ymd").".txt");
    }

    public static function Error($log) {
        self::LogFile($log, "log/error".date("Ymd").".txt");
    }

    public static function hex_dump($data, $newline="n") {
        static $from = '';
        static $to = '';

        static $width = 16; # number of bytes per line

        static $pad = '.'; # padding for non-visible characters

        if ($from==='')
        {
            for ($i=0; $i<=0xFF; $i++)
            {
                $from .= chr($i);
                $to .= ($i >= 0x20 && $i <= 0x7E) ? chr($i) : $pad;
            }
        }

        $hex = str_split(bin2hex($data), $width*2);
        $chars = str_split(strtr($data, $from, $to), $width);

        $offset = 0;
        foreach ($hex as $i => $line)
        {
            self::Log(sprintf('%6X',$offset).' : '.implode(' ', str_split($line,2)) . ' [' . $chars[$i] . ']' . $newline);
            $offset += $width;
        }
    }

}