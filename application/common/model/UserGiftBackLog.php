<?php

namespace app\common\model;

use think\Model;

/**
 * 会员礼物退回记录
 * Class UserGiftBackLog
 * @package app\common\model
 */
class UserGiftBackLog extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = false;

}