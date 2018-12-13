<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/11/6
 * Time: 下午2:54
 */

namespace Common;

class TTCode
{

    // 基本
    const TT_SUCCESS = 0; // 成功
    const TT_FAILED =  1; // 失败
    const TT_DB_FAILED = 2; // 数据操作失败
    const TT_PARA_ERR = 3; // 缺少参数
    const TT_INVALID_PARAM = 4; // 参数无效
    const TT_INVALID_DATA = 5; // 数据不符合规定
    const TT_DB_NOT_CHANGE = 6; // 数据未更新
    const TT_NOT_SUPPORT = 7; // 接口未完成
    const TT_SAVE_IMAGE_ERR = 8; // 保存图片错误
    const TT_UPLOAD_IMAGE_ERR = 9; // 上传图片错误
    const TT_SECURE_ERR = 10; // 校验错误

    // 用户
    const TT_VERIFY_FAILED = 101; // 短信校验失败
    const TT_NOT_USER = 102; // 用户不存在
    const TT_EXIST_USER = 103; // 用户存在
    const TT_NOT_LOGIN = 104; // 未登录
    const TT_PASSWORD_ERR = 105; // 密码错误
    const TT_THIRD_LOGIN_TYPE_ERR = 106; // 三方登录类型错误
    const TT_BINDING_OTHER_MOBILE = 107; // 已绑定其他手机号
    const TT_LOGIN_INVALID = 108; // 登录失效，请重新登录
    const TT_OTHER_DEVICE_LOGIN = 109; // 在其他设备登录
    const TT_EXIST_NICKNAME = 110; // 用户昵称已经存在
    const TT_EMPTY_NICKNAME = 111; // 用户昵称不能为空

    // 好友
    const TT_ALREADY_FRIEND = 201; // 已经是好友
    const TT_ADD_SELF_FRIEND = 202; // 不能添加自己为好友
    const TT_NOT_FRIEND = 203; // 不是好友
    const TT_NO_INVITE = 204; // 无邀请
    const TT_ALREADY_INVITE = 205; // 重复邀请

    // 群组
    const TT_ALREADY_GROUP_MEMBER = 301; // 已经是群组成员　
    const TT_NO_GROUP = 302; // 没有找到群组
    const TT_NOT_GROUP_MEMBER = 303; // 不是群组成员
    const TT_IS_GROUP_MASTER = 304; // 是群主
    const TT_NOT_GROUP_MASTER = 305; // 不是群主
    const TT_ALREADY_GROUP = 306; // 当前有正在骑行的群
    const TT_GROUP_IS_END = 307; // 骑行已结束
    const TT_GROUP_IS_FULL = 308; // 群组已满
    const TT_GROUP_IS_OUT = 309; // 不在骑行
    const TT_GROUP_NOT_SHARE_LOCATION = 310; // 未开启位置共享

    // 对讲
    const TT_INTERCOM_HAS_USER = 401; // 其他用户在对讲
    const TT_INTERCOM_NO_AUTH = 402; // 无抢麦权限
    const TT_INTERCOM_FAILED = 403; // 抢麦失败

    public static function getReason($code) {

        switch ($code) {

            // ------------------ 基本 ------------------ //

            case TTCode::TT_SUCCESS:
                $reason = "成功";
                break;

            case TTCode::TT_FAILED:
                $reason = "失败";
                break;

            case TTCode::TT_DB_FAILED:
                $reason = "数据操作失败";
                break;

            case TTCode::TT_PARA_ERR:
                $reason = "缺少参数";
                break;

            case TTCode::TT_INVALID_PARAM:
                $reason = "参数无效";
                break;

            case TTCode::TT_INVALID_DATA:
                $reason = "数据不符合规定";
                break;

            case TTCode::TT_DB_NOT_CHANGE:
                $reason = "数据未更新";
                break;

            case TTCode::TT_NOT_SUPPORT:
                $reason = "接口未完成";
                break;

            case TTCode::TT_SAVE_IMAGE_ERR:
                $reason = "保存图片错误";
                break;

            case TTCode::TT_UPLOAD_IMAGE_ERR:
                $reason = "上传图片错误";
                break;

            case TTCode::TT_SECURE_ERR:
                $reason = "校验错误";
                break;

            // ------------------ 用户 ------------------ //

            case TTCode::TT_VERIFY_FAILED:
                $reason = "短信校验码不正确";
                break;

            case TTCode::TT_NOT_USER:
                $reason = "用户不存在";
                break;

            case TTCode::TT_EXIST_USER:
                $reason = "用户已经存在";
                break;

            case TTCode::TT_NOT_LOGIN:
                $reason = "未登录";
                break;

            case TTCode::TT_PASSWORD_ERR:
                $reason = "密码错误";
                break;

            case TTCode::TT_THIRD_LOGIN_TYPE_ERR:
                $reason = "三方登录类型错误";
                break;

            case TTCode::TT_BINDING_OTHER_MOBILE:
                $reason = "已绑定其他手机号";
                break;

            case TTCode::TT_LOGIN_INVALID:
                $reason = "登录失效，请重新登录";
                break;

            case TTCode::TT_OTHER_DEVICE_LOGIN:
                $reason = "您的账号已经在其他设备上登录";
                break;

            case TTCode::TT_EXIST_NICKNAME:
                $reason = "用户昵称已经存在";
                break;

            case TTCode::TT_EMPTY_NICKNAME:
                $reason = "用户昵称不能为空";
                break;

            // ------------------ 好友 ------------------ //

            case TTCode::TT_ALREADY_FRIEND:
                $reason = "已经是好友";
                break;

            case TTCode::TT_ADD_SELF_FRIEND:
                $reason = "不能添加自己为好友";
                break;

            case TTCode::TT_NOT_FRIEND:
                $reason = "不是好友";
                break;

            case TTCode::TT_NO_INVITE:
                $reason = "无邀请";
                break;

            case TTCode::TT_ALREADY_INVITE:
                $reason = "重复邀请";
                break;

            // ------------------ 群组 ------------------ //

            case TTCode::TT_ALREADY_GROUP_MEMBER:
                $reason = "已经是群组成员";
                break;

            case TTCode::TT_NO_GROUP:
                $reason = "没有找到群组";
                break;

            case TTCode::TT_NOT_GROUP_MEMBER:
                $reason = "不是群组成员";
                break;

            case TTCode::TT_IS_GROUP_MASTER:
                $reason = "是群主";
                break;

            case TTCode::TT_NOT_GROUP_MASTER:
                $reason = "不是群主";
                break;

            case TTCode::TT_ALREADY_GROUP:
                $reason = "当前有正在骑行的群";
                break;

            case TTCode::TT_GROUP_IS_END:
                $reason = "骑行已结束";
                break;

            case TTCode::TT_GROUP_IS_FULL:
                $reason = "群组已满";
                break;

            case TTCode::TT_GROUP_IS_OUT:
                $reason = "不在骑行";
                break;

            case TTCode::TT_GROUP_NOT_SHARE_LOCATION:
                $reason = "未开启位置共享";
                break;

            // ------------------ 对讲 ------------------ //

            case TTCode::TT_INTERCOM_HAS_USER:
                $reason = "其他用户正在对讲";
                break;

            case TTCode::TT_INTERCOM_NO_AUTH:
                $reason = "没有权限";
                break;

            case TTCode::TT_INTERCOM_FAILED:
                $reason = "抢麦失败";
                break;

            default:
                $reason = "其他错误";
                break;
        }

        return $reason;
    }


}