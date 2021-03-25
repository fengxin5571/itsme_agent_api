<?php
/**
 * Created by PhpStorm.
 * User: fengxin
 * Date: 2021/3/24
 * Time: 9:14 AM
 */
namespace app\api\Impl;
use app\common\exception\BusinessException;
use app\common\model\Agent;
use app\common\model\User;
use think\Validate;

/**
 * 艺人服务类
 * Class ArtistsServerice
 * @package app\api\Impl
 */
class ArtistsService  {
    /**
     * 我的艺人列表
     * @param $search 查询条件
     * @param $sex 姓名
     * @param $agent_id 登录经纪人id
     * @param $location 经纪人当前位置
     * @param int $limit 分页
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function artist_list($search,$sex,$agent_id,$location,$limit=10){
        $where=['status'=>'normal','p_agent_id'=>$agent_id];
        if(Validate::regex($search, '/^1\d{10}$/')){//如果是手机号
            $where['mobile']=$search;
            unset($where['gender']);
        }
        $artist_list=User::where($where)->where('gender',$sex)
            ->field('id,nickname,mobile,avatar,age,gender,is_online,prevtime,certify,worth,is_vip,(st_distance (point (lng,lat),point ('.$location['lng'].','.$location['lat'].'))*111195 )as distance')->paginate($limit)
            ->each(function ($artist,$k){
                $artist->server_time=time();
                $artist->certify=json_decode($artist->certify);
                //格式化距离
                $artist->distance=$this->formatDistance($artist->distance);
            });
        $artist_total=$artist_list->isEmpty()?0:$artist_total=User::where($where)->count("id");
        $artist_list=array_merge(['artist_total'=>$artist_total],$artist_list->toArray());
        return $artist_list;
    }
    /**
     * 艺人详情
     * @param $location 经纪人位置
     * @param $user_id 用户id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws BusinessException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function artist_info($location,$user_id){
        $artist_info=User::field('id,nickname,mobile,avatar,age,height,weight,
        (select GROUP_CONCAT(name separator \'/\') from itsme_area where id in (province,city))as czd,gender,is_online,prevtime,createtime,certify,worth,is_vip,
        (st_distance (point (lng,lat),point ('.$location['lng'].','.$location['lat'].'))*111195 )as distance')
            ->with(['userPhotos'=>function($query){
            $query->limit(3);
        }])->withCount('userPhotos')->where('id',$user_id)
            ->find();
        if(!$artist_info){
            throw  new BusinessException('无此对应的用户');
        }
        $artist_info['certify']=json_decode($artist_info['certify'],true);
        //格式化距离
        $artist_info['distance']=$this->formatDistance($artist_info['distance']);
        $artist_info['server_time']=time();
        return $artist_info;
    }
    /**
     * 距离格式化
     * @param $distance
     * @return string
     */
    protected function formatDistance($distance){
        if($distance>=1000){//大于1000m
            $format=(round(intval($distance)/1000))."km";
        }elseif ($distance>100){//小于1000m大于100m
            $format=intval($distance)."m";
        }elseif ($distance<100){//小于100m
            $format='距您 <100m';
        }
        return $format;
    }
}