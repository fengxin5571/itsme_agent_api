<?php

namespace app\common\model;

use think\Model;

/**
 * 会员收到礼物
 * Class userGiftReceived
 * @package app\common\model
 */
class UserGiftReceived extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 接收
     * @param $send_id 发送礼物id
     */
    public static function do($send_id)
    {
        // 发送数据
        $send = UserGiftSend::get($send_id);

        self::create([
            'send_id' => $send_id,
            'money' => $send->price - $send->platform_commission - $send->agent_commission,
            'user_id' => $send->receive_user_id,
            'num' => $send->num
        ]);
    }
}