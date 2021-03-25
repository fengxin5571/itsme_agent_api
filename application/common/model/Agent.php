<?php
/**
 * Created by PhpStorm.
 * User: fengxin
 * Date: 2021/3/22
 * Time: 9:49 AM
 */
namespace app\common\model;
use Redis\RedisPackage;
use think\Model;
/**
 * 经纪人模型
 * Class Agent
 * @package app\common\model
 */
class Agent extends Model{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $hidden =['password','salt','token'];
    /**
     * 经纪人实名认证信息
     * @return $this
     */
    public function agentReal()
    {
        return $this->hasOne('AgentReal','agent_id')->field('agent_id,name,identity_card,gender,real_item');
    }
    /**
     * 旗下艺人
     * @return \think\model\relation\HasMany
     */
    public function artists(){
        return $this->hasMany('User','p_agent_id');
    }

    /**
     * 保存reids经纪人信息
     * @param $agent
     * @return bool
     */
    public static function setRedisAgentInfo($agent)
    {
        $redis_agent = json_encode($agent);
        $redis = new RedisPackage();
        $redis::$handler->set('userinfo:'.$agent->id, $redis_agent);
        return true;
    }
    /**
     * 获取redis里的经纪人信息
     * @param $agent_id
     */
    public static function getRedisAgentInfo($agent_id){
        $redis = new RedisPackage();
        return $redis::$handler->get('userinfo:'.$agent_id);
    }
    /**
     * 删除redis经纪人信息
     * @param $agent
     * @return bool
     */
    public static function delRedisAgentInfo($agent_id){
        $redis = new RedisPackage();
        $redis::$handler->del('userinfo:'.$agent_id);
        return true;
    }

}