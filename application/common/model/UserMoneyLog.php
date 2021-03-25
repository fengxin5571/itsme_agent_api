<?php

namespace app\common\model;

use think\Model;

/**
 * 会员余额变动模型
 * Class UserMoneyLog
 * @package app\common\model
 */
class UserMoneyLog extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = false;
}