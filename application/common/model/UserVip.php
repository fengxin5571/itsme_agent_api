<?php

namespace app\common\model;

use think\Model;

/**
 * 会员vip明细模型
 * Class UserVip
 * @package app\common\model
 */
class UserVip extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 编辑
     * @param int $user_id 会员id
     * @param array $configVipData 会员vip配置
     */
    public function edit($user_id = 0, $configVipData = [])
    {
        // 要更新或添加的基础数据
        $initData = [
            'user_id'        => $user_id,
            'vip_id'         => $configVipData['id'],
            'vip_name'       => $configVipData['name'],
            'price'          => $configVipData['price'],
            'discount_price' => $configVipData['discount_price'],
            'time_limit'     => $configVipData['time_limit'],
            'up_worth'       => $configVipData['up_worth'],
            'content'        => $configVipData['content'],
            'end_time'       => 0,
        ];
        
        // 查询现有数据
        $data = $this->where(['user_id' => $user_id])->find();
        
        if ($data) {
            // vip结束时间
            $initData['end_time'] = $data['end_time'] + $initData['time_limit'] * 86400;
            // 更新
            $this->allowField(true)->save($initData, ['user_id' => $data['user_id']]);
        } else {
            // 添加
            $this->add($initData);
        }
    }

    /**
     * 添加
     * @param array $data 数据
     */
    public function add($data = [])
    {
        // vip结束时间=当前时间+有效期（天）
        $data['end_time'] = time() + $data['time_limit'] * 86400;

        $this->allowField(true)->save($data);
    }
}