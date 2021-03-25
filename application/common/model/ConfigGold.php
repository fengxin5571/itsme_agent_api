<?php

namespace app\common\model;

use think\Model;

/**
 * 配置会员金币模型
 * Class ConfigGold
 * @package app\common\model
 */
class ConfigGold extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}