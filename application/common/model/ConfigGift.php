<?php

namespace app\common\model;

use think\Model;

/**
 * 配置礼物
 * Class ConfigGift
 * @package app\common\model
 */
class ConfigGift extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}