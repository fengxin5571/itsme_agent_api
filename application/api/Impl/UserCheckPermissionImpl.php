<?php


namespace app\api\Impl;


use app\common\controller\Api;
use app\common\model\User as usermodel;
use app\common\model\UserFootprint;
use app\common\model\UserPermissionLog;
use app\common\model\UserPhoto;
use fast\Http;
use Redis\RedisPackage;
use TencentCloud\Cds\V20180420\Models\DbauditTypesInfo;
use think\Config;
use think\Controller;
use think\Db;
use think\Log;
use think\Queue;

/**
 * 用户鉴权类
 * Class UserCheckPermission
 * @package app\api\controller
 */
class UserCheckPermissionImpl
{
    //修改vip用户权限
    public static function editUserPermission($uesrId,$vipId){
        //查询用户权限
        $configUserPermission = Db::name('config_user_permission')->where('vip_id',$vipId)->find();
        $time = time();
        //写入个人用户数据
        $userPermissionData = [
            'user_id' => $uesrId,
            'view_user_home' => $configUserPermission['view_user_home'],
            'view_user_home_gold' => $configUserPermission['view_user_home_gold'],
            'daily_view_girl_home' => $configUserPermission['daily_view_girl_home'],
            'daily_view_girl_home_gold' => $configUserPermission['daily_view_girl_home_gold'],
            'send_task_num' => $configUserPermission['send_task_num'],
            'send_task_num_gold' => $configUserPermission['send_task_num_gold'],
            'daily_view_money_photo' => $configUserPermission['daily_view_money_photo'],
            'daily_view_money_photo_gold' => $configUserPermission['daily_view_money_photo_gold'],
            'daily_view_money_video' => $configUserPermission['daily_view_money_video'],
            'daily_view_money_video_gold' => $configUserPermission['daily_view_money_video_gold'],
            'view_unset_photo_time' => $configUserPermission['view_unset_photo_time'],
            'view_unset_video_time' => $configUserPermission['view_unset_video_time'],
            'send_chat_num' => $configUserPermission['send_chat_num'],
            'send_chat_num_gold' => $configUserPermission['send_chat_num_gold'],
            'view_like_me' => $configUserPermission['view_like_me'],
            'view_opsex_guest' => $configUserPermission['view_opsex_guest'],
            'can_gift' => $configUserPermission['can_gift'],
            'createtime' => $time,
            'updatetime' => $time
        ];
        //判断是否存在
        $userInfo = Db::name('user_permission')->field("id")->where(['user_id'=>$uesrId])->find();
        if(empty($userInfo)){
            $insertUserPersion = Db::name('user_permission')->insert($userPermissionData);
            if($insertUserPersion){
                //用户权限数据写入redis数据
                $userPermissionDataJson = json_encode($userPermissionData);
                $redisUserPermission = \app\common\model\User::setUserPermission("set",$uesrId,$userPermissionDataJson);
            }
        }else{
            $updateUserPersion = Db::name('user_permission')->where(['id'=>$userInfo['id']])->update($userPermissionData);
            if($updateUserPersion){
                //用户权限数据写入redis数据
                $userPermissionDataJson = json_encode($userPermissionData);
                $redisUserPermission = \app\common\model\User::setUserPermission("set",$uesrId,$userPermissionDataJson);
            }
        }
    }

    //修改男女用户权限
    public static function updateUserPermission($uesrId,$type){
        //查询用户权限
        $configUserPermission = Db::name('config_user_permission')->where('type',$type)->find();
        $time = time();
        //写入个人用户数据
        $userPermissionData = [
            'user_id' => $uesrId,
            'view_user_home' => $configUserPermission['view_user_home'],
            'view_user_home_gold' => $configUserPermission['view_user_home_gold'],
            'daily_view_girl_home' => $configUserPermission['daily_view_girl_home'],
            'daily_view_girl_home_gold' => $configUserPermission['daily_view_girl_home_gold'],
            'send_task_num' => $configUserPermission['send_task_num'],
            'send_task_num_gold' => $configUserPermission['send_task_num_gold'],
            'daily_view_money_photo' => $configUserPermission['daily_view_money_photo'],
            'daily_view_money_photo_gold' => $configUserPermission['daily_view_money_photo_gold'],
            'daily_view_money_video' => $configUserPermission['daily_view_money_video'],
            'daily_view_money_video_gold' => $configUserPermission['daily_view_money_video_gold'],
            'view_unset_photo_time' => $configUserPermission['view_unset_photo_time'],
            'view_unset_video_time' => $configUserPermission['view_unset_video_time'],
            'send_chat_num' => $configUserPermission['send_chat_num'],
            'send_chat_num_gold' => $configUserPermission['send_chat_num_gold'],
            'view_like_me' => $configUserPermission['view_like_me'],
            'view_opsex_guest' => $configUserPermission['view_opsex_guest'],
            'can_gift' => $configUserPermission['can_gift'],
            'createtime' => $time,
            'updatetime' => $time
        ];
        //判断是否存在
        $userInfo = Db::name('user_permission')->field("id")->where(['user_id'=>$uesrId])->find();
        if(empty($userInfo)){
            $insertUserPersion = Db::name('user_permission')->insert($userPermissionData);
            if($insertUserPersion){
                //用户权限数据写入redis数据
                $userPermissionDataJson = json_encode($userPermissionData);
                $redisUserPermission = \app\common\model\User::setUserPermission("set",$uesrId,$userPermissionDataJson);
            }
        }else{
            $updateUserPersion = Db::name('user_permission')->where(['id'=>$userInfo['id']])->update($userPermissionData);
            if($updateUserPersion){
                //用户权限数据写入redis数据
                $userPermissionDataJson = json_encode($userPermissionData);
                $redisUserPermission = \app\common\model\User::setUserPermission("set",$uesrId,$userPermissionDataJson);
            }
        }
    }

    //游客进入主页判断
    public function tourist_home_check($touristId,$userId){
        $redis = new RedisPackage();
        //判断redis数据是否存在
        $checkKey = $redis::$handler->hExists('tourist:userlist'.$touristId,"count");
        if($checkKey){
            //判断当天游览人数
            $checkUser = $redis::$handler->hGet('tourist:userlist'.$touristId,'userlist');
            if($checkUser){
                $userList = explode(",",$checkUser);
                if(in_array($userId,$userList)){
                    $return['code'] = 1;
                    $return['msg'] = "可以访问";
                }else{
                    //判断个数
                    $count = $redis::$handler->hGet('tourist:userlist'.$touristId,'count');
                    if($count>=3){
                        $return['code'] = 0;
                        $return['msg'] = "已经大于3次机会";
                    }else{
                        $return['code'] = 1;
                        $return['msg'] = "可以访问";
                    }
                }
            }
        }else{
            $return['code'] = 1;
            $return['msg'] = "可以访问";
        }
        return $return;
    }

    //游客进入主页记录
    public function tourist_home_record($touristId,$userId){
        $redis = new RedisPackage();
        //判断redis数据是否存在
        $checkKey = $redis::$handler->hExists('tourist:userlist'.$touristId,"count");
        if($checkKey){
            //判断当天游览人数
            $checkUser = $redis::$handler->hGet('tourist:userlist'.$touristId,'userlist');
            if($checkUser){
                $userList = explode(",",$checkUser);
                if(in_array($userId,$userList)){
                    $return['code'] = 1;
                    $return['msg'] = "可以访问";
                }else{
                    //判断个数
                    $count = $redis::$handler->hGet('tourist:userlist'.$touristId,'count');
                    if($count>=3){
                        $return['code'] = 0;
                        $return['msg'] = "已经大于3次机会";
                    }else{
                        $newCount = $count + 1;
                        $redis::$handler->hSet('tourist:userlist'.$touristId,'count',$newCount);
                        $userListStr = $checkUser.",".$userId;
                        $redis::$handler->hSet('tourist:userlist'.$touristId,'userlist',$userListStr);
                        $time24 = strtotime(date('Ymd'))+ 86400;
                        $time = time();
                        $expire = $time24-$time;
                        $redis::$handler->expire('tourist:userlist'.$touristId,$expire);
                        $return['code'] = 1;
                        $return['msg'] = "可以访问";
                    }
                }
            }
        }else{
            $userListStr = $userId;
            $redis::$handler->hSet('tourist:userlist'.$touristId,'count',1);
            $redis::$handler->hSet('tourist:userlist'.$touristId,'userlist',$userListStr);
            $return['code'] = 1;
            $return['msg'] = "可以访问";
        }
        return $return;


//        $redis = new RedisPackage();
//        $count = $redis::$handler->hGet('tourist:userlist'.$touristId,'count');
//        if($count>=3){
//            $return['code'] = 0;
//            $return['msg'] = "已经大于3次机会";
//        }else{
//            $newCount = $count + 1;
//            $touristUserlist = $redis::$handler->hSet('tourist:userlist'.$touristId,'count',$newCount);
//            $return['code'] = 1;
//            $return['msg'] = "可以访问";
//        }
//        return $return;
    }

    /**
     * 用户主页鉴权，添加记录，10次提醒和15次提醒
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view_user_home_check($userId,$toUserId){
        //获取参数
        $otherUserId = $toUserId;
        $user = usermodel::get($userId);
        $userPermission = Db::name('user_permission')->where("user_id",$userId)->find(); //用户权限
        $userPermissionLog = new UserPermissionLog(); //用户浏览记录表
        $view_user_home = 0; //游览主页、女神权限次数
        $visitCount = 0; //当天游览次数

        //输出数据
        $data = [];

        //判断用户是否是vip
        $isVip = $this->checkVip($userId);
        if($isVip){
            //判断是vip 需要检测是否是游览女神用户
            $checkGirl = $this->checkGirl($otherUserId);
            if($checkGirl){
                //调用游览记录模型，判断是否是游览自己还是其它人
                $visitListData = $userPermissionLog->select_view_user_home($userId,2); //查询游览次数
                $visitListDataOther = $userPermissionLog->select_view_user_home($userId,2,$otherUserId); //查询用户是否今天游览过
                //判断次数限制
                if(!empty($userPermission)){
                    $view_user_home = $userPermission["daily_view_girl_home"];
                }
                if(!empty($visitListData)){
                    $visitCount = $visitListData['counts'];
                }
                if(!empty($visitListDataOther)){
                    //当天有游览记录，那么直接进入
                    $data['code'] = 1;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }

                //判断用户参数次数
                if($view_user_home==0){
                    $data['code'] = 0;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }
                //判断用户游览次数
                if($visitCount >= $view_user_home){
                    $data['code'] = 0;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }else{
                    //判断 用户主页次数-游览次数
                    $diffCount = $view_user_home-$visitCount;
                    $res = $this->insertPermissionLog($otherUserId,$user,$visitListData,2);
                    if($diffCount<=6){
                        if($res){
                            $data['code'] = 0;
                            $data['look_counts'] = $visitCount+1;
                            $data['zong_counts'] = $view_user_home;
                            return $data;
                        }else{
                            $data['code'] = 0;
                            $data['look_counts'] = $visitCount;
                            $data['zong_counts'] = $view_user_home;
                            return $data;
                        }
                    }else{
                        if($res){
                            $data['code'] = 1;
                            $data['look_counts'] = $visitCount+1;
                            $data['zong_counts'] = $view_user_home;
                            return $data;
                        }else{
                            $data['code'] = 1;
                            $data['look_counts'] = $visitCount;
                            $data['zong_counts'] = $view_user_home;
                            return $data;
                        }
                    }
                }
            }else{
                $visitListData = $userPermissionLog->select_view_user_home($userId,1); //查询游览次数
                $visitListDataOther = $userPermissionLog->select_view_user_home($userId,1,$otherUserId); //查询是否今天游览过
                //判断次数限制
                if(!empty($userPermission)){
                    $view_user_home = $userPermission["view_user_home"];
                }
                if(!empty($visitListData)){
                    $visitCount = $visitListData['counts'];
                }
                if(!empty($visitListDataOther)){
                    //当天有游览记录，那么直接进入
                    $data['code'] = 1;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }

                //判断用户参数次数
                if($view_user_home==0){
                    $data['code'] = 0;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }
                //判断用户游览次数
                if($visitCount >= $view_user_home){
                    $data['code'] = 0;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }else{
                    //判断 用户主页次数-游览次数
                    $diffCount = $view_user_home-$visitCount;
                    $res = $this->insertPermissionLog($otherUserId,$user,$visitListData);
                    if($diffCount<=6){
                        if($res){
                            $data['code'] = 0;
                            $data['look_counts'] = $visitCount+1;
                            $data['zong_counts'] = $view_user_home;
                            return $data;
                        }else{
                            $data['code'] = 0;
                            $data['look_counts'] = $visitCount;
                            $data['zong_counts'] = $view_user_home;
                            return $data;
                        }
                    }else{
                        if($res){
                            $data['code'] = 1;
                            $data['look_counts'] = $visitCount+1;
                            $data['zong_counts'] = $view_user_home;
                            return $data;
                        }else{
                            $data['code'] = 1;
                            $data['look_counts'] = $visitCount;
                            $data['zong_counts'] = $view_user_home;
                            return $data;
                        }
                    }
                }
            }
        }else{
            //不是vip用户
            //调用游览记录模型，判断是否是游览自己还是其它人
            $visitListData = $userPermissionLog->select_view_user_home($userId,1); //查询游览次数
            $visitListDataOther = $userPermissionLog->select_view_user_home($userId,1,$otherUserId); //查询是否今天游览过
            //判断次数限制
            if(!empty($userPermission)){
                $view_user_home = $userPermission["view_user_home"];
            }
            if(!empty($visitListData)){
                $visitCount = $visitListData['counts'];
            }
            if(!empty($visitListDataOther)){
                //当天有游览记录，那么直接进入
                $data['code'] = 1;
                $data['look_counts'] = $visitCount;
                $data['zong_counts'] = $view_user_home;
                return $data;
            }

            //判断用户参数次数
            if($view_user_home==0){
                $data['code'] = 0;
                $data['look_counts'] = $visitCount;
                $data['zong_counts'] = $view_user_home;
                return $data;
            }
            //判断用户游览次数
            if($visitCount >= $view_user_home){
                $data['code'] = 0;
                $data['look_counts'] = $visitCount;
                $data['zong_counts'] = $view_user_home;
                return $data;
            }else{
                //判断 用户主页次数-游览次数
                $diffCount = $view_user_home-$visitCount;
                $res = $this->insertPermissionLog($otherUserId,$user,$visitListData);
                if($diffCount<=6){
                    if($res){
                        $data['code'] = 0;
                        $data['look_counts'] = $visitCount+1;
                        $data['zong_counts'] = $view_user_home;
                        return $data;
                    }else{
                        $data['code'] = 0;
                        $data['look_counts'] = $visitCount;
                        $data['zong_counts'] = $view_user_home;
                        return $data;
                    }
                }else{
                    if($res){
                        $data['code'] = 1;
                        $data['look_counts'] = $visitCount+1;
                        $data['zong_counts'] = $view_user_home;
                        return $data;
                    }else{
                        $data['code'] = 1;
                        $data['look_counts'] = $visitCount;
                        $data['zong_counts'] = $view_user_home;
                        return $data;
                    }
                }
            }
        }
    }

    /**
     * 判断用户进入主页权限
     * @param $userId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view_user_hmoe_check_prev($userId,$otherUserId){
        $userPermission = Db::name('user_permission')->where("user_id",$userId)->find(); //用户权限
        $userPermissionLog = new UserPermissionLog(); //用户浏览记录表
        $view_user_home = 0; //游览主页、女神权限次数
        $visitCount = 0; //当天游览次数

        //输出数据
        $data = [];

        //判断用户是否是vip
        $isVip = $this->checkVip($userId);
        if($isVip){
            //判断是vip 需要检测是否是游览女神用户
            $checkGirl = $this->checkGirl($otherUserId);
            if($checkGirl){
                //调用游览记录模型，判断是否是游览自己还是其它人
                $visitListData = $userPermissionLog->select_view_user_home($userId,2); //查询游览次数
                $visitListDataOther = $userPermissionLog->select_view_user_home($userId,2,$otherUserId); //查询用户是否今天游览过
                //判断次数限制
                if(!empty($userPermission)){
                    $view_user_home = $userPermission["daily_view_girl_home"];
                }
                if(!empty($visitListData)){
                    $visitCount = $visitListData['counts'];
                }
                if(!empty($visitListDataOther)){
                    //当天有游览记录，那么直接进入
                    $data['code'] = 1;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }

                //判断用户参数次数
                if($view_user_home==0){
                    $data['code'] = 0;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }
                //判断用户游览次数
                if($visitCount >= $view_user_home){
                    $data['code'] = 0;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }else{
                    //判断 用户主页次数-游览次数
                    $diffCount = $view_user_home-$visitCount;
                    if($diffCount<=5){
                        $data['code'] = 0;
                        $data['look_counts'] = $visitCount;
                        $data['zong_counts'] = $view_user_home;
                        return $data;
                    }else{
                        $data['code'] = 1;
                        $data['look_counts'] = $visitCount;
                        $data['zong_counts'] = $view_user_home;
                        return $data;
                    }
                }
            }else{
                $visitListData = $userPermissionLog->select_view_user_home($userId,1); //查询游览次数
                $visitListDataOther = $userPermissionLog->select_view_user_home($userId,1,$otherUserId); //查询是否今天游览过
                //判断次数限制
                if(!empty($userPermission)){
                    $view_user_home = $userPermission["view_user_home"];
                }
                if(!empty($visitListData)){
                    $visitCount = $visitListData['counts'];
                }
                if(!empty($visitListDataOther)){
                    //当天有游览记录，那么直接进入
                    $data['code'] = 1;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }

                //判断用户参数次数
                if($view_user_home==0){
                    $data['code'] = 0;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }
                //判断用户游览次数
                if($visitCount >= $view_user_home){
                    $data['code'] = 0;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }else{
                    //判断 用户主页次数-游览次数
                    $diffCount = $view_user_home-$visitCount;
                    if($diffCount<=5){
                        $data['code'] = 0;
                        $data['look_counts'] = $visitCount;
                        $data['zong_counts'] = $view_user_home;
                        return $data;
                    }else{
                        $data['code'] = 1;
                        $data['look_counts'] = $visitCount;
                        $data['zong_counts'] = $view_user_home;
                        return $data;
                    }
                }
            }
        }else{
            //不是vip用户
            //调用游览记录模型，判断是否是游览自己还是其它人
            $visitListData = $userPermissionLog->select_view_user_home($userId,1); //查询游览次数
            $visitListDataOther = $userPermissionLog->select_view_user_home($userId,1,$otherUserId); //查询是否今天游览过
            //判断次数限制
            if(!empty($userPermission)){
                $view_user_home = $userPermission["view_user_home"];
            }
            if(!empty($visitListData)){
                $visitCount = $visitListData['counts'];
            }
            if(!empty($visitListDataOther)){
                //当天有游览记录，那么直接进入
                $data['code'] = 1;
                $data['look_counts'] = $visitCount;
                $data['zong_counts'] = $view_user_home;
                return $data;
            }

            //判断用户参数次数
            if($view_user_home==0){
                $data['code'] = 0;
                $data['look_counts'] = $visitCount;
                $data['zong_counts'] = $view_user_home;
                return $data;
            }
            //判断用户游览次数
            if($visitCount >= $view_user_home){
                $data['code'] = 0;
                $data['look_counts'] = $visitCount;
                $data['zong_counts'] = $view_user_home;
                return $data;
            }else{
                //判断 用户主页次数-游览次数
                $diffCount = $view_user_home-$visitCount;
                if($diffCount<=5){
                    $data['code'] = 0;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }else{
                    $data['code'] = 1;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }
            }
        }
    }

    //新判断用户进入主页权限
    public function view_user_hmoe_check_prev_new($userId,$otherUserId){
        $userPermission = Db::name('user_permission')->where("user_id",$userId)->find(); //用户权限
        $userPermissionLog = new UserPermissionLog(); //用户浏览记录表
        $view_user_home = 0; //游览主页、女神权限次数
        $visitCount = 0; //当天游览次数

        //输出数据
        $data = [];

        //判断用户是否是vip
        $isVip = $this->checkVip($userId);
        if($isVip){
            $visitListData = $userPermissionLog->select_view_user_home($userId,1); //查询游览次数
            $visitListDataOther = $userPermissionLog->select_view_user_home($userId,1,$otherUserId); //查询是否今天游览过
            //判断次数限制
            if(!empty($userPermission)){
                $view_user_home = $userPermission["view_user_home"];
            }
            if(!empty($visitListData)){
                $visitCount = $visitListData['counts'];
            }
            if(!empty($visitListDataOther)){
                //当天有游览记录，那么直接进入
                $data['code'] = 1;
                $data['look_counts'] = $visitCount;
                $data['zong_counts'] = $view_user_home;
                return $data;
            }

            //判断用户参数次数
            if($view_user_home==0){
                $data['code'] = 0;
                $data['look_counts'] = $visitCount;
                $data['zong_counts'] = $view_user_home;
                return $data;
            }
            //判断用户游览次数
            if($visitCount >= $view_user_home){
                $data['code'] = 0;
                $data['look_counts'] = $visitCount;
                $data['zong_counts'] = $view_user_home;
                return $data;
            }else{
                //判断 用户主页次数-游览次数
                $diffCount = $view_user_home-$visitCount;
                if($diffCount<=5){
                    $data['code'] = 0;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }else{
                    $data['code'] = 1;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }
            }
        }else{
            //不是vip用户
            //调用游览记录模型，判断是否是游览自己还是其它人
            $visitListData = $userPermissionLog->select_view_user_home($userId,1); //查询游览次数
            $visitListDataOther = $userPermissionLog->select_view_user_home($userId,1,$otherUserId); //查询是否今天游览过
            //判断次数限制
            if(!empty($userPermission)){
                $view_user_home = $userPermission["view_user_home"];
            }
            if(!empty($visitListData)){
                $visitCount = $visitListData['counts'];
            }
            if(!empty($visitListDataOther)){
                //当天有游览记录，那么直接进入
                $data['code'] = 1;
                $data['look_counts'] = $visitCount;
                $data['zong_counts'] = $view_user_home;
                return $data;
            }

            //判断用户参数次数
            if($view_user_home==0){
                $data['code'] = 0;
                $data['look_counts'] = $visitCount;
                $data['zong_counts'] = $view_user_home;
                if($this->checkBoy($userId)){
                    $data['bullet_frame'] = config("permission_info.boy");
                }else{
                    $data['bullet_frame'] = config("permission_info.girl");
                }
                return $data;
            }
            //判断用户游览次数
            if($visitCount >= $view_user_home){
                $data['code'] = 0;
                $data['look_counts'] = $visitCount;
                $data['zong_counts'] = $view_user_home;
                if($this->checkBoy($userId)){
                    $data['bullet_frame'] = config("permission_info.boy");
                }else{
                    if($this->checkGirl($userId)){
                        $data['bullet_frame'] = config("permission_info.girl_goddess");
                    }else{
                        $data['bullet_frame'] = config("permission_info.girl");
                    }
                }
                return $data;
            }else{
                //判断 用户主页次数-游览次数
                $diffCount = $view_user_home-$visitCount;
                if($diffCount<=5){
                    $data['code'] = 0;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    if($this->checkBoy($userId)){
                        $data['bullet_frame'] = config("permission_info.boy_num");
                    }else{
                        if($this->checkGirl($userId)){
                            $data['bullet_frame'] = config("permission_info.girl_goddess_num");
                        }else{
                            $data['bullet_frame'] = config("permission_info.girl_num");
                        }
                    }
                    return $data;
                }else{
                    $data['code'] = 1;
                    $data['look_counts'] = $visitCount;
                    $data['zong_counts'] = $view_user_home;
                    return $data;
                }
            }
        }
    }

    /**
     * 用户照片、视频鉴权
     * 类型1图片 2视频
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view_user_photo_check($userId,$toUserId,$photoId,$type){
        //获取参数
        $otherUserId = $toUserId;
        $userPhoto = new UserPhoto(); //查找照片、视频类型
        $photoFindInfo = $userPhoto->where(["id"=>$photoId])->find();

        //返回数据
        $data = [];

        //判断是否是自己,如果是自己，那么就返回所有权限
        if($userId==$toUserId){
            $data['code'] = 1;
            $data['msg'] = '照片鉴权通过';
            return $data;
        }

        //判断是否是vip 规则类型
        $ruleType = $photoFindInfo["rule_type"];
        if($this->checkVip($userId)){
            if($type=='itsme_image'){//图片
                if($ruleType==1){
                    $data['code'] = 1;
                    $data['msg'] = '照片鉴权通过';
                }elseif ($ruleType==2){
                    $data['code'] = 1;
                    $data['msg'] = '照片鉴权通过';
                }elseif ($ruleType==3){
                    $start = strtotime(date('Y-m-d').'00:00:00');
                    $end = strtotime(date('Y-m-d').'23:59:59');
                    $where['user_id'] = $otherUserId;
                    $where['photo_id'] = $photoId;
                    $where['createtime'] = ["between","$start,$end"];
                    $readPhoto = Db::name("user_photo_read")->where($where)->find();
                    if(empty($readPhoto)){
                        $data['code'] = 0;
                        $data['msg'] = '照片鉴权失败';
                    }else{
                        $data['code'] = 1;
                        $data['msg'] = '照片鉴权通过';
                    }
                }elseif ($ruleType==4){
                    //查找支付记录
                    $readPhoto = Db::name("user_money_log")->where(['type'=>1,'type_id'=>$photoId])->find();
                    if(empty($readPhoto)){
                        $data['code'] = 0;
                        $data['msg'] = '照片鉴权失败';
                    }else{
                        $data['code'] = 1;
                        $data['msg'] = '照片鉴权通过';
                    }
                }
            }elseif ($type=='itsme_video'){//视频
                if($ruleType==1){
                    $data['code'] = 1;
                    $data['msg'] = '视频鉴权通过';
                }elseif ($ruleType==2){
                    $data['code'] = 1;
                    $data['msg'] = '视频鉴权通过';
                }elseif ($ruleType==3){
                    $readPhoto = Db::name("user_photo_read")->where(['user_id'=>$otherUserId,'photo_id'=>$photoId])->find();
                    if(empty($readPhoto)){
                        $data['code'] = 0;
                        $data['msg'] = '视频鉴权失败';
                    }else{
                        $data['code'] = 1;
                        $data['msg'] = '视频鉴权通过';
                    }
                }elseif ($ruleType==4){
                    $readPhoto = Db::name("user_money_log")->where(['type'=>2,'type_id'=>$photoId])->find();
                    if(empty($readPhoto)){
                        $data['code'] = 0;
                        $data['msg'] = '视频鉴权失败';
                    }else{
                        $data['code'] = 1;
                        $data['msg'] = '视频鉴权通过';
                    }
                }
            }
        }else{
            if($type=='itsme_image'){//图片
                if($ruleType==1){
                    $data['code'] = 1;
                    $data['msg'] = '照片鉴权通过';
                }elseif ($ruleType==2){
                    $data['code'] = 1;
                    $data['msg'] = '照片鉴权通过';
                }elseif ($ruleType==3){
                    $start = strtotime(date('Y-m-d').'00:00:00');
                    $end = strtotime(date('Y-m-d').'23:59:59');
                    $where['user_id'] = $otherUserId;
                    $where['photo_id'] = $photoId;
                    $where['createtime'] = ["between","$start,$end"];
                    $readPhoto = Db::name("user_photo_read")->where($where)->find();
                    if(empty($readPhoto)){
                        $data['code'] = 0;
                        $data['msg'] = '照片鉴权失败';
                    }else{
                        $data['code'] = 1;
                        $data['msg'] = '照片鉴权通过';
                    }
                }elseif ($ruleType==4){
                    //查找支付记录
                    $readPhoto = Db::name("user_money_log")->where(['type'=>1,'type_id'=>$photoId])->find();
                    if(empty($readPhoto)){
                        $data['code'] = 0;
                        $data['msg'] = '照片鉴权失败';
                    }else{
                        $data['code'] = 1;
                        $data['msg'] = '照片鉴权通过';
                    }
                }
            }elseif ($type=='itsme_video'){//视频
                if($ruleType==1){
                    $data['code'] = 1;
                    $data['msg'] = '视频鉴权通过';
                }elseif ($ruleType==2){
                    $data['code'] = 1;
                    $data['msg'] = '视频鉴权通过';
                }elseif ($ruleType==3){
                    $readPhoto = Db::name("user_photo_read")->where(['user_id'=>$otherUserId,'photo_id'=>$photoId])->find();
                    if(empty($readPhoto)){
                        $data['code'] = 0;
                        $data['msg'] = '视频鉴权失败';
                    }else{
                        $data['code'] = 1;
                        $data['msg'] = '视频鉴权通过';
                    }
                }elseif ($ruleType==4){
                    $readPhoto = Db::name("user_money_log")->where(['type'=>2,'type_id'=>$photoId])->find();
                    if(empty($readPhoto)){
                        $data['code'] = 0;
                        $data['msg'] = '视频鉴权失败';
                    }else{
                        $data['code'] = 1;
                        $data['msg'] = '视频鉴权通过';
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 用户任务鉴权
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view_user_tast_check($userId){
        //获取参数数据
        $userPermission = Db::name('user_permission')->field("id,send_task_num")->where(["user_id"=>$userId])->find(); //查询权限
        $taskCount = Db::name("user_task")->where(['user_id'=>$userId])->count(); //任务表

        //判断添加任务的权限
        $data = array();
        if($taskCount >= $userPermission['send_task_num']){
            $data['code'] = 2;
            $data['msg'] = '任务鉴权失败';
            return $data;
        }else{
            $data['code'] = 1;
            $data['msg'] = '任务鉴权通过';
            return $data;
        }
    }

    /**
     * 聊天次数鉴权
     * @param $userId
     * @param $toUserId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function send_chat_num_check($userInfo){
        //获取参数数据
        $user = $userInfo[0]; //用户信息
        $userId = $userInfo[0]['id']; //用户id号
        $toUserId = $userInfo[1]['id']; //接受者id号
        $gender = $userInfo[0]['gender'];  //用户性别

        //查询权限
        $userPermission = Db::name('user_permission')->field("send_chat_num,send_chat_num_gold")
            ->where(["user_id"=>$userId])
            ->find();

        //数据判断
        $data = []; //返回数据
        $time = time(); //当前时间

        //性别判断
        if($gender==1){
            //男性用户判断
            $userVip = Db::name('user_vip')->field("time_limit,end_time")
                ->where(["user_id"=>$userId])->order("createtime desc")->find();
            $startTime = $userVip['end_time']-$userVip['time_limit']*86400;
            $endTime = $userVip['end_time'];

            $where['user_id'] = $userId;
            $where['createtime'] = ["between","$startTime,$endTime"];
            //和别人聊天数据
            $chatFind = Db::name('user_permission_chat')
                ->field("id,user_id,to_user_id,gender,counts,prev,createtime")
                ->where($where)
                ->order("createtime desc")
                ->find();
            if(($chatFind != null) && ($chatFind["counts"]>=$userPermission['send_chat_num'])){
                $data['code'] = 2;
                $data['send_chat_num_gold'] = $userPermission['send_chat_num_gold'];
                $data['msg'] = 'vip超出和别人聊天次数,请充值金币';
            }else{
                $res = $this->insertPermissionChatlog($userId,$toUserId,1,$time,$chatFind,$startTime,$endTime);
                if($res){
                    $data['code'] = 1;
                    $data['msg'] = '通过鉴权';
                }else{
                    $data['code'] = 2;
                    $data['msg'] = '没有通过鉴权';
                }
            }
        }else{
            //判断参数 本月中时间戳
            $timeFirst = date('Y-m-01', strtotime(date("Y-m-d")));
            $timeEnd = date('Y-m-d', strtotime("$timeFirst +1 month -1 day"));
            $beginDate = strtotime($timeFirst);
            $endDate = strtotime($timeEnd);

            //女性用户判断
            $where['user_id'] = $userId;
            $where['createtime'] = ["between","$beginDate,$endDate"];
            $chatFind = Db::name('user_permission_chat')
                ->where($where)
                ->order("createtime desc")
                ->find(); //和别人聊天数据
            if(($chatFind != null) && ($chatFind["counts"]>=$userPermission['send_chat_num'])){
                $data['code'] = 2;
                $data['send_chat_num_gold'] = $userPermission['send_chat_num_gold'];
                $data['msg'] = '本月已经超出和别人聊天次数,请充值金币';
            }else{
                $res = $this->insertPermissionChatlog($userId,$toUserId,0,$time,$chatFind,$beginDate,$endDate);
                if($res){
                    $data['code'] = 1;
                    $data['msg'] = '通过鉴权';
                }else{
                    $data['code'] = 2;
                    $data['msg'] = '没有通过鉴权';
                }
            }
        }

        //返回数据
        return $data;
    }

    //判断发送方聊天权限
    public function send_chat_msg_check_sender($userId,$toUserId){
        //发送者权限：同性不能聊天 男-聊天次数限制-是否vip 聊天七天有效期判断 女-真人认证 身价值判断
        $data = [];
        $time = time();

        //用户数据
        $userInfo =Db::name("user")
            ->field("id,status,worth,gender,is_vip,certify")
            ->where("id","in",[$userId,$toUserId])
            ->select();

        if(count($userInfo)<=0){
            $data['code'] = 2;
            $data['msg'] = "参数有问题";
            return $data;
        }

        //1.男性vip判断，不是vip不能聊天
        if($userInfo[0]['gender']==1 && $userInfo[0]['is_vip']!=1){
            $data['code'] = 2;
            $data['msg'] = "请充值vip用户发起聊天";
            return $data;
        }
        //是vip判断时间
        if($userInfo[0]['gender']==1 && $userInfo[0]['is_vip']==1){
            $userVip = Db::name('user_vip')->field("end_time")
                ->where(["user_id"=>$userId])
                ->order("createtime desc")
                ->find();
            if($time>$userVip['end_time']){
                $data['code'] = 2;
                $data['msg'] = "请充值vip用户发起聊天2";
                return $data;
            }
        }

        //2.女性用户判断,如果不是真人认证不能发信息
        if($userInfo[0]['gender']==0){
            $certify = json_decode($userInfo[0]['certify'],true);
            if(!isset($certify['real']) || $certify['real']['verified']!='true'){
                $data['code'] = 2;
                $data['msg'] = "请真人认证";
                return $data;
            }
        }

        //3.同性判断
        if($userInfo[0]['gender']==$userInfo[1]['gender']){
            $data['code'] = 2;
            $data['msg'] = "同性不能发信息";
            return $data;
        }

        //4.判断是否是黑名单
        $checkBlack = Db::name('user_black')->where(['user_id'=>$toUserId,'to_user_id'=>$userId])->find();
        if($checkBlack){
            $data['code'] = 2;
            $data['msg'] = "对方已经把你加入黑名单";
            return $data;
        }

        //5.判断接受者账户，如果是正常才能发信息
        if($userInfo[1]['status']!='normal'){
            $data['code'] = 2;
            $data['msg'] = "对方账户被禁言或者冻结";
            return $data;
        }

        //6.身价值判断 -100~100 区间聊天
        $checkWorth = $userInfo[0]['worth']-$userInfo[1]['worth'];
        if($checkWorth>100){
            $data['code'] = 2;
            $data['msg'] = "对方身价值较低， 与您的身价与对方不匹配，无法进行深度沟通";
            return $data;
        }
        if($checkWorth<-100){
            $data['code'] = 2;
            $data['msg'] = "对方身价值较高， 与您的身价与对方不匹配，无法进行深度沟通";
            return $data;
        }

        //判断次数
        $checkNum = $this->send_chat_num_check($userInfo);
        if($checkNum['code']==1){
            $data['code'] = 1;
            $data['msg'] = "通过验证";
            return $data;
        }else{
            //次数判断不能发送聊天
            return $checkNum;
        }
    }

    //判断接受方聊天权限
    public function send_chat_msg_check_receiver($userId){
        //接受者权限：同性检测、黑名单检测、用户停用、用户禁言
        $data = [];
        $time = time();
        $userInfo =Db::name("user")->field("certify,worth,gender,is_vip")->where("id",$userId)->find();

        //1.男性vip判断，不是vip不能聊天
        if($userInfo['gender']==1 && $userInfo['is_vip']!=1){
            $data['code'] = 2;
            $data['msg'] = "请充值vip用户发起聊天";
            return $data;
        }
        //是vip判断时间
        if($userInfo['gender']==1 && $userInfo['is_vip']==1){
            $userVip = Db::name('user_vip')->field("end_time")
                ->where(["user_id"=>$userId])
                ->order("createtime desc")
                ->find();
            if($time>$userVip['end_time']){
                $data['code'] = 2;
                $data['msg'] = "请充值vip用户发起聊天";
                return $data;
            }
        }

        //2.女性判断，不是真人不能聊天
        if($userInfo['gender']==0){
            $certify = json_decode($userInfo['certify'],true);
            if(!isset($certify['real']) || $certify['real']['verified']!='true'){
                $data['code'] = 2;
                $data['msg'] = "请真人认证";
                return $data;
            }
        }

        $data['code'] = 1;
        $data['msg'] = "通过验证";
        return $data;
    }

    /**
     * 喜欢我鉴权
     * @param $userId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view_like_me_check($userId){
        //获取参数数据
        $userPermission = Db::name('user_permission')->where(["user_id"=>$userId])->find(); //查询权限

        //判断添加任务的权限
        $data = array();
        $data['code'] = $userPermission['view_like_me'];
        if($userPermission['view_like_me']==1){
            $data['msg'] = '可以查看喜欢我';
        }else{
            $data['msg'] = '不可以查看喜欢我';
        }
        return $data;
    }

    /**
     * 异性访客鉴权
     * @param $userId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view_opsex_guest_check($userId){
        //获取参数数据
        $userPermission = Db::name('user_permission')->where(["user_id"=>$userId])->find(); //查询权限

        //判断添加任务的权限
        $data = array();
        $data['code'] = $userPermission['view_opsex_guest'];
        if($userPermission['view_opsex_guest']==1){
            $data['msg'] = '可以查看异性访客';
        }else{
            $data['msg'] = '不可以查看异性访客';
        }
        return $data;
    }

    /**
     * 可送礼物鉴权
     * @param $userId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function can_gift_check($userId){
        //获取参数数据
        $userPermission = Db::name('user_permission')->where(["user_id"=>$userId])->find(); //查询权限

        //判断添加任务的权限
        $data = array();
        $data['code'] = $userPermission['can_gift'];
        if($userPermission['can_gift']==1){
            $data['msg'] = '可以送礼物';
        }else{
            $data['msg'] = '不可以送礼物';
        }
        return $data;
    }

    /**
     * 调用百度身份证接口
     * @param string $url
     * @param string $param
     * @return bool|string
     */
    protected function identity_request_post($url = '', $param = ''){
        if (empty($url) || empty($param)) {
            return false;
        }

        $postUrl = $url;
        $curlPost = $param;
        // 初始化curl
        $headers = array();
        array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $postUrl);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // post提交方式
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        // 运行curl
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }

    /**
     * 身份证识别
     * 测试图片地址
     * //$img = file_get_contents('http://www.ceshi.com/uploads/qian.jpg');
     * @param $imgurl
     * @param $type
     * @return mixed
     */
    public function realCheck($imgurl,$type){
        $Http = new Http();
        $params['grant_type'] = Config::get('BaiduIdentity.grant_type');
        $params['client_id'] = Config::get('BaiduIdentity.client_id');
        $params['client_secret'] = Config::get('BaiduIdentity.client_secret');
        $resInfo = $Http->sendRequest(config('BaiduFace.token_url'), $params);
        $token = $resInfo;
        $token = $token['msg'];
        $token = json_decode($token,true);
        $access_token = $token['access_token'];
        $url = Config::get('BaiduIdentity.api_url') . $access_token;

        //监测图片地址是否有效
        $curl = curl_init($imgurl);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        $result = curl_exec($curl);
        $found = false;
        // 如果请求没有发送失败
        if ($result !== false) {
            // 再检查http响应码是否为200
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($statusCode == 200) {
                $found = true;
            }
        }
        curl_close($curl);

        if($found){
            $img = file_get_contents($imgurl);
            $img = base64_encode($img);
            $bodys = array(
                'id_card_side' => $type==1?"front":"back",
                'image' => $img
            );
            $res = $this->identity_request_post($url, $bodys);
            $res = json_decode($res,true);
            return $res;
        }else{
            return [];
        }
    }

    /**
     * 银行卡认证判断
     * @param $param [bank_card-银行卡,bank_name-姓名,identity_card-身份证]
     * if($bankCheckData['code']=="0000" && $bankCheckData['result']==1){}
     * @return mixed
     */
    public function bankCheck($param){
        if(is_array($param) && !empty($param['bank_card'] && !empty($param['bank_name']) && !empty($param['identity_card']))){
            //阿里云第三方银行卡接口验证数据
            $host = Config::get("bank_check.host");
            $path = Config::get("bank_check.path");
            $method = "POST";
            $appcode = Config::get("bank_check.appcode");
            $headers = array();
            array_push($headers, "Authorization:APPCODE " . $appcode);
            //根据API的要求，定义相对应的Content-Type
            array_push($headers, "Content-Type".":"."application/x-www-form-urlencoded; charset=UTF-8");
            $querys = "";
            $bodys = "accountNo=".$param['bank_card']."&idName=".$param['bank_name']."&idNumber=".$param['identity_card'];
            $url = $host . $path;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            if (1 == strpos("$".$host, "https://"))
            {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
            //{"code":"0000","msg":"处理成功","result":"1","resultMsg":"认证信息匹配","requestId":"20210126111850372_92o7k09g"}
            $bankCheckData = curl_exec($curl);
            $bankCheckData = json_decode($bankCheckData,true);
            return $bankCheckData;
        }else{
            $str = '{"code": "I005","msg": "业务异常 ","result": "-1","resultMsg": "请求身份证号不标准：身份证号为空或者不符合身份证校验规范","requestId": "20200720113810361_le805776"}';
            $bankCheckData = json_decode($str,true);
            return $bankCheckData;
        }
    }

    /**
     * 判断是否是vip
     * @param $userId
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkVip($userId){
        $data = Db::name("user")->where(['id'=>$userId])->find();
        $isVip = $data['is_vip'];
        return $isVip;
    }

    /**
     * 判断是否是女神
     * @param $userId
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkGirl($userId){
        //goddess
        $data = Db::name('user')->where(['id'=>$userId])->find();
        $certify = json_decode($data['certify'],true);
        if(!empty($certify)){
            if(array_key_exists("goddess",$certify)){
                if($certify['goddess']['verified']){
                    return 1;
                }else{
                    return 0;
                }
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }

    /**
     * 判断男女
     */
    protected function checkBoy($userId){
        $data = Db::name('user')->field("gender")->where(['id'=>$userId])->find();
        if($data['gender']){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 添加用户游览数据方法
     * @param $otherUserId
     * @param $user
     * @param $visitList
     * @return bool
     */
    protected function insertPermissionLog($otherUserId,$user,$visitList,$type=1){
        $visitData["user_id"] = $user->id;
        $visitData["other_user_id"] = $otherUserId;
        $visitData["gender"] = $user->gender;
        $visitData["type"] = $type; //1每日查看用户主页类型 2每日查看女神主页
        if(empty($visitList)){
            //添加游览记录
            $visitData["counts"] = 1;
            $visitData["visit_id_prev"] = 0;
            $UserPermissionLog = UserPermissionLog::create($visitData);
        }else{
            //添加游览记录
            $count = $visitList['counts']+1;
            $visitData["counts"] = $count;
            $visitData["visit_id_prev"] = $visitList['id'];
            $UserPermissionLog = UserPermissionLog::create($visitData);
        }
        if($UserPermissionLog){
            //创建任务： 项目命名空间\模块\文件夹\控制器@方法
            $job = "app\job\controller\FootPrint@do";//$job = 任务名称
            $userFootprint = new UserFootprint(); //用户浏览记录表
            $visitListData = $userFootprint->select_view_user_home($user->id,$type); //查询游览次数
            if(!empty($visitListData)){
                $visit_id_prev = $visitListData['id'];
            }else{
                $visit_id_prev = 0;
            }
            $data = [$user->id,$otherUserId,$type,$visitData['counts'],$visit_id_prev];//$data = 数据
            $queueName = 'do_footprint';//null 指定任务名称，没有则使用默认
            $do_like = Queue::push($job,$data, $queueName);
            if( $do_like !== false ){
                Log::write('点击足迹任务创建成功！');
                return true;
            }else{
                Log::write('点击足迹任务创建失败！');
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 添加用户和别人聊天记录次数方法
     * @param $userId
     * @param $toUserId
     * @param $gender
     * @param $time
     * @return bool
     */
    protected function insertPermissionChatlog($userId,$toUserId,$gender,$time,$chatList,$startTime,$endTime){
        //判断上一条是否为空
        if(empty($chatList)){
            $chatData["user_id"] = $userId;
            $chatData["to_user_id"] = $toUserId;
            $chatData["gender"] = $gender;
            $chatData["counts"] = 1;
            $chatData["prev"] = 0;
            $chatData["createtime"] = $time;
            $chatData["updatetime"] = $time;
            $res = Db::name("user_permission_chat")->insert($chatData);
            if($res){
                $chatDataLog["type"] = 2;
                $chatDataLog["user_id"] = $userId;
                $chatDataLog["other_user_id"] = $toUserId;
                $chatDataLog["gender"] = $gender;
                $chatDataLog["counts"] = 1;
                $chatDataLog["visit_id_prev"] = 0;
                $chatDataLog["createtime"] = $time;
                $chatDataLog["updatetime"] = $time;
                Db::name("user_permission_log")->insert($chatDataLog);
                return true;
            }else{
                return false;
            }
        }else{
            $chatData["user_id"] = $userId;
            $chatData["to_user_id"] = $toUserId;
            $chatData["gender"] = $gender;
            $counts = $chatList['counts']+1;
            $chatData["prev"] = $chatList['id'];
            $chatData["counts"] = $counts;
            $chatData["createtime"] = $time;
            $chatData["updatetime"] = $time;

            $chatWhere['user_id'] = $userId;
            $chatWhere['to_user_id'] = $toUserId;;
            $chatWhere['gender'] = $gender;
            //男的是会员时间
            //女的是月时间
            $chatWhere['createtime'] = ["between","$startTime,$endTime"];
            $checkChat = Db::name("user_permission_chat")->field("createtime")->where($chatWhere)->find();
            if($checkChat == null){
                $res = Db::name("user_permission_chat")->insert($chatData);
                if($res){
                    $chatDataLog["type"] = 2;
                    $chatDataLog["user_id"] = $userId;
                    $chatDataLog["other_user_id"] = $toUserId;
                    $chatDataLog["gender"] = $gender;
                    $chatDataLog["counts"] = $counts;
                    $chatDataLog["visit_id_prev"] = $chatList['id'];
                    $chatDataLog["createtime"] = $time;
                    $chatDataLog["updatetime"] = $time;
                    Db::name("user_permission_log")->insert($chatDataLog);
                    return true;
                }else{
                    return false;
                }
            }else{
                //判断七天数据,当前时间-上次游览时间
                $checkSevenDay = round(($time - $checkChat['createtime'])/86400,2);
                if($checkSevenDay>=7){
                    $res = Db::name("user_permission_chat")->insert($chatData);
                    $chatDataLog["type"] = 2;
                    $chatDataLog["user_id"] = $userId;
                    $chatDataLog["other_user_id"] = $toUserId;
                    $chatDataLog["gender"] = $gender;
                    $chatDataLog["counts"] = $counts;
                    $chatDataLog["visit_id_prev"] = $chatList['id'];
                    $chatDataLog["createtime"] = $time;
                    $chatDataLog["updatetime"] = $time;
                    Db::name("user_permission_log")->insert($chatDataLog);
                }
                return true;
            }
        }

    }
}
