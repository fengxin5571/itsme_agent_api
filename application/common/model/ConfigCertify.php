<?php

namespace app\common\model;

use think\Model;
use redis\RedisPackage;

/**
 * 认证项模型
 */
class ConfigCertify extends Model
{
    public static function getCertifyId($code){
        $info = self::get(["code"=>$code]);
        return $info->id;
    }

    /**
     * 添加用户认证身价分
     */
    public static function addUserCertifyWorth($userId,$certifyId){
        $certifyInfo = ConfigSocialStatus::getConfigSocialStatusCertify($certifyId);
        $data['user_id'] = $userId;
        $data['worth'] = $certifyInfo['worth'];
        $data['type'] = 5;
        $data['type_id'] = $certifyInfo['certify_id'];
        $res = UserWorthLog::addUserWorthLog($data);
        return $res;
    }
}
