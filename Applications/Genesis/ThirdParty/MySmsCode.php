<?php

namespace ThirdParty;

use Tools\PhpLog;

/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2018/2/5
 * Time: 下午3:10
 */
class MySmsCode
{
    const VERIFY_OK = 200;

    const API = "https://webapi.sms.mob.com/sms/verify";
    const APP_KEY = "222ba64952340";
    const ZONE = "86";

    /**
     * 发起一个post请求到指定接口
     *
     * @param string $api 请求的接口
     * @param array $params post参数
     * @param int $timeout 超时时间
     * @return string 请求结果
     */
    private static function postRequest($api, array $params = array(), $timeout = 30 ) {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $api );
        // 以返回的形式接收信息
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        // 设置为POST方式
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
        // 不验证https证书
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
            'Accept: application/json',
        ) );
        // 发送数据
        $response = curl_exec( $ch );
        // 不要忘记释放资源
        curl_close( $ch );
        return $response;
    }

    public static function verify($mobile, $code) {

        // qinwangwei for test
        if(substr($mobile, 0, 7) == "1066766"
            || $mobile == "13667662518" || $mobile == "18628283188") {
            return MySmsCode::VERIFY_OK;
        }

        // 发送验证码
        $response = self::postRequest( self::API, array(
            'appkey' => self::APP_KEY,
            'phone' => $mobile,
            'zone' => self::ZONE,
            'code' => $code,
        ) );

        PhpLog::Log("MySmsCode $mobile, $code->$response");

        $result = json_decode($response, true);

        return $result["status"];
    }

    public static function getMessage($status) {

        switch ($status) {
            case self::VERIFY_OK:
                $message = "验证成功";
                break;

            case 405:
                $message = "AppKey为空";
                break;

            case 406:
                $message = "AppKey无效";
                break;

            case 456:
                $message = "国家代码或手机号码为空";
                break;

            case 457:
                $message = "手机号码格式错误";
                break;

            case 466:
                $message = "请求校验的验证码为空";
                break;

            case 467:
                $message = "请求校验验证码频繁";
                break;

            case 468:
                $message = "验证码错误";
                break;

            case 474:
                $message = "没有打开服务端验证开关";
                break;

            default:
                $message = "未知";
                break;
        }

        return $message;
    }

}