<?php

namespace app\common\model;

use think\Model;

/**
 * 用户实名认证记录表
 * Class UserVipOrder
 * @package app\common\model
 */
class UserTag extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}