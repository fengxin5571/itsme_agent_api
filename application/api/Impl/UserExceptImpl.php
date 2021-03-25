<?php
namespace app\api\Impl;

use app\common\model\Area;
use app\common\model\User;
use app\common\model\UserExpect;
use Elasticsearch\ClientBuilder;
use Redis\RedisPackage;
use think\Db;

/**
 * 用户期望
 * Class UserExceptImpl
 * @package app\api\Impl
 */
class UserExceptImpl{
    //返回用户期望信息
    public static function getUserProfile($userId,$type=1,$sex=1){
        //调用redis
        $redis = new RedisPackage();

        //查询所有的期望值
        $expectStep = Db::name("expect_type_step")->field("type_id")->where(["step"=>$type,"sex"=>$sex])->order("sort asc")->select();
        $typeIds = array_column($expectStep,"type_id");
        $where['type_id'] = ['in',$typeIds];
        $expectInfo = Db::name("expect")->field("id,name,type_id")->where($where)->select();

        //查询用户填写的数据
        $userExpect = Db::name("user_expect")->field("id,expect_id")->where(["user_id"=>$userId])->order("id desc")->select();
        $expectIds = array_column($userExpect,'expect_id',"id");

        //处理期望数据
        foreach($expectInfo as $k=>$v) {
            if (in_array($v['id'], $expectIds)) {
                $expectInfo[$k]['is_choice'] = 1;
                $expectInfo[$k]['is_add'] = 1;
                $expectInfo[$k]['is_id'] = array_search($v['id'],$expectIds);
            } else {
                $expectInfo[$k]['is_choice'] = 0;
                $expectInfo[$k]['is_add'] = 0;
                $expectInfo[$k]['is_id'] = null;
            }
        }

        //分组展示
        $data = [];
        $userCustom = [];
        $userExpectCustomInfo = $redis::$handler->keys('userExpectCustomInfo:'.$userId);
        if($userExpectCustomInfo){
            $userCustom = $redis::$handler->get('userExpectCustomInfo:'.$userId);
            $userCustom = json_decode($userCustom,true);
        }
        foreach($typeIds as $key=>$val){
            $data[$key]['list'] = array_filter($expectInfo, function ($info) use ($val) {
                return $info['type_id'] == $val;
            });
            if(!empty($userCustom)){
                foreach($userCustom as $k=>$v){
                    if($v['type_id']==$val){
                        $data[$key]['list'][] = $v;
                    }
                }
            }
            $data[$key]['list']  = array_values($data[$key]['list']);
            $data[$key]['type_info'] = Db::name("expect_type")->where(['id'=>$val])->find();
            $data[$key]['type'] = 1;
        }

        if($type==1){
            //自定义期望数据
            $systemCustom = [
                "list" => [
                    [
                        "name"=>null
                    ],
                    [
                        "name"=>null
                    ]
                ],
                "type_info" => [
                    "type_name" => "期望年龄段"
                ],
                "type" => 2
            ];
            $sysExpectCustomInfo = $redis::$handler->keys('systemExpectCustomInfo:'.$userId);
            if($sysExpectCustomInfo){
                $systemCustom = $redis::$handler->get('systemExpectCustomInfo:'.$userId);
                $systemCustom = json_decode($systemCustom,true);
            }
            $data[] = $systemCustom;
        }
        $data = array_values($data);

        return $data;
    }

    //保存用户期望信息-新增和更新
    public static function setUserProfile($userId,$objInfo){
        //查询所有的期望值
        $objInfo = html_entity_decode($objInfo);
        $objInfo = json_decode($objInfo,true);
        //处理数据
        $infoArrAdd = [];
        $infoArrDel = [];
        $systemCustomInfo = null;
        $userCustomInfo = [];
        $userCustomInfoDel = [];
        array_map(function($val,$jianzhi) use (&$infoArrAdd,&$infoArrDel,&$systemCustomInfo,&$userCustomInfo,&$userCustomInfoDel,$userId) {
            if($val['type']==1){
                foreach($val['list'] as $k=>$v){
                    $keyVal = $k+1;
                    if(isset($v['is_add'])){
                        if($v['is_add']==1 && $v['is_choice']==0){
                            $infoArrDel[$jianzhi]['id'] = $v['is_id'];
                            $infoArrDel[$jianzhi]['expect_id'] = $v['id'];
                            $infoArrDel[$jianzhi]['user_id'] = $userId;
                        }
                        if($v['is_add']==0 && $v['is_choice']==1){
                            $infoArrAdd[$jianzhi]['expect_id'] = $v['id'];
                            $infoArrAdd[$jianzhi]['user_id'] = $userId;
                            $infoArrAdd[$jianzhi]['createtime'] = time();
                        }
                    }
                    if(isset($v['is_custom'])){
                        if($v['is_choice']==1){
                            $userCustomInfo[] = $v;
                        }else{
                            $userCustomInfoDel[] = $v;
                        }
                    }
                }
            }
            if($val['type']==2){
                $systemCustomInfo = $val;
            }
        },$objInfo,array_keys($objInfo));

        //删除操作
        if(!empty($infoArrDel)){
            $where['id'] = ['in',array_column($infoArrDel,'id')];
            $del = Db::name("user_expect")->where($where)->delete();
        }

        //添加操作
        if(!empty($infoArrAdd)){
            $userExpect = new UserExpect();
            $add = $userExpect->insertAll($infoArrAdd);
        }

        //自定义操作
        $redis = new RedisPackage();
        if(!empty($systemCustomInfo)){
            $redis::$handler->set('systemExpectCustomInfo:'.$userId, json_encode($systemCustomInfo));
        }
        if(!empty($userCustomInfo)){
            $userCustomInfoOld = $redis::$handler->get('userExpectCustomInfo:'.$userId);
            if(!empty($userCustomInfoOld)){
                $userCustomInfoOld = json_decode($userCustomInfoOld,true);
                if(!empty($userCustomInfoDel)){
                    foreach($userCustomInfoOld as $key=>$val){
                        foreach ($userCustomInfoDel as $keyDel=>$valDel){
                            if($val['name']==$valDel['name'] && $val['type_id']==$valDel['type_id']){
                                unset($userCustomInfoOld[$key]);
                            }
                        }
                        foreach ($userCustomInfo as $keyDel=>$valDel){
                            if($val['name']==$valDel['name'] && $val['type_id']==$valDel['type_id']){
                                unset($userCustomInfo[$key]);
                            }
                        }
                    }
                }
                $userCustomInfo = array_merge($userCustomInfoOld,$userCustomInfo);
            }
            $redis::$handler->set('userExpectCustomInfo:'.$userId, json_encode($userCustomInfo));
        }else{
            if(!empty($userCustomInfoDel)){
                $userCustomInfoOld = $redis::$handler->get('userExpectCustomInfo:'.$userId);
                if(!empty($userCustomInfoOld)){
                    $userCustomInfoOld = json_decode($userCustomInfoOld,true);
                    foreach($userCustomInfoOld as $key=>$val){
                        foreach ($userCustomInfoDel as $keyDel=>$valDel){
                            if($val['name']==$valDel['name'] && $val['type_id']==$valDel['type_id']){
                                unset($userCustomInfoOld[$key]);
                            }
                        }
                    }
                    $userCustomInfo = array_merge($userCustomInfoOld,$userCustomInfo);
                }
                $redis::$handler->set('userExpectCustomInfo:'.$userId, json_encode($userCustomInfo));
            }
        }

        return true;
    }
}
