<?php

namespace app\common\model;

use think\Model;

/**
 * 会员喜欢表
 * Class UserVipOrder
 * @package app\common\model
 */
class UserLike extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}