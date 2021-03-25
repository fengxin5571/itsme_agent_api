<?php

namespace app\common\model;

use app\api\controller\Expect;
use think\Model;
use redis\RedisPackage;
use think\Db;
use app\common\model\UserPhoto;
/**
 * 会员模型
 */
class User extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    // 追加属性
    protected $append = [
        'url',
    ];
    protected $hidden= ['password','token','salt'];
    //会员相册
    public function userPhotos(){
        return $this->hasMany('UserPhoto','user_id')
            ->field('id,user_id,url,type,rule_type,price');
    }










    /**
     * 获取其他会员信息
     * @param mixed $user
     * @return object
     */
    public static function getUserInfo($user_id,$self_user_id='')
    {
        $data = [];
        //TODO:查询redis会员信息
        $user = self::field('id,nickname,avatar,gender,age,bio,money,outtime,status,height,weight,province,city,constellation,worth,certify,is_online,invite_code,p_user_id,is_vip')->where(['id'=>$user_id])->find();
        if($user){
            //修改城市编码为汉字
            $dizhi = Area::get($user['city']);
            $user['city'] = $dizhi['name'];
            $user = json_decode(json_encode($user),true);
            $UserExtendInfo = self::getUserExtendInfo($user_id,['user_photo','user_tag','user_certify','user_vip'],$self_user_id);
            $data = array_merge($user,$UserExtendInfo,['server_time'=>time()]);
        }
        return $data;
    }

    public static function getUserMainShow($userId,$userArr){
        $userIds = array_column($userArr,"id");
        $where['id'] = ["in",$userIds];
        $userIdsStr = implode(",",$userIds);
        $userList = self::field('id,nickname,avatar,gender,age,city,constellation,worth,certify,is_vip,is_online,outtime')
            ->where($where)
            ->orderRaw("field(id,".$userIdsStr.")")
            ->select();

        //距离数据
        $userDistance = array_column($userArr,"distance");
        $distanceArr = array_combine($userIds,$userDistance);

        //喜欢查询
        $likeArr = [];
        if ($userId) {
            $likeWhere['user_id'] = $userId;
            $likeWhere['to_user_id'] = ["in",$userIds];
            $likeInfo = Db::name('user_like')->field("id,user_id,to_user_id")
                ->where($likeWhere)
                ->orderRaw("field(to_user_id,".$userIdsStr.")")
                ->select();
            $likeArr = array_column($likeInfo,"to_user_id");
        }

        // 职业查询
        $expect_type_id = Db::name('expect_type')->field('id')->where(['type_name' => '我的职业'])->column('id');
        if (!empty($expect_type_id)) {
            $expect = Db::name('expect')->field('id, name')->whereIn('type_id', $expect_type_id)->select();
        }


        //组装数据
        foreach($userList as $key=>$val){
            $dizhi = Area::get($val['city']);
            $userList[$key]['city'] = $dizhi['name'];
            if($distanceArr[$val['id']]<1){
                $distance = intval(($distanceArr[$val['id']]*1000)).'m';
                if($distance>=0 || $distance<=100){
                    $distance = "100m以内";
                }
            }else{
                $distance = (intval($distanceArr[$val['id']])).'km';
            }
            $userList[$key]['server_time'] = time();
            $userList[$key]['distance'] = $distance;
            $userList[$key]['like'] = in_array($val['id'],$likeArr)?1:0;

            //职业查询
            $profession = '';
            if (!empty($expect)) {
                foreach ($expect as $item) {
                    $d = Db::name('user_expect')->field('id')->where(['user_id' => $val['id'], 'expect_id' => $item['id']])->find();
                    if (!empty($d)) {
                        $profession = Db::name('expect')->field('name')->where('id', $item['id'])->value('name');
                    }
                }
            }
            $userList[$key]['profession'] = $profession;
        }

        return $userList;
    }

    /**
     * 获取用户推荐排序
     * @param $user_id
     * @param $where
     * @param $lat
     * @param $lng
     * @param $is_nearby true附近 false不是附近
     */
    public function recommendOrder($user_id, $where, $lat, $lng, $is_nearby)
    {
        if (empty($user_id)) {
            $user_id = 0;
        }

        // debug('begin');
        $arr = Db::name('user')
            ->field([
                'id',
                'instr(`itsme_user`.certify, \'{"desc":"女神","short":"女","verified":"true"}\') as `goddess`',
                'instr(`itsme_user`.certify, \'{"desc":null,"short":null,"verified":"true"}\') as `real`',
                'worth',
                'outtime',
                'is_online',
                'is_vip',
                'age',
                'city',
                'gender',
                '(  
                    6378.137 * acos (
                        cos ( radians('.$lat.') )
                        * cos( radians( lat ) )
                        * cos( radians( lng ) - radians('.$lng.') )
                        + sin ( radians('.$lat.') )
                        * sin( radians( lat ) )
                    )
                ) AS distance'
            ])
            // ->fetchSql(true)
            ->where($where)
            ->select();
        // debug('end');
        // echo debug('begin', 'end') . 's';
        // dump($arr);
        // echo $arr;
        // die;

        if (empty($arr)) {
            return [];
        }


        // 目前的需求是把女性不是真人认证的干掉
        $new_arr = $arr;
        foreach ($arr as $index => $user) {
            if ($user['gender'] === 0 && $user['real'] === 0 || $user['gender'] === 0 && $user['real'] == null) {
                unset($new_arr[$index]);
            }
        }
        $arr = $new_arr;

        /**
         * where只有nickname比较特殊，不需要过多的处理，把所有模糊查到的用户列出来
         * 按照距离最小排序
         */
        if (!empty($where['nickname'])) {
            // 距离
            array_sort($arr, 'distance', "SORT_ASC");
            return $arr;
        }

        $arr = array_column($arr, NULL, 'id');

        if ($user_id) {// 登录
            if ($where['gender'] === 1) {
                // 查出来是男用户
                $data = $this->_nv_group1($arr);
            } elseif ($where['gender'] === 0) {
                // 查出来是女用户
                $gr1 = $this->_nan_group1($arr, $user_id);
                $gr2 = $this->_nan_group2($arr);
                $gr3 = $this->_nan_group3($arr);
                $data = $gr1 + $gr2 + $gr3;
            }
            // 通过上面的筛选规则会筛掉一部分数据，把不符合规则的用户拼接到最后面
            if (count($arr) != count($data)) {
                $diff_key = array_diff_key($arr, $data);
                $data = $data + $diff_key;
            }
            // todo gender为2时用什么规则？
            if ($is_nearby) {
                // 是附近
                array_sort($data, 'distance', SORT_ASC);
            } else {
                // 不是附近
                /**
                 * 排序
                 * 在线<距离小到大<真人
                 */
                array_sort($data, 'real', SORT_DESC);
                array_sort($data, 'goddess', SORT_DESC);
                array_sort($data, 'is_vip', SORT_DESC);
                // array_sort($data, 'distance', SORT_ASC);
                array_sort($data, 'is_online', SORT_DESC);
            }

        } else { // 未登录
            $data = $this->_no_login_group($arr);
            // 未登录数组数据一共100个，30个30个给前端
            $keys = array_keys($data);
            //打乱数组的键排序
            shuffle($keys);
            $result = [];
            foreach ($keys as $key){
                $result[$key] = $data[$key];
            }
            $data = array_slice($result, 0, 100);

            if ($is_nearby) {
                // 按距离排序
                array_sort($data, 'distance', "SORT_ASC");
            }
        }
        return $data;
    }

    /**
     * 获取用户推荐排序（未登录）
     */
    public function _no_login_group($arr)
    {
        // 认证 女神&真人
        $certify = $this->_goddessAuth($arr);
        // 真人认证
        $real = $this->_realPersonAuth($arr);
        // vip认证
        $vip = $this->_vipAuth($arr);
        // 离线时间3天以内
        $out = $this->_outtime($arr, 259200);
        // 15岁~30岁
        $age = $this->_age($arr, [15, 30]);

        $data = $certify + $real + $vip + $out + $age;

        // 未登录列表用户全部在线状态
        foreach ($data as $index => &$item) {
            $item['is_online'] = 1;
        }
        unset($item);

        return $certify + $real + $vip + $out + $age;
    }

    /**
     * 男组1
     */
    private function _nan_group1($arr,$user_id)
    {
        // todo 太卡了，arr太大，循环sql查询不行
        //      改成redis
        // 期望关联项
        // $expect = $arr;
        // foreach ($expect as $index => &$item) {
        //     $expect_match = Expect::match1($user_id, $item['id']);
        //     $item['expect_num'] = $expect_match['num'];
        // }
        // unset($item);
        // uasort($expect, function ($a, $b) {
        //     if ($a['expect_num'] == $b['expect_num']) return 0;
        //     return ($a['expect_num'] < $b['expect_num']) ? 1 : -1;
        // });

        // 认证 女神&真人
        $certify = $this->_goddessAuth($arr);
        // 身价值 > 180
        // $worth = $this->_worth($arr, ['>', 180]);
        // 离线时间3天以内
        $out = $this->_outtime($arr, 259200);
        // 距离 < 50公里
        $distance = $this->_distance($arr, ['<', 50]);

        return $certify + $out + $distance;
        // return $certify + $worth + $out + $distance;
        // return $expect + $certify + $worth + $out + $distance;
    }

    /**
     * 男组2
     */
    private function _nan_group2($arr)
    {
        // 认证 女神&真人
        $certify = $this->_goddessAuth($arr);
        // 身价值 > 180
        // $worth = $this->_worth($arr, ['>', 180]);
        // 离线时间7天以内
        $out = $this->_outtime($arr, 604800);
        // 距离 < 300公里
        $distance = $this->_distance($arr, ['<', 300]);

        return $certify + $out + $distance;
        // return $certify + $worth + $out + $distance;
    }

    /**
     * 男组3
     */
    private function _nan_group3($arr)
    {
        // 身价值 > 50
        // $worth = $this->_worth($arr, ['>', 50]);
        // 离线时间15天以内
        $out = $this->_outtime($arr, 1296000);
        // 距离 > 10000公里
        $distance = $this->_distance($arr, ['>', 10000]);

        return $out + $distance;
        // return $worth + $out + $distance;
    }

    /**
     * 女组1
     */
    private function _nv_group1($arr)
    {
        // 真人认证
        $real = $this->_realPersonAuth($arr);
        // vip认证
        $vip = $this->_vipAuth($arr);
        // 身价值 > 180
        // $worth = $this->_worth($arr, ['>', 180]);
        // 离线时间3天以内
        $out = $this->_outtime($arr, 259200);
        // 距离 > 50公里
        $distance = $this->_distance($arr, ['>', 10000]);

        return $real + $vip + $out + $distance;
        // return $real + $vip + $worth + $out + $distance;
    }

    /**
     * 真人认证
     */
    private function _realPersonAuth($arr)
    {
        return array_filter($arr, function ($item) {
            return $item['real'] > 0;
        });
    }

    /**
     * vip认证
     */
    private function _vipAuth($arr)
    {
        return array_filter($arr, function ($item) {
            return $item['is_vip'] == 1;
        });
    }

    /**
     * 女神真人认证
     */
    private function _goddessAuth($arr)
    {
        return array_filter($arr, function ($item) {
            $num = 0;
            if ($item['goddess'] > 0) {
                $num++;
            }
            if ($item['real'] > 0) {
                $num++;
            }
            if ($num == 2) return $item;
        });
    }

    /**
     * 身价分以内，身价分以外
     * @param $arr
     * @param $condition[0] 运算符
     * @param $condition[1] 身价分
     */
    private function _worth($arr, $condition)
    {
        return array_filter($arr, function ($item) use ($condition) {
            if ($condition[0] == '>') {
                return $item['worth'] > $condition[1];
            }
            if ($condition[0] == '<') {
                return $item['worth'] < $condition[1];
            }
        });
    }

    /**
     * 离线时间几天以内
     * @param $condition 几天以内（毫秒）
     */
    private function _outtime($arr, $condition)
    {
        return array_filter($arr, function ($item) use ($condition) {
            $t = time() - $item['outtime'];
            return $t < $condition;
        });
    }

    /**
     * 几公里以内，几公里以外
     * @param $arr
     * @param $condition[0] 运算符
     * @param $condition[1] 公里数
     */
    private function _distance($arr, $condition)
    {
        return array_filter($arr, function ($item) use ($condition) {
            if ($condition[0] == '>') {
                return $item['distance'] > $condition[1];
            }
            if ($condition[0] == '<') {
                return $item['distance'] < $condition[1];
            }
        });
    }

    /**
     * 年龄以内
     */
    private function _age($arr, $condition)
    {
        return array_filter($arr, function ($item) use ($condition) {
            if ($item['age'] > $condition[0] && $item['age'] < $condition[1]) {
                return $item;
            }
        });
    }

    public static function getUserInfoSimple($userId,$type=0,$typeId=0)
    {
        $data = [];
        //TODO:查询redis会员信息
        if($type==0){
            $user = self::field('id,nickname,avatar,gender,age,city,constellation,worth,certify,is_vip')->where(['id'=>$userId])->find();
        }else{
            //查询任务所在发
            $addressInfo = Db::name("user_task")->field("lng,lat")->where(['id'=>$typeId])->find();
            $user = self::field('id,nickname,avatar,gender,age,city,constellation,worth,certify,is_vip,(  
                            6378.137 * acos (
                              cos ( radians('.$addressInfo['lat'].') )
                              * cos( radians( lat ) )
                              * cos( radians( lng ) - radians('.$addressInfo['lng'].') )
                              + sin ( radians('.$addressInfo['lat'].') )
                              * sin( radians( lat ) )
                            )
                        ) AS distance')->where(['id'=>$userId])->find();
        }
        if($user){
            //修改城市编码为汉字
            $dizhi = Area::get($user['city']);
            $user['city'] = $dizhi['name'];
            //职业查询 profession
            $configUserTag = Db::name("config_user_tag")->field("id")->where(["code"=>"profession"])->find();
            $userTag = Db::name("user_tag")->field("id,item_id")->where(["tag_id"=>$configUserTag['id'],"user_id"=>$userId])->find();
            if($userTag){
                $configUserTagItem = Db::name("config_user_tag_item")->field("name")->where(["id"=>$userTag['item_id']])->find();
                $user['profession'] = $configUserTagItem['name'];
            }else{
                $user['profession'] = "";
            }
            $user = json_decode(json_encode($user),true);
            $data = $user;
        }
        unset($data['url']);
        return $data;
    }

    /**
     * 获取会员额外信息
     * @param mixed $user
     * @return object
     */
    public static function getUserExtendInfo($user_id,$extend,$self_user_id='')
    {
        $data = [];
        if(in_array('user_vip',$extend)){
            $uservip = Db::name('user_vip')->field('vip_name,time_limit,end_time')->where('user_id',$user_id)->find();
            if($uservip){
                $data['user_vip'] = $uservip;
            }else{
                $data['user_vip'] = [];
            }
        }
        if(in_array('user_certify',$extend)){
            $redis = new RedisPackage();
            $usercertify = $redis::$handler->get('usercertify:'.$user_id);
            if(!$usercertify){
                $usercertify = Db::name('user_certify')->where('user_id',$user_id)->select();
                if(!$usercertify){
                    $usercertify = '';
                }else{
                    $usercertify = json_encode($usercertify);
                    self::setUserCertify($user_id,$usercertify);
                }
            }else{
                $usercertify = json_decode($usercertify,true);
            }
            $data['user_certify'] = $usercertify;
        }
        if(in_array('user_photo',$extend)){
            /* $redis = new RedisPackage();
            $userphoto = $redis::$handler->get('userphoto:'.$user_id);
            if(!$userphoto){
                $userphoto = Db::name('user_photo')->where('user_id',$user_id)->select();
                if(!$userphoto){
                    $userphoto = '';
                }else{
                    $userphoto = json_encode($userphoto);
                    UserPhoto::setUserPhoto($user_id,$userphoto);
                }
            }else{
                $userphoto = json_decode($userphoto,true);
            } */
            $userphoto = Db::name('user_photo')->field('id,url,type,rule_type,price')->where('user_id',$user_id)->order('id desc')->limit(6)->select();
            foreach($userphoto as $k=>$v){
                $user_photo_read = Db::name('user_photo_read')->where(['user_id'=>$self_user_id,'photo_id'=>$v['id']])->value('id');
                $userphoto[$k]['is_read'] = $user_photo_read ? 1 : 0;
                $userphoto[$k]['price'] = (int)$v['price'];
            }
            $data['user_photo'] = $userphoto;
        }
        if(in_array('user_tag',$extend)){
            $user_tag = Db::name('config_user_tag')->field('id,name,code')->where(["step"=>['in',['1','2']]])->order("step desc")->select();
            foreach($user_tag as $k=>$v){
                $user_tag_1 = Db::name('user_tag')->field('tag_id,item_id,content')->where(['user_id'=>$user_id,'tag_id'=>$v['id']])->select();
                if($user_tag_1){
                    foreach($user_tag_1 as $g=>$h){
                        if($v['code']=='czd'){
                            $user_tag[$k]['content'] = Area::get($h['content'])['name'];
                        }else{
                            $user_tag[$k]['content'] = $h['content'] ? $h['content'] : '';
                            if($h['item_id']){
                                $user_tag[$k]['item'][] = Db::name('config_user_tag_item')->field('id,name')->where(['id'=>$h['item_id']])->find();
                            }
                        }
                    }
                }else{
                    $user_tag[$k]['content'] = null;
                }
            }
            $data['user_tag'] = $user_tag;
        }
        return $data;
    }

    /**
     * 保存用户资料到redis
     * @param mixed $user
     * @return object
     */
    public static function setUserInfo($user)
    {
        $redis_user = json_encode($user);
        $redis = new RedisPackage();
        $redis::$handler->set('userinfo:'.$user->id, $redis_user);
        return true;
    }



    /**
     * 保存用户认证信息到redis
     * @param mixed $user
     * @return object
     */
    public static function setUserCertify($user_id,$user_certify)
    {
        $redis = new RedisPackage();
        $redis::$handler->set('usercertify:'.$user_id, $user_certify);
        return true;
    }

    /**
     * 保存用户标签信息到redis
     * @param mixed $user
     * @return object
     */
    public static function setUserTag($do='set',$user_id,$type,$tag_id,$item_id=0,$content='')
    {
        $redis = new RedisPackage();
        if($do=='set'){
            $redis::$handler->set('usertag:tag_id-'.$tag_id.'-type-'.$type.'-user_id-'.$user_id.'-item_id-'.$item_id, $content);
        }elseif($do=='del'){
            $keys = $redis::$handler->keys('usertag:tag_id-'.$tag_id.'-type-'.$type.'-user_id-'.$user_id.'-item_id-*');
            if($keys){
                foreach($keys as $k=>$v){
                    $redis::$handler->del($v);
                }
            }
        }
        return true;
    }

    /**
     * 保存用户权限信息到redis
     * @param mixed $user
     * @return object
     */
    public static function setUserPermission($do='set',$user_id,$content='')
    {
        $redis = new RedisPackage();
        if($do=='set'){
            $redis::$handler->set('userpermission:'.$user_id, $content);
        }elseif($do=='del'){
            $keys = $redis::$handler->keys('userpermission:'.$user_id);
            if($keys){
                foreach($keys as $k=>$v){
                    $redis::$handler->del($v);
                }
            }
        }
        return true;
    }

    /**
     * 获取个人URL
     * @param   string $value
     * @param   array  $data
     * @return string
     */
    public function getUrlAttr($value, $data)
    {
        return "/u/" . $data['id'];
    }

    /**
     * 获取头像
     * @param   string $value
     * @param   array  $data
     * @return string
     */
    public function getAvatarAttr($value, $data)
    {
        if (!$value) {
            //如果不需要启用首字母头像，请使用
            //$value = '/assets/img/avatar.png';
            $value = letter_avatar($data['nickname']);
        }
        return $value;
    }

    /**
     * 获取会员的组别
     */
    public function getGroupAttr($value, $data)
    {
        return UserGroup::get($data['group_id']);
    }

    /**
     * 获取验证字段数组值
     * @param   string $value
     * @param   array  $data
     * @return  object
     */
    public function getVerificationAttr($value, $data)
    {
        $value = array_filter((array)json_decode($value, true));
        $value = array_merge(['email' => 0, 'mobile' => 0], $value);
        return (object)$value;
    }

    /**
     * 设置验证字段
     * @param mixed $value
     * @return string
     */
    public function setVerificationAttr($value)
    {
        $value = is_object($value) || is_array($value) ? json_encode($value) : $value;
        return $value;
    }

    /**
     * 加会员余额
     * @param int $money 余额
     * @param int $user_id 会员ID
     * @param int $type 类型
     * @param int $type_id 类型ID
     * @param string $memo 备注
     */
    public static function addMoney($money, $user_id, $type = 0, $type_id = 0, $memo = '')
    {
        $user = self::get($user_id);
        if ($user && $money != 0) {
            $before = $user->money;
            $after = $before + $money;
            // 1.更新会员余额
            $user->save(['money' => $after]);
            // 2.写入money_log日志表
            MoneyLog::create([
                'user_id' => $user_id,
                'money'   => $money,
                'before'  => $before,
                'after'   => $after,
                'memo'    => $memo,
                'type'    => $type,
                'type_id' => $type_id
            ]);
            // 3.更新会员redis
            self::setUserInfo($user);
            return true;
        }
        return false;
    }

    /**
     * 减会员余额
     * @param int $money 余额
     * @param int $user_id 会员ID
     * @param int $type 类型
     * @param int $type_id 类型ID
     * @param string $memo 备注
     */
    public static function subtractMoney($money, $user_id, $type = 0, $type_id = 0, $memo = '')
    {
        $user = self::get($user_id);
        if ($user && $money != 0 && $user->money >= $money) {
            $before = $user->money;
            $after = $before - $money;
            // 1.更新会员余额
            $user->save(['money' => $after]);
            // 2.写入money_log日志表
            MoneyLog::create([
                'user_id' => $user_id,
                'money'   => $money,
                'before'  => $before,
                'after'   => $after,
                'memo'    => $memo,
                'type'    => $type,
                'type_id' => $type_id
            ]);
            // 3.更新会员redis
            self::setUserInfo($user);
            return true;
        }
        return false;
    }

    /**
     * 变更会员积分
     * @param int    $score   积分
     * @param int    $user_id 会员ID
     * @param string $memo    备注
     */
    public static function score($score, $user_id, $memo)
    {
        $user = self::get($user_id);
        if ($user && $score != 0) {
            $before = $user->score;
            $after = $user->score + $score;
            $level = self::nextlevel($after);
            //更新会员信息
            $user->save(['score' => $after, 'level' => $level]);
            //写入日志
            ScoreLog::create(['user_id' => $user_id, 'score' => $score, 'before' => $before, 'after' => $after, 'memo' => $memo]);
        }
    }

    /**
     * 根据积分获取等级
     * @param int $score 积分
     * @return int
     */
    public static function nextlevel($score = 0)
    {
        $lv = array(1 => 0, 2 => 30, 3 => 100, 4 => 500, 5 => 1000, 6 => 2000, 7 => 3000, 8 => 5000, 9 => 8000, 10 => 10000);
        $level = 1;
        foreach ($lv as $key => $value) {
            if ($score >= $value) {
                $level = $key;
            }
        }
        return $level;
    }

    /**
     * 编辑会员是否vip
     * @param int $user_id 用户id
     * @param int $is_vip 是否VIP 1是0否
     */
    public function editIsVip($user_id, $is_vip = 0)
    {
        $this->save(['is_vip' => $is_vip], ['id' => $user_id]);
    }

    /**
     * 加身价分
     * @param int $user_id 会员id
     * @param int $worth 身价分
     */
    public function editWorth($user_id = 0, $worth = 0)
    {
        $this->where(['id' => $user_id])->setInc('worth', $worth);
    }

    /**
     * 邀请码获取用户对象
     * @param $invite_code 邀请码
     */
    public static function getByInviteCode($invite_code)
    {
        $p_user = self::get(function ($query) use ($invite_code) {
            $query->where('invite_code', $invite_code);
        });

        return $p_user;
    }

    public static function userDistance($userId,$toOtherId){
        if($userId>0 && $toOtherId>0){
            $userIdInfo = self::where(['id'=>$userId])->field("lng,lat")->find();
            $lng = $userIdInfo['lng'];
            $lat = $userIdInfo['lat'];
            $toOtherIdInfo = self::where(['id'=>$toOtherId])->field("(  
                    6378.137 * acos (
                        cos ( radians($lat) )
                        * cos( radians( lat ) )
                        * cos( radians( lng ) - radians($lng) )
                        + sin ( radians($lat) )
                        * sin( radians( lat ) )
                    )
                ) AS distance")->find();

            if($toOtherIdInfo['distance']<1){
                $distance = intval(($toOtherIdInfo['distance']*1000)).'m';
                if($distance>=0 || $distance<=100){
                    $distance = "距您 <100m";
                }
            }else{
                $distance = (intval($toOtherIdInfo['distance'])).'km';
            }
            return $distance;
        }else{
            return "距您 <100m";
        }
    }
}
