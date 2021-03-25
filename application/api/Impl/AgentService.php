<?php
/**
 * Created by PhpStorm.
 * User: fengxin
 * Date: 2021/3/22
 * Time: 10:08 AM
 */
namespace app\api\Impl;
use app\common\library\Sms;
use app\common\model\Agent;
use app\common\model\AgentReal;
use app\common\model\Area;
use Redis\RedisPackage;

/**
 * 经纪人服务类
 * Class AgentService
 * @package app\api\Impl
 */
class AgentService {
    public function __construct()
    {

    }
    /**
     * 经纪人普通登录
     * @param $credentials
     * @param $auth
     * @return mixed
     */
    public function login($credentials,$auth){
        return $auth->login($credentials['mobile'],$credentials['password']);
    }
    /**
     * 经纪人手机验证码登录
     * @param $credentials
     * @param $auth
     * @return bool
     */
    public function mobileLogin($credentials,$auth){
        //判断经纪人是否存在
        $agent=Agent::getByMobile($credentials['mobile']);
        if(!$agent){
            $auth->setError('Account is incorrect');
            return false;
        }
        //经纪人账号是否可用
        if ($agent->status != 'normal') {
            $auth->setError(__('Account is locked'));
            return false;
        }
        //判断你验证码是否正确
        if(!Sms::check($credentials['mobile'],$credentials['captcha'],'agent_mobilelogin')){
            $auth->setError(__('Captcha is incorrect'));
            return false;
        }
        //如果已经有账号则直接登录
        $ret = $auth->direct($agent->id);
        Sms::flush($credentials['mobile'], 'agent_mobilelogin');
        return $ret;
    }
    /**
     * 获取经纪人信息
     * @param $agent_id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAgentInfo($agent_id){
         $data= Agent::with('agentReal')->where(['id'=>$agent_id,'status'=>'normal'])->find();
         $data->city=$data->city? Area::where('id',$data->city)->value('name'):"";
         $data->is_real=$data->agent_real?true:false;
         $data->today_income=0;
         $data->history_income=0;
         return $data;
    }
}