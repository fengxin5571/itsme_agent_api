<?php

namespace app\common\model;

use think\Model;

/**
 * 用户提现表
 * Class UserVipOrder
 * @package app\common\model
 */
class UserWithdrawal extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

}