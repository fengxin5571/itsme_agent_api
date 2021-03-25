<?php
/**
 * Created by PhpStorm.
 * User: fengxin
 * Date: 2021/3/23
 * Time: 1:30 PM
 */
namespace app\api\Impl;
use app\common\exception\BusinessException;
use app\common\model\AgentBank;
use app\common\model\AgentReal;

/**
 * 经纪人银行服务类
 * Class BankService
 * @package app\api\Impl
 */
class BankService{
    /**
     * 添加银行卡
     * @param $params
     * @param $agent_id
     * @throws BusinessException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bank_add($params,$agent_id){
        //获取经纪人认证信息
        $agent_real=AgentReal::where('agent_id',$agent_id)->find();
        if(!$agent_real){
            throw new BusinessException('此用户还未实名认证');
        }
        $insert_date=[
            'bank_of_deposit'=>$params['bank_of_deposit'],
            'bank_card'=>$params['bank_card'],
            'bank_name'=>$agent_real->name,
            'agent_id' =>$agent_id,
            'identity_card'=>$agent_real->identity_card,
        ];
        $is_bank=AgentBank::where($insert_date)->field("id")->find();
        if($is_bank){
            throw new BusinessException('此银行卡存在');
        }
        if(!AgentBank::create($insert_date)){
            throw new BusinessException('添加银行卡失败');
        }

    }
    /**
     * 银行卡列表
     * @param $is_hidden
     * @param $agent_id
     * @return false|\PDOStatement|string|\think\Collection|\think\model\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bank_list($is_hidden,$agent_id){
       $bankList= AgentBank::where('agent_id',$agent_id)
           ->field('id,agent_id,bank_of_deposit,bank_card,bank_name,identity_card,state,is_check')->select();
       $bankList=collection($bankList);
       $bankList->each(function($item) use ($is_hidden){
           $item->bank_card=$is_hidden?$this->hiddenCard($item->bank_card):$item->bank_card;
           $item->identity_card=$is_hidden?$this->hiddenCard($item->identity_card):$item->identity_card;
       });
       return $bankList;
    }
    /**
     * 隐藏号码
     * @param $card
     * @return string
     */
    private function hiddenCard($card){
        if(strlen($card)>=16){
            $identityCard = str_split($card,4);
            $identityCard = array_fill(1,count($identityCard) - 2,"****") + $identityCard;
            ksort($identityCard);
            $card = implode("",$identityCard);
        }
        return $card;
    }
}