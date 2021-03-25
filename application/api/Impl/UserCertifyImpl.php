<?php

namespace app\api\Impl;

use app\common\model\User;
use app\common\model\User as usermodel;
use app\common\model\UserCertify;
use app\common\model\UserPhoto;
use think\Db;

/** 用户认证类 **/
class UserCertifyImpl{

    /**
     * 取得认证信息数据
     * @param $userId
     * @param $code
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCertifyInfo($userId,$code){
        //查询认证配置表配置项
        $data = [];
        $certify = Db::name('config_certify')->field('id,code,name,up_worth')->where(['code'=>$code])->find();
        if(empty($certify)){
            $data['code'] = 0;
            $data['data'] = [];
            return $data;
        }

        $userCertifyJson = User::get($userId);
        $userCertifyArr = json_decode($userCertifyJson->certify,true);

        //用户认证配置项目
        $userCertify = Db::name('user_certify')
            ->field("certify_id,certify_item")
            ->where(['user_id'=>$userId,'certify_id'=>$certify['id']])
            ->find();
        if(empty($userCertify)){
            $data['code'] = 0;
            $data['data'] = [];
            return $data;
        }else{
            $certifyArr = json_decode($userCertify['certify_item'],true);
            if(isset($certifyArr['profession_id'])){
                $data['code'] = 1;
                $data['data'] = [
                    'name'=> '职业认证',
                    'state'=> $userCertifyArr['goddess']['verified'],
                    'photo_list'=> $certifyArr['photo_list']
                ];
            }else{
                $data['code'] = 1;
                $data['data'] = [
                    'name'=> '身材认证',
                    'state'=> $userCertifyArr['goddess']['verified'],
                    'photo_list'=> $certifyArr['photo_list']
                ];
            }
            return $data;
        }
    }

    /**
     * 女神认证
     * @param $userId             用户id号
     * @param $code               认证编码
     * @param $userCertifyJson    之前用户认证数据
     * @param $dataJson           前端数据
     * 职业 ['name'=>'profession',‘profession_id’=>10,'photo_list'=>[['img'=>'http://www.baidu.com/one.jpg'],['img'=>'http://www.baidu.com/one.jpg']]]
     * 身材 ['name'=>'figure','photo_list'=>[['img'=>'http://www.baidu.com/one.jpg'],['img'=>'http://www.baidu.com/one.jpg']]]
     */
    public function goddessCheck($userId,$code,$userCertifyJson,$dataJson){
        //参数列表 当前时间
        $time = time();
        //返回数据
        $data = [];

        //判断是否之前认证过
        $certify =  $this->userCertifyCheck($userId,$code,$dataJson);
        if($certify['code']==1){
            //判断用户之前认证项数据
            if(empty($userCertifyJson)){
                $certifyArr = [];
            }else{
                $certifyArr = json_decode($userCertifyJson,true);
            }
            $dataArr = json_decode($dataJson,true);
            $certifyArr[$code] = [
                'desc'=>$certify['data']['list'][0]['name'],
                'short'=>mb_substr($certify['data']['list'][0]['name'],0,1),
                'verified'=>$this->certifyState($code)
            ];
            $certifyArr[$dataArr['name']] = [
                'desc'=>$certify['data']['list'][1]['name'],
                'short'=>mb_substr($certify['data']['list'][1]['name'],0,1),
                'verified'=>$this->certifyState($dataArr['name'])
            ];
            //添加用户认证数据user_certify表
            $userCertifyArr = [
                ['user_id'=>$userId,'certify_id'=>$certify['data']['list'][0]['id'],'up_worth'=>$certify['data']['list'][0]['up_worth'],'certify_item'=>$dataJson,'createtime'=>$time],
                ['user_id'=>$userId,'certify_id'=>$certify['data']['list'][1]['id'],'up_worth'=>$certify['data']['list'][0]['up_worth'],'certify_item'=>$dataJson,'createtime'=>$time]
            ];
            $goddessInfoId = Db::name('user_certify')->insertGetId($userCertifyArr[0]);
            $goddessOtherId = Db::name('user_certify')->insertGetId($userCertifyArr[1]);
            //添加用户认证数据user_certify_log表
            $userCertifyLogArr = [
                ['user_certify_id'=>$goddessInfoId,'certify_item'=>$dataJson,'state'=>2,'createtime'=>$time],
                ['user_certify_id'=>$goddessOtherId,'certify_item'=>$dataJson,'state'=>2,'createtime'=>$time]
            ];
            $userCertifyLog = Db::name('user_certify_log')->insertAll($userCertifyLogArr);

            //修改用户certify数据
            usermodel::update(['id'=>$userId,'certify'=>json_encode($certifyArr)]);
            //修改或者添加用户信息redis数据
            $userInfo =usermodel::get($userId);
            usermodel::setUserInfo($userInfo);
            //修改或者添加用户认证redis数据
            $user_certify = Db::name('user_certify')->where(['user_id'=>$userId])->select();
            $user_certify = json_encode($user_certify);
            usermodel::setUserCertify($userId,$user_certify);
            //TODO 没有审核通过没有添加认证项目加身价分
            $data['code'] = 1;
            $data['msg'] = "添加认证信息成功";
            return $data;
        }elseif ($certify['code']==2){
            //判断用户之前认证项数据
            $certifyArr = json_decode($userCertifyJson,true);
            //如果之前存在就删除
            if(isset($certifyArr["goddess"])){unset($certifyArr["goddess"]);}
            if(isset($certifyArr["profession"])){unset($certifyArr["profession"]);}
            if(isset($certifyArr["figure"])){unset($certifyArr["figure"]);}
            $dataArr = json_decode($dataJson,true);
            $certifyArr[$code] = [
                'desc'=>$certify['data']['list'][0]['name'],
                'short'=>mb_substr($certify['data']['list'][0]['name'],0,1),
                'verified'=>$this->certifyState($code)
            ];
            $certifyArr[$dataArr['name']] = [
                'desc'=>$certify['data']['list'][1]['name'],
                'short'=>mb_substr($certify['data']['list'][1]['name'],0,1),
                'verified'=>$this->certifyState($dataArr['name'])
            ];

            //更新用户认证数据user_certify表
            if(count($certify['data']['ids'])>1){
                $userCertifyArr = [
                    ['id'=>$certify['data']['ids'][0],'certify_item'=>$dataJson,'createtime'=>$time],
                    ['id'=>$certify['data']['ids'][1],'certify_item'=>$dataJson,'createtime'=>$time]
                ];
                $userCertify = new UserCertify();
                $userCertify->saveAll($userCertifyArr);

                //添加用户认证数据user_certify_log表
                $userCertifyLogArr = [
                    ['user_certify_id'=>$certify['data']['ids'][0],'certify_item'=>$dataJson,'state'=>2,'createtime'=>$time],
                    ['user_certify_id'=>$certify['data']['ids'][1],'certify_item'=>$dataJson,'state'=>2,'createtime'=>$time]
                ];
                $userCertifyLog = Db::name('user_certify_log')->insertAll($userCertifyLogArr);
            }else{
                $userCertifyArr = [
                    ['id'=>$certify['data']['ids'][0],'certify_item'=>$dataJson,'createtime'=>$time],
                    ['user_id'=>$userId,'certify_id'=>$certify['data']['list'][1]['id'],'up_worth'=>$certify['data']['list'][0]['up_worth'],'certify_item'=>$dataJson,'createtime'=>$time]
                ];
                Db::name("user_certify")->update($userCertifyArr[0]);
                $otherId =Db::name("user_certify")->insertGetId($userCertifyArr[1]);

                //添加用户认证数据user_certify_log表
                $userCertifyLogArr = [
                    ['user_certify_id'=>$certify['data']['ids'][0],'certify_item'=>$dataJson,'state'=>2,'createtime'=>$time],
                    ['user_certify_id'=>$otherId,'certify_item'=>$dataJson,'state'=>2,'createtime'=>$time]
                ];
                $userCertifyLog = Db::name('user_certify_log')->insertAll($userCertifyLogArr);
            }

            //修改用户certify数据
            usermodel::update(['id'=>$userId,'certify'=>json_encode($certifyArr)]);
            //修改或者添加用户信息redis数据
            $userInfo =usermodel::get($userId);
            usermodel::setUserInfo($userInfo);
            //修改或者添加用户认证redis数据
            $user_certify = Db::name('user_certify')->where(['user_id'=>$userId])->select();
            $user_certify = json_encode($user_certify);
            usermodel::setUserCertify($userId,$user_certify);
            //TODO 没有审核通过没有添加认证项目加身价分
            $data['code'] = 1;
            $data['msg'] = "添加认证信息成功";
            return $data;
        } else{
            $data['code'] = 0;
            $data['msg'] = $certify['msg'];
            return $data;
        }
    }

    /**
     * 判断用户是否已经认证
     * @param $userId     用户id号
     * @param $code       认证项目编码
     * @return int        返回配置id号 0表示已经认证或者认证项目有误 大于1表示没有认证
     */
    public function userCertifyCheck($userId,$code,$dataJson=""){
        //查询认证配置表配置项
        $data = [];    //返回数据
        $where = [];   //查询条件
        if($code=="goddess"){
            $dataArr = json_decode($dataJson,true);
            if($dataArr['name']=="profession"){
                $where = ['code' => [ [ 'eq' , $code] , [ 'eq' , 'profession' ] , 'or' ]];
            }elseif($dataArr['name']=="figure"){
                $where = ['code' => [ [ 'eq' , $code] , [ 'eq' , 'figure' ] , 'or' ]];
            }
        }else{
            $where = ['code' => [ [ 'eq' , $code] ] ];
        }

        $certify = Db::name('config_certify')->field('id,code,name,up_worth')->where($where)->select();
        if(count($certify)<=0){
            $data['code'] = 0;
            $data['msg'] = "认证项目有问题";
            $data['data'] = [];
            return $data;
        }

        //用户认证配置项目
        $ids = implode(",",array_column($certify,"id"));
        $userCertify = Db::name('user_certify')
            ->alias("a")
            ->field("a.id,a.user_id,a.certify_id,a.certify_item,a.up_worth,b.state,b.rename,b.createtime,b.updatetime")
            ->join("user_certify_log b","a.id = b.user_certify_id")
            ->where(['a.user_id'=>$userId,'a.certify_id'=>['in',$ids]])
            ->order("b.createtime desc")
            ->select();
        if(empty($userCertify)){
            $data['code'] = 1;
            $data['msg'] = "没有认证过该项目";
            $certifyData['list'] = $certify;
            $certifyData['ids'] = "";
            $data['data'] = $certifyData;
            return $data;
        }else{
            $stateCheck = 0;
            foreach ($userCertify as $k=>$v){
                if($v['state']==1){
                    $stateCheck = 1;
                    break;
                }elseif ($v['state']==2){
                    $stateCheck = 2;
                    break;
                }
            }
            if($stateCheck==1){
                $data['code'] = 0;
                $data['msg'] = "已近认证过该项目";
                $data['data'] = [];
            }elseif ($stateCheck==2){
                $data['code'] = 0;
                $data['msg'] = "认证项目审核中";
                $data['data'] = [];
            }else{
                $data['code'] = 2;
                $data['msg'] = "认证失败重新认证";
                $certifyData['list'] = $certify;
                if(count($userCertify)>1){
                    $certifyData['ids'] = array($userCertify[1]['id'],$userCertify[0]['id']);
                }else{
                    $certifyData['ids'] = array($userCertify[0]['id']);
                }
                $data['data'] = $certifyData;
            }
            return $data;
        }
    }

    /**
     * 判断认证项状态
     * @param $code
     * @return string
     */
    protected function certifyState($code){
        //0-false(没有通过) 1-true(通过) 2-audit(审核中)
        $state = 'false';
        if($code == 'profession' || $code == 'figure' || $code == 'goddess'){
            $state = 'audit';
        }else{
            $state = 'true';
        }
        return $state;
    }
}