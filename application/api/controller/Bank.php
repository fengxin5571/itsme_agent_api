<?php

namespace app\api\controller;

use app\api\Impl\AgentService;
use app\api\Impl\BankService;
use app\api\Impl\UserCheckPermissionImpl;
use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use app\common\model\UserBank;
use app\common\model\UserBlack;
use app\common\model\UserReal;
use app\common\model\UserWithdrawal;
use fast\Random;
use think\Request;
use think\Validate;
use think\Cache;
use think\Db;
use think\Exception;
use think\Config;
use app\common\model\User as usermodel;
use app\common\model\ConfigSocialStatus;
use app\common\model\UserPhoto;
use think\Queue;
use think\Log;
use app\common\model\Area;

/**
 * 会员银行及实名认证接口
 */
class Bank extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';
    protected $services=[];
    public function _initialize(){
        parent::_initialize();
        $this->services['bankServ']=new BankService();
    }
    /**
     * 添加银行卡接口
     * 'bank_card'      => 'require', //银行卡号
     * 'bank_of_deposit' //开户行
     */
    public function agent_bank_add(){
        //获取参数
        $param = $this->request->only(['bank_card','bank_of_deposit']);
        $validate = new \think\Validate([
            'bank_card'      => 'require', //银行卡号
            'bank_of_deposit'  => 'require', //开户行
        ]);
        if (!$validate->check($param)) {
            $this->error($validate->getError());
        }
        $this->services['bankServ']->bank_add($param,$this->auth->id);
        return $this->success('添加银行卡成功');
    }
    /**
     * 银行卡列表
     * is_hidden 0 否 1是 是否隐藏关键号码
     * @throws \think\exception\DbException
     */
    public function user_bank_list(Request $request){
        $is_hidden=$request->request('is_hidden',0);//是否隐藏卡号
        $data= $this->services['bankServ']->bank_list($is_hidden,$this->auth->id);
        return $this->success('ok',$data);
    }
}
