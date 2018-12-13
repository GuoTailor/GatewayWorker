<?php

/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/1
 * Time: 下午7:16
 */

namespace Tools;
$f = null;
class PhpLog
{
    private static function LogFile($log, $file) {
        $time = date("Y-m-d H:i:s");
        file_put_contents($file, $time." ".$log."\r\n\r\n",FILE_APPEND);
    }

    public static function println($TAG ,$log) {
        $f=self::lock_open();//创建锁
        self::lock_lock($f);//锁定

        $time = date("Y-m-d H:i:s");
        print $time." ".$TAG.": ";
        if ($log == null)
            print "null";
        else
            print_r($log);
        print "<<\r\n";

        self::lock_unlock($f);//解锁
        self::lock_close($f);//删除锁
    }

    /**
     * 创建一个锁
     * @return [type] [description]
     */
    static function lock_open(){
        global $f;
        if ($f == null)
            $f = fopen("flock.xhxx","w+");
        return $f;
    }

    /**
     * 销毁一个锁
     * @param  [type] $f [description]
     * @return [type]    [description]
     */
    static function lock_close($f){
        fclose($f);
        unlink("flock.xhxx");
    }

    /**
     * 进入锁定
     * @param  [type] $f [description]
     * @return [type]    [description]
     */
    static function lock_lock($f){
        flock($f,LOCK_EX);
    }

    /**
     * 退出锁定
     * @param  [type] $f [description]
     * @return [type]    [description]
     */
    static function lock_unlock($f){
        flock($f,LOCK_UN);
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