<?php
namespace app\api\Impl;

use Elasticsearch\ClientBuilder;

/**
 * elasticsearch 公用类
 * Class ElasticSearchImpl
 * @package app\api\Impl
 */
class ElasticSearchImpl{
    //实例对象
    private static $instance = null;
    //es对象
    private static $esInstance = null;

    //保存用户的自定义配置参数
    private $settingAttribute = [];

    //构造器私有化:禁止从类外部实例化
    private function __construct(){}

    //克隆方法私有化:禁止从外部克隆对象
    private function __clone(){}

    //因为用静态属性返回类实例,而只能在静态方法使用静态属性
    //所以必须创建一个静态方法来生成当前类的唯一实例
    public static function getInstance()
    {
        //检测当前类属性$instance是否已经保存了当前类的实例
        if (self::$instance == null) {
            //如果没有,则创建当前类的实例
            self::$instance = new self();
        }
        //如果已经有了当前类实例,就直接返回,不要重复创建类实例
        return self::$instance;
    }

    private function connect(){
        if(self::$esInstance==null){
            self::$esInstance = ClientBuilder::create()->build();
        }
        return self::$esInstance;
    }

    /**
     * 创建表
     * @param $param
     * @return array
     */
    public function create($param){
        $res = $this->connect()->indices()->create($param);
        return $res;
    }

    /**
     * 添加数据
     * @param $param
     * @return array|callable
     */
    public function add($param){
        $res = $this->connect()->index($param);
        return $res;
    }

    /**
     * 批量添加数据
     * @param $param
     * @return array|callable
     */
    public function bulk($param){
        $res = $this->connect()->bulk($param);
        return $res;
    }

    /**
     * 取得数据
     * @param $param
     * @return array|callable
     */
    public function get($param){
        $res = $this->connect()->get($param);
        return $res;
    }

    /**
     * 取得资源数据
     * @param $param
     * @return array|callable
     */
    public function getSource($param){
        $res = $this->connect()->getSource($param);
        return $res;
    }

    /**
     * 删除数据
     * @param $param
     * @return array|callable
     */
    public function del($param){
        $res = $this->connect()->delete($param);
        return $res;
    }

    /**
     * 查询数据
     * @param $param
     * @return array|callable
     */
    public function search($param){
        $res = $this->connect()->search($param);
        return $res;
    }

    //设置配置项
    public function setAttribute($index, $value)
    {
        $this->settingAttribute[$index] = $value;
    }

    //读取配置项
    public function getAttribute($index)
    {
        return $this->settingAttribute[$index];
    }
}