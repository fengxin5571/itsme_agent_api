<?php

namespace app\common\model;

use think\Model;

/**
 * 用户足迹记录表
 * Class UserVipOrder
 * @package app\common\model
 */
class UserFootprint extends Model
{
    protected $autoWriteTimestamp = 'int';
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 查询当天游览别人主页记录
     * @param $userId
     * @return array|bool|\PDOStatement|string|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function select_view_user_home($userId,$type,$otherUserId=null){
        $start = strtotime(date('Y-m-d').'00:00:00');
        $end = strtotime(date('Y-m-d').'23:59:59')+3600*6; //外加6小时
        $where['type'] = $type;
        if($otherUserId != null){
            $where['other_user_id'] = $otherUserId;
        }
        $where['user_id'] = $userId;
        $where['createtime'] = ["between","$start,$end"];
        $res = $this->where($where)->order('createtime', 'desc')->find();
        if(empty($res)){
            $res = null;
        }else{
            $res = $res->toArray();
        }
        return $res;
    }
}