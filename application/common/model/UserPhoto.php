<?php

namespace app\common\model;

use think\Model;
use redis\RedisPackage;
use think\Db;

/**
 * 会员相册模型
 */
class UserPhoto extends Model
{
    /**
     * 保存用户相册到redis
     * @param mixed $user_id
     * @param mixed $user_photo 用户全部相册obj
     * @return object
     */
    public static function setUserPhoto($user_id,$user_photo=[])
    {
        if(!$user_photo){
            $user_photo = Db::name('user_photo')->where('user_id',$user_id)->select();
            if(!$user_photo){
                return false;
            }else{
                $user_photo = json_encode($user_photo);
            }
        }
        $redis = new RedisPackage();
        $redis::$handler->set('userphoto:'.$user_id, $user_photo);
        return true;
    }

    /**
     * 获取用户相册
     * @param mixed $user_id
     * @return object
     */
    public static function getUserPhoto($user_id)
    {
        $redis = new RedisPackage();
        $userphoto = $redis::$handler->get('userphoto:'.$user_id);
        if(!$userphoto){
            $userphoto = Db::name('user_photo')->where('user_id',$user_id)->select();
            if(!$userphoto){
                $userphoto = '';
            }else{
                $userphoto = json_encode($userphoto);
                UserPhoto::setUserPhoto($user_id,$userphoto);
            }
        }else{
            $userphoto = json_decode($userphoto,true);
        }
        return $userphoto;
    }
}
