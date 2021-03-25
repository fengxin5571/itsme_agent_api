<?php
/**
 * Created by PhpStorm.
 * User: fengxin
 * Date: 2021/3/22
 * Time: 5:34 PM
 */
namespace app\common\model;
use think\Model;

class  AgentReal extends Model{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    /**
     * 认证项获取器
     * @param $value
     * @return mixed
     */
    public function getRealItemAttr($value)
    {
        if(is_string($value)){
            $value=json_decode($value,true);
        }
        return $value;
    }
}