<?php
/**
 * Created by PhpStorm.
 * User: fengxin
 * Date: 2021/3/23
 * Time: 9:41 AM
 */
namespace app\api\controller;
use app\api\Impl\AgentService;
use app\api\Impl\CertifyService;
use app\common\controller\Api;
use app\common\model\AgentReal;
use think\Request;
use think\Validate;
/**
 * 经纪人认证控制器
 * Class Certify
 * @package app\api\controller
 */
class Certify extends Api{
    protected $noNeedLogin = [''];
    protected $noNeedRight = ['*'];
    protected $services=[];
    public function _initialize(){
        parent::_initialize();
        $this->services['agentServ']=new AgentService();
        $this->services['certifyServ']=new CertifyService();
    }
    /**
     * 经纪人实名认证
     * @param Request $request
     */
    public function agent_real(Request $request){
        $params=$request->only(['front_img','back_img']);
        $validate=new Validate([
            'front_img'      => 'require',
            'back_img'       =>'require',
        ]);
        if (!$validate->check($params)) {
            $this->error($validate->getError());
        }
        //调用实名认证方法
        $res=$this->services['certifyServ']->real_certify($params['front_img'],$params['back_img'],$this->auth->id);
        if(!$res){
            return $this->error('认证失败');
        }
        return $this->success('认证成功');
    }
    /**
     * 验证经纪人是否实名认证
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function agent_is_real(){
        $data=[
            'is_real'=>false,
            'real_info'=>[],
        ];
        $agentReal=AgentReal::where('agent_id',$this->auth->id)
            ->field(['id,name,agent_id,identity_card,gender,real_item'])->find();
        if($agentReal){
            $data['is_real']=true;
            $data['real_info']=$agentReal;
        }
        return $this->success('ok',$data);
    }
}