<?php

namespace app\common\model;

use think\Model;
use redis\RedisPackage;

/**
 * 会员标签模型
 */
class ConfigUserTag extends Model
{
    public static function getTagId($code){
        $info = self::get(["code"=>$code]);
        return $info->id;
    }

    /**
     * 添加用户标签身价分
     */
    public static function addUserTagWorth($userId,$tagId){
        $tagInfo = ConfigSocialStatus::getConfigSocialStatus($tagId);
        $data['user_id'] = $userId;
        $data['worth'] = $tagInfo['worth'];
        $data['type'] = 1;
        $data['type_id'] = $tagInfo['tag_id'];
        $res = UserWorthLog::addUserWorthLog($data);
        return $res;
    }
}
