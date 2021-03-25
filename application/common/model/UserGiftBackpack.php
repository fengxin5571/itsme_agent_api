<?php

namespace app\common\model;

use think\Model;

/**
 * 会员礼物背包
 * Class UserGiftBackpack
 * @package app\common\model
 */
class UserGiftBackpack extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 加礼物
     * @param $user_id 会员id
     * @param $gift_id 礼物id
     * @param $num 礼物数量
     */
    public static function addGift($user_id, $gift_id, $num)
    {
        $data = self::get(['gift_id' => $gift_id, 'user_id' => $user_id]);
        if (!empty($data)) {
            $data->save(['num' => $data->num + $num]);
        } else {
            self::create([
                'user_id' => $user_id,
                'gift_id' => $gift_id,
                'num'     => $num
            ]);
        }
    }

    /**
     * 减礼物
     * @param $user_id 会员id
     * @param $gift_id 礼物id
     * @param $num 礼物数量
     */
    public static function subtractGift($user_id, $gift_id, $num)
    {
        $data = self::get(['gift_id' => $gift_id, 'user_id' => $user_id]);
        if ($data->num >= $num) {
            $after = $data->num - $num;
            if ($after > 0) {
                $data->save(['num' => $after]);
            } else {
                $data->delete();
            }
            return true;
        }
        return false;
    }
}