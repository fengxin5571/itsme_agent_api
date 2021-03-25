<?php
/**
 * Created by PhpStorm.
 * User: fengxin
 * Date: 2021/3/22
 * Time: 9:06 AM
 */
namespace app\api\controller;
use app\api\Impl\AgentService;
use app\common\controller\Api;
use think\Request;
use think\Validate;
use app\common\model\Agent as AgentModel;
use app\common\library\Token;

/**
 * 经纪人控制器
 * Class Agent
 * @package app\api\controller
 */
class Agent extends Api{
    protected $noNeedLogin = ['login','mobileLogin'];
    protected $noNeedRight = ['*'];
    protected $services=[];
    public function _initialize(){
        parent::_initialize();
        $this->services['agentServ']=new AgentService();
    }
    /**
     * 经纪人登录
     * @param Request $request
     */
    public function login(Request $request){
        $credentials=$request->only(['mobile','password']);
        $validate=new Validate([
            'mobile'=>'require',
            'password'=>'require',
        ]);
        if(!$validate->check($credentials)){
            return  $this->error(__('Invalid parameters'));
        }
        //验证手机格式
        if(!Validate::regex($credentials['mobile'],
            '/^[1](([3][0-9])|([4][5-9])|([5][0-3,5-9])|([6][5,6])|([7][0-8])|([8][0-9])|([9][1,8,9]))[0-9]{8}$/')){
            return $this->error(__('Mobile is incorrect'));
        }
        $res=$this->services['agentServ']->login($credentials,$this->auth);
        if(!$res){
           return $this->error($this->auth->getError());
        }
        $data = ['token' =>Token::get($this->auth->getToken())];
        return $this->success(__('Logged in successful'), $data);
    }
    /**
     * 经纪人手机验证码登录
     * @param Request $request
     */
    public function mobileLogin(Request $request){
        $credentials=$request->only(['mobile','captcha']);
        $validate=new Validate([
            'mobile'=>'require',
            'captcha'=>'require',
        ]);
        if(!$validate->check($credentials)){
            return  $this->error(__('Invalid parameters'));
        }
        //验证手机格式
        if(!Validate::regex($credentials['mobile'],
            '/^[1](([3][0-9])|([4][5-9])|([5][0-3,5-9])|([6][5,6])|([7][0-8])|([8][0-9])|([9][1,8,9]))[0-9]{8}$/')){
            return $this->error(__('Mobile is incorrect'));
        }
        $ret=$this->services['agentServ']->mobileLogin($credentials,$this->auth);
        if(!$ret){
            return $this->error($this->auth->getError());
        }
        $data = ['token' =>Token::get($this->auth->getToken())];
        $this->success(__('Logged in successful'), $data);
    }
    /**
     * 获取经纪人信息
     */
    public function info(){
        $agentInfo=$this->services['agentServ']->getAgentInfo($this->auth->id);
        return $this->success('', $agentInfo);
    }
    /**
     * 经纪人登出
     * @throws \think\exception\DbException
     */
    public function logout(){
        AgentModel::where('id',$this->auth->id)
            ->update(['outtime'=>time(),'is_online'=>0]);
        //登出
        $this->auth->logout();
        return $this->success(__('Logout successful'));
    }
}