<?php
/**
 * Created by PhpStorm.
 * User: fengxin
 * Date: 2021/3/23
 * Time: 1:41 PM
 */
namespace app\common\model;
use think\Model;

class AgentBank extends Model{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
}