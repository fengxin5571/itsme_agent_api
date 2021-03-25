<?php

namespace app\common\model;

use think\Model;

/**
 * 礼物订单
 * Class UserGiftOrder
 * @package app\common\model
 */
class UserGiftOrder extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    /**
     * 添加
     * @param array $data
     * @return mixed
     */
    public function add($data = [])
    {
        $this->data($data);
        $this->save();
        return $this->id;
    }

    /**
     * 编辑
     * @param array $data
     */
    public function edit($data = [])
    {
        // 微信返回SUCCESS订单状态改为已支付
        if ($data['result_code'] == 'SUCCESS') {
            $data['status'] = 1;
        }
        // 支付完成时间改为时间戳
        $data['time_end'] = strtotime($data['time_end']);

        if ($data['pay_type'] == 1) {
            // 将微信回调的所有数据保存起来
            $data['weixin_notify_json'] = json_encode($data);
        }
        if ($data['pay_type'] == 2) {
            // 将支付宝回调的所有数据保存起来
            $data['ali_notify_json'] = json_encode($data['notify_arr']);
        }

        // 更新订单数据
        $this->allowField(true)->save($data, ['out_trade_no' => $data['out_trade_no']]);
    }
}