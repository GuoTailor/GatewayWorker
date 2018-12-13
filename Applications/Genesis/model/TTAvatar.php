<?php
/**
 * Created by PhpStorm.
 * User: wangweiqin
 * Date: 2017/12/15
 * Time: 上午9:24
 */

namespace model;

use Common\TTCode;
use Common\TTDB;
use Common\TTDBConst;
use Common\TTDbFun;
use Common\TTProfile;
use Tools\PhpLog;

class TTAvatar
{
    const TYPE_USER = 1;
    const TYPE_GROUP = 2;

    const FILE_EXT = ".jpg";

    const TYPE_USER_NAME = "user";
    const TYPE_GROUP_NAME = "group";

    private static function getFile($type, $id) {

        $index = ceil($id / TTProfile::DIR_MAX_NUM);
        $index_path = TTProfile::AVATAR_FILE_PATH.$type."/".$index;

        // 创建索引目录
        if(!file_exists($index_path)) {
            mkdir($index_path);
        }

        $key = TTPublic::makeTempFile($id);

        // 返回该群组的头像文件
        return $index_path."/".$id."-".$key."-hd".self::FILE_EXT;
    }

    public static function uploadUserAvatar($user_id, $access_token, $imageFileHd) {

        $file = self::getFile(self::TYPE_USER_NAME, $user_id);
        $ret = rename($imageFileHd, $file);

        return self::uploadAvatar($access_token, $file, self::TYPE_USER, null);
    }

    public static function uploadGroupAvatar($group_id, $access_token, $imageFileHd) {
        $file = self::getFile(self::TYPE_GROUP_NAME, $group_id);
        $ret = rename($imageFileHd, $file);
        return self::uploadAvatar($access_token, $file, self::TYPE_GROUP, $group_id);
    }

    private static function getThumbnail($avatar_hd_file) {
        return str_replace("-hd.jpg", ".jpg", $avatar_hd_file);
    }

    private static function getHd($avatar_thumbnail_file) {
        return str_replace(".jpg", "-hd.jpg", $avatar_thumbnail_file);
    }

    private static function deleteAvatar($avatar_file) {
        if($avatar_file != null) {
            unlink($avatar_file);
            unlink(self::getHd($avatar_file));
        }
    }

    private static function uploadAvatar($access_token, $imageFileHd, $type, $group_id) {

        // 生成缩略图
        $imageFile = self::getThumbnail($imageFileHd);
        self::mkThumbnail($imageFileHd, 200, 200, $imageFile);

        // 获取image的http url
        $avatarUrl = TTPublic::getAvatarUrl($imageFile);

        // 检测是否登陆
        $myInfo = TTDbFun::getInfoByAccessToken($access_token);
        if($myInfo == null) {
            self::deleteAvatar($imageFile);
            return TTPublic::getResponse(TTCode::TT_LOGIN_INVALID);
        }

        // 获取用户id
        $my_user_id = $myInfo[TTDB::USER_ID];
        $oldAvatarUrl = null;

        // 写入新的头像
        if($type == self::TYPE_USER) {
            $oldAvatarUrl = $myInfo[TTDB::USER_AVATAR_URL];
            $ret = TTDbFun::uploadUserAvatar($access_token, $avatarUrl);
        } else {
            $groupInfo = TTDbFun::getGroupInfo($group_id);
            if($groupInfo == null) {
                self::deleteAvatar($imageFile);
                return TTPublic::getResponse(TTCode::TT_NO_GROUP);
            }
            $oldAvatarUrl = $groupInfo[TTDB::GROUP_AVATAR];

            $ret = TTDbFun::uploadGroupAvatar($group_id, $my_user_id, $avatarUrl);
        }
        if($ret != TTDBConst::OK) {
            self::deleteAvatar($imageFile);
            return TTPublic::getResponse(TTCode::TT_DB_FAILED);
        }

        // 删除之前的头像文件
        if($avatarUrl != $oldAvatarUrl) {
            $oldAvatar = TTPublic::getAvatarFile($oldAvatarUrl);
            self::deleteAvatar($oldAvatar);
        }

        return TTPublic::getResponse(TTCode::TT_SUCCESS,
            array("avatar_url" => $avatarUrl));
    }

    private static function mkThumbnail($src, $width = null, $height = null, $filename = null) {
        if (!isset($width) && !isset($height))
            return false;
        if (isset($width) && $width <= 0)
            return false;
        if (isset($height) && $height <= 0)
            return false;

        $size = getimagesize($src);
        if (!$size)
            return false;

        list($src_w, $src_h, $src_type) = $size;
        $src_mime = $size['mime'];
        switch($src_type) {
            case 1 :
                $img_type = 'gif';
                break;
            case 2 :
                $img_type = 'jpeg';
                break;
            case 3 :
                $img_type = 'png';
                break;
            case 15 :
                $img_type = 'wbmp';
                break;
            default :
                return false;
        }

        if (!isset($width))
            $width = $src_w * ($height / $src_h);
        if (!isset($height))
            $height = $src_h * ($width / $src_w);

        $imagecreatefunc = 'imagecreatefrom' . $img_type;
        $src_img = $imagecreatefunc($src);
        $dest_img = imagecreatetruecolor($width, $height);
        imagecopyresampled($dest_img, $src_img, 0, 0, 0, 0,
            $width, $height, $src_w, $src_h);

        $imagefunc = 'image' . $img_type;
        if ($filename) {
            $imagefunc($dest_img, $filename);
        } else {
            header('Content-Type: ' . $src_mime);
            $imagefunc($dest_img);
        }
        imagedestroy($src_img);
        imagedestroy($dest_img);
        return true;
    }


}