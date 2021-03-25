<?php


namespace app\common\model;


use think\Model;

/**
 * 会员期望标签
 * Class UserExpect
 * @package app\common\model
 */
class UserExpect extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = false;
}