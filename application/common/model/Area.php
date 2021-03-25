<?php

namespace app\common\model;

use think\Cache;
use think\Model;
use think\Db;
use redis\RedisPackage;

/**
 * 地区数据模型
 */
class Area extends Model
{

    /**
     * 获取和对方的距离
     * @param string $user_id 自己id
     * @param string $to_user_id 对方id
     */
    public static function getDistance($user_id, $to_user_id)
    {
        //GEODIST key member1 member2
        $redis = new RedisPackage();
        $Distance = $redis::$handler->GEODIST('geoitsme:',$user_id,$to_user_id,'km');
        return $Distance;
    }

    /**
     * 获取省
     */
    public static function getProvince()
    {
        $province = cache('area:province');
        if(!$province){
            $province = Db::name('area')->field('id,name')->where('pid',0)->select();
            $province = json_encode($province,JSON_UNESCAPED_UNICODE);
            cache('area:province',$province);
        }
        return $province;
    }

    /**
     * 获取市
     *
     * @param string $pid 省id
     */
    public static function getCity($pid)
    {
        $city = cache('area:city_'.$pid);
        if(!$city){
            $city = Db::name('area')->field('id,name')->where('pid',$pid)->select();
            $city = json_encode($city,JSON_UNESCAPED_UNICODE);
            cache('area:city_'.$pid,$city);
        }
        return $city;
    }

    /**
     * 保存用户经纬度
     *
     * @param string $lng 经度
     * @param string $lat 纬度
     * @param string $user_id 会员id
     */
    public static function setAreaFromLngLat($lng, $lat,$user_id)
    {
        //GEOADD Sicily 13.361389 38.115556 "Palermo" 15.087269 37.502669 "Catania
        $redis = new RedisPackage();
        $redis::$handler->GEOADD('geoitsme:',$lng,$lat,$user_id);
        return true;
    }

    /**
     * 根据经纬度获取当前地区信息
     *
     * @param string $lng 经度
     * @param string $lat 纬度
     * @return Area 城市信息
     */
    public static function getAreaFromLngLat($lng, $lat, $level = 3)
    {
        // 判断redis中是否已经添加位置信息内容
        $geoInRedis = Cache::get('geoitsme:flag');
        if ($geoInRedis !== 'yes') {
            self::addAreaToRedis();
        }
        $namearr = [1 => 'geoitsme:province', 2 => 'geoitsme:city', 3 => 'geoitsme:district'];
        $rangearr = [1 => 15000, 2 => 1000, 3 => 200];
        $geoname = isset($namearr[$level]) ? $namearr[$level] : $namearr[3];
        $georange = isset($rangearr[$level]) ? $rangearr[$level] : $rangearr[3];
        // 读取范围内的ID
        $redis = Cache::store('redis')->handler();
        $georadiuslist = [];
        if (method_exists($redis, 'georadius')) {
            $georadiuslist = $redis->georadius($geoname, $lng, $lat, $georange, 'km', ['WITHDIST', 'COUNT' => 5, 'ASC']);
        }

        if ($georadiuslist) {
            list($id, $distance) = $georadiuslist[0];
        }
        $id = isset($id) && $id ? $id : 3;
        return self::get($id);
    }

    public static function addAreaToRedis() {
        $areas = self::all();
        foreach ($areas as $area) {
            $redis = Cache::store('redis')->handler();
            if (method_exists($redis, 'geoadd')) {
                $namearr = [1 => 'geoitsme:province', 2 => 'geoitsme:city', 3 => 'geoitsme:district'];
                $redis->geoadd($namearr[$area['level']], $area['lng'], $area['lat'], $area['id']);
            }
        }
        Cache::set('geoitsme:flag', 'yes');
    }

    /**
     * 根据经纬度获取省份
     *
     * @param string $lng 经度
     * @param string $lat 纬度
     * @return Area
     */
    public static function getProvinceFromLngLat($lng, $lat)
    {
        $provincedata = null;
        $citydata = self::getCityFromLngLat($lng, $lat);
        if ($citydata) {
            $provincedata = self::get($citydata['pid']);
        }
        return $provincedata;
    }

    /**
     * 根据经纬度获取城市
     *
     * @param string $lng 经度
     * @param string $lat 纬度
     * @return Area
     */
    public static function getCityFromLngLat($lng, $lat)
    {
        $citydata = null;
        $districtdata = self::getDistrictFromLngLat($lng, $lat);
        if ($districtdata) {
            $citydata = self::get($districtdata['pid']);
        }
        return $citydata;
    }

    /**
     * 根据经纬度获取地区
     *
     * @param string $lng 经度
     * @param string $lat 纬度
     * @return Area
     */
    public static function getDistrictFromLngLat($lng, $lat)
    {
        $districtdata = self::getAreaFromLngLat($lng, $lat, 3);
        return $districtdata;
    }

}
