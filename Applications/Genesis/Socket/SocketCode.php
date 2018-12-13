<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/12/18
 * Time: 下午6:37
 */

namespace Socket;

class SocketCode
{
    // 用户广播
    const USER_LOGIN_IND = 1001; // 用户登录
    const USER_LOGOUT_IND = 1002; // 用户登出
    const USER_OTHER_DEVICE_LOGIN_IND = 1003; // 用户在其他设备登录

    // 好友广播
    const FRIEND_INVITE_IND = 2001; // 好友邀请
    const FRIEND_RESPONSE_IND = 2002; // 好友回应
    const FRIEND_DELETE_IND = 2003; // 好友删除

    // 群组广播
    const GROUP_JOIN_IND = 3001; // 输入群组口令加入群组
    const GROUP_INVITE_IND = 3002; // 加入群组邀请
    const GROUP_RESPONSE_IND = 3003; // 加入群组应答
    const GROUP_EXIT_IND = 3004; // 退出群组
    const GROUP_DELETE_IND = 3005; // 删除群组
    const GROUP_CHANGED_IND = 3006; // 群组变更
    const GROUP_ENTER_IND = 3007; // 进入群组
    const GROUP_LEAVE_IND = 3008; // 离开群组
    const GROUP_ACQUIRE_INTERCOM_IND = 3009; // 申请对讲
    const GROUP_RELEASE_INTERCOM_IND = 3010; // 释放对讲
    const GROUP_INVITE_OTHER_IND = 3011; // 邀请其他人员进入群组
    const GROUP_LOCATION_CHANGE_IND = 3012; // 定位变化通知
    const GROUP_VOICE_ID_CHANGE_IND = 3013; // 语音ID变化通知
    const GROUP_NEW_RECORD_IND = 3014; // 新骑行记录通知
    const GROUP_CANCEL_INVITE_IND = 3015; // 取消加入群组邀请
    const GROUP_SHARE_LOCATION_CHANGE_IND = 3016; // 用户修改位置共享开关参数

    // 蓝牙群组广播
    const BLUE_GROUP_INVITE_IND = 4001; // 加入蓝牙群组邀请
    const BLUE_GROUP_INVITE_RESPONSE_IND = 4002; // 加入蓝牙群组邀请响应

    // 用户登录前的请求
    const USER_REGISTER_REQ = 1; // 注册
    const USER_LOGIN_REQ = 2; // 登录
    const USER_THIRD_LOGIN_REQ = 3; // 三方登录
    const USER_THIRD_REGISTER_REQ = 4; // 三方注册
    const USER_RESET_PASSWORD_REQ = 5; // 重置密码
    const USER_VERIFY_SMSCODE_REQ = 6; // 检测短信验证码
    const USER_CHECK_USER_REQ = 7; // 检测用户

    //  登录后的接口
    // [ +++++++++++++++++
    const IN_LOGIN_REQ_BEGIN = 100; // 完成登录的起始ID

    // 用户请求
    const USER_LOGOUT_REQ = 101; // 登出
    const USER_UPLOADAVATAR_REQ = 102; // 上传个人头像
    const USER_GET_INFORMATION_REQ = 103; // 获取个人信息
    const USER_MODIFY_INFORMATION_REQ = 104; // 修改个人信息
    const USER_FIND_CLUB_REQ = 105; // 查找俱乐部
    const USER_FIND_USER_REQ = 106; // 查找用户
    const USER_GET_USERS_REQ = 107; // 获取多个用户的信息
    const USER_GET_MOBILE_LIST_NOT_FRIEND_REQ = 108; // 获取未添加好友的手机用户
    const USER_UPLOAD_DEVICE_TOKEN = 109; // 上传设备Token
    const USER_THIRD_BINDING= 110; // 绑定
    const USER_THIRD_UNBINDING = 111; // 取消绑定
    const USER_EXIST_NICKNAME_REQ = 112; // 检测昵称是否合法

    // 好友请求
    const FRIEND_ADD_FRIEND_REQ = 201; // 添加好友
    const FRIEND_DELETE_FRIEND_REQ = 202; // 删除好友
    const FRIEND_GET_FRIEND_LIST_REQ = 203; // 获取好友列表
    const FRIEND_GET_INVITE_REQ = 204; // 获取邀请信息
    const FRIEND_RESPONSE_INVITE_REQ = 205; // 响应邀请
    const FRIEND_REMARK_USER_INFO_REQ = 206; // 备注用户

    // 群组请求
    const GROUP_CREATE_REQ = 301; // 创建群组
    const GROUP_DELETE_REQ = 302; // 删除群组
    const GROUP_EXIT_REQ = 303; // 退出群组
    const GROUP_MODIFY_REQ = 304; // 修改群组
    const GROUP_ADD_USERS_REQ = 305; // 添加用户到群组
    const GROUP_GET_INFORMATION_REQ = 306; // 获取群组信息
    const GROUP_GET_RIDING_RECORD_REQ = 307; // 获取骑行记录
    const GROUP_GET_INVITE_REQ = 308; // 获取邀请信息
    const GROUP_RESPONSE_INVITE_REQ = 309; // 响应邀请
    const GROUP_JOIN_GROUP_REQ = 310; // 输入群组口令加入群
    const GROUP_ENTER_REQ = 311; // 进入群组
    const GROUP_LEAVE_REQ = 312; // 离开群组
    const GROUP_GET_RIDING_GROUP_REQ = 313; // 获取当前骑行群信息
    const GROUP_DEL_RIDING_RECORD_REQ = 314; // 删除骑行记录

    // 进入群组后的接口 [这些接口需要检测用户是否进入群组]
    // [ +++++++++++++++++
    const IN_GROUP_REQ_BEGIN = 350; // 进入群组起始ID

    // 对讲接口
    const GROUP_ACQUIRE_INTERCOM_REQ = 351; // 申请对讲
    const GROUP_RELEASE_INTERCOM_REQ = 352; // 释放对讲
    const GROUP_GET_MEMBERS_REQ = 353; // 获取群组成员
    const GROUP_UPLOAD_AVATAR_REQ = 354; // 上传群头像
    const GROUP_GET_ONLINE_REQ = 355; // 获取群组在线成员
    const GROUP_ENTER_REAL_LOCATION = 356; // 进入实时定位
    const GROUP_EXIT_REAL_LOCATION = 357; // 退出实时定位
    const GROUP_UPLOAD_LOCATION = 358; // 上传最新定位
    const GROUP_UPLOAD_VOICE_ID = 359; // 上传语音ID
    const GROUP_SET_SHARE_LOCATION_REQ = 360; // 设置是否共享定位信息

    const IN_GROUP_REQ_END = 399; // 进入群组结束ID
    // +++++++++++++++++ ]

    // 设备接口
    const DEVICE_UPLOAD_MAIN = 401; // 上传主设备信息
    const DEVICE_UPLOAD_CONTROLER = 402; // 上传控制器信息
    const DEVICE_UPLOAD_HEADSET = 403; // 上传耳机信息
    const DEVICE_GET_MAIN_LIST = 404; // 获取主设备信息

    // 蓝牙对讲群组请求
    const BLUE_GROUP_CREATE_REQ = 501; // 创建群组
    const BLUE_GROUP_GET_INVITE_REQ = 502; // 获取邀请
    const BLUE_GROUP_SET_INVITE_REQ = 503; // 响应邀请
    const BLUE_GROUP_GET_GROUP_REQ = 504; // 获取当前骑行

    const IN_LOGIN_REQ_END = 999; // 完成登录的结束ID
    // +++++++++++++++++ ]

}