<?php

namespace app\common\model;

use think\Model;

class CommissionLog extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = false;

    /**
     * 写入日志
     * @param $send_id 发送礼物ID
     */
    public static function writeLog($send_id)
    {
        // 发送数据
        $send = UserGiftSend::get($send_id);
        // todo 缺少经纪人ID
        // 经纪人的
        self::create([
            'send_id' => $send_id,
            'money' => $send->agent_commission,
            'num' => $send->num,
            'type' => 1,
        ]);
        // 平台的
        self::create([
            'send_id' => $send_id,
            'money' => $send->platform_commission,
            'num' => $send->num,
            'type' => 2,
            'user_id' => 0
        ]);
    }
}