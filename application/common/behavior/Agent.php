<?php
/**
 * Created by PhpStorm.
 * User: fengxin
 * Date: 2021/3/22
 * Time: 11:44 AM
 */
namespace app\common\behavior;
use app\common\model\Agent as AgentModel;
/**
 * 经纪人钩子
 * Class Agent
 * @package app\common\behavior
 */
class Agent {
    /**
     * 经纪人登录行为
     * @param $params
     */
    public function userLoginSuccessed($params){
        AgentModel::setRedisAgentInfo($params);
    }
    /**
     * 经纪人登出行为
     * @param $params
     */
    public function userLogoutSuccessed($params){
        //清除经纪人redis的信息
        AgentModel::delRedisAgentInfo($params->id);
    }
}