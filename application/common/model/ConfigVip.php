<?php

namespace app\common\model;

use think\Model;

/**
 * 配置会员VIP模型
 * Class ConfigVip
 * @package app\common\model
 */
class ConfigVip extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

}