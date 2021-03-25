<?php

namespace app\common\model;

use think\Model;
use redis\RedisPackage;

/**
 * 身价配置模型
 */
class ConfigSocialStatus extends Model
{
    /**
     * 获取身价配置-tag
     * @param mixed $user
     * @return object
     */
    public static function getConfigSocialStatus($tag_id)
    {
        $redis = new RedisPackage();
        $arr = $redis::$handler->get('ConfigSocialStatus:tag_id-'.$tag_id);
        if(!$arr){
            $arr = self::get(['tag_id' => $tag_id]);
            $arr = json_encode($arr);
            $redis::$handler->set('ConfigSocialStatus:tag_id-'.$tag_id, $arr);
        }
        $arr = json_decode($arr,true);
        return $arr;
    }

    /**
     * 获取身价配置-certify
     */
    public static function getConfigSocialStatusCertify($certify_id){
        $redis = new RedisPackage();
        $arr = $redis::$handler->get('ConfigSocialStatus:certify_id-'.$certify_id);
        if(!$arr){
            $arr = self::get(['certify_id' => $certify_id]);
            $arr = json_encode($arr);
            $redis::$handler->set('ConfigSocialStatus:certify_id-'.$certify_id, $arr);
        }
        $arr = json_decode($arr,true);
        return $arr;
    }
}
