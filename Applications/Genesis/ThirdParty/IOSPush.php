<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2018/3/22
 * Time: 上午10:36
 */

namespace ThirdParty;


use Common\TTDB;
use Common\TTRedis;
use GatewayWorker\Lib\Gateway;
use model\TTPublic;
use Tools\PhpLog;

class IOSPush
{

    const USER_ID = "user_id";
    const TITLE = "title";
    const TEXT = "text";
    const DATA = "data";

    public static function getShowName($user_id, $nick_name) {
        if(empty($nick_name)) {
            return "VIMOTO_".$nick_name;
        }

        return $nick_name;
    }

    public static function push($user_id, $title, $text, $data) {
        $messageObj = [self::USER_ID => $user_id,
            self::TITLE => $title,
            self::TEXT => $text,
            self::DATA => $data];

        TTRedis::pushSendMessage(json_encode($messageObj));

        PhpLog::Task("IOSPush push ".json_encode($messageObj));
    }

    /**
     * 给指定群组发消息
     * @param $group_id => 群组ID
     * @param $title => 标题
     * @param $text => 内容
     * @param $body => 数据
     * @param $exclude_client_id => 排除的client_id
     * @param bool $all
     */
    public static function pushToGroup($group_id, $title, $text, $body, $exclude_client_id, $all = true) {

        PhpLog::Task("pushToGroup[$group_id,$exclude_client_id,$all]:".$body);

        $groupInfo = TTRedis::getGroupInfo($group_id);
        if($groupInfo == null) {
            return;
        }

        $groupMember = $groupInfo[TTDB::LOCAL_GROUP_MEMBERS];
        if(empty($groupMember) || TTPublic::getRecordCount($groupMember) <= 0) {
            return;
        }

        foreach ($groupMember as $user_id) {

            // 检测是否要发送全部群组成员
            if(!$all) {
                $group_id = TTRedis::getUserGroup($user_id);
                if(empty($group_id)) {
                    continue;
                }
            }

            // 发送消息
            self::push($user_id, $title, $text, $body);
        }
    }

    /**
     * 发送消息到用户列表
     * @param $users => 单个用户/多个用户
     * @param $title => 标题
     * @param $text => 内容
     * @param $body => 数据包
     */
    public static function pushToUsers($users, $title, $text, $body) {

        PhpLog::Task("pushToUsers[".json_encode($users)
            .",".$title.",".$text.",".json_encode($body)."]:");

        if(is_array($users)) {
            foreach ($users as $user_id) {
                self::push($user_id, $title, $text, $body);
            }
        } else {
            self::push($users, $title, $text, $body);
        }
    }

    /**
     * @return mixed|null
     */
    public static function pop() {
        $message = TTRedis::popSendMessage();
        if($message != null) {
            return json_decode($message, true);
        }

        return null;
    }

}