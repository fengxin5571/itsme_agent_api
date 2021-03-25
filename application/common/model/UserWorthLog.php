<?php

namespace app\common\model;

use Redis\RedisPackage;
use think\Model;

/**
 * 会员身价变动模型
 * Class UserWorthLog
 * @package app\common\model
 */
class UserWorthLog extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = false;

    public static function addUserWorthLog($data){
        //获取用户之前身价值
        $userInfoGet = User::get($data['user_id']);
        $userWorth = $userInfoGet->worth;

        //新增身价分值
        $worth = $userWorth+$data['worth'];

        //创建身价分日志记录
        $create['user_id'] = $data['user_id'];
        $create['worth'] = $data['worth'];
        $create['before'] = $userWorth;
        $create['after'] = $worth;
        $create['type'] = $data['type'];
        $create['type_id'] = $data['type_id'];
        $res = self::create($create);
        if($res){
            //更行用户身价分
            $userUpdate = User::update(['id' => $data['user_id'], 'worth' => $worth]);
            if($userUpdate){
                $userinfo = User::get($data['user_id']);
                $return = User::setUserInfo($userinfo);
            }
        }
        return $return;
    }
}