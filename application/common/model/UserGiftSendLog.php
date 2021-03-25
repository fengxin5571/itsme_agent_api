<?php

namespace app\common\model;

use think\Model;

class UserGiftSendLog extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = false;
}