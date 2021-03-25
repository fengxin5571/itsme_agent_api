<?php

namespace app\common\model;

use think\Model;

/**
 * 邀请会员记录
 * Class UserInviteLog
 * @package app\common\model
 */
class UserInviteLog extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = false;
}