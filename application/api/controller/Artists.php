<?php
/**
 * Created by PhpStorm.
 * User: fengxin
 * Date: 2021/3/24
 * Time: 9:00 AM
 */
namespace app\api\controller;
use app\api\Impl\ArtistsService;
use app\common\controller\Api;
use think\Request;
use think\Validate;
/**
 * 艺人控制器
 * Class Artists
 * @package app\api\controller
 */
class Artists extends Api{
    protected $noNeedLogin = [''];
    protected $noNeedRight = ['*'];
    protected $services=[];
    public function _initialize(){
        parent::_initialize();
        $this->services['artistsServ']=new ArtistsService();
    }
    /**
     * 我的艺人
     * @param Request $request
     */
    public function artist_list(Request $request){
        $search=$request->request('search','');//搜索条件
        $listRows = $this->request->request('limit/d', 10);//每页条数
        $lat=$request->request('lat','39.904989');//纬度
        $lng=$request->request('lng','116.405285');//经度
        $gender=$request->request('sex',0);//性别
        $loaction=['lat'=>$lat,'lng'=>$lng];
        $data=$this->services['artistsServ']->artist_list($search,$gender,$this->auth->id,$loaction,$listRows);
        return $this->success('ok',$data);
    }
    /**
     * 艺人详细信息
     * @param Request $request
     */
    public function artist_info(Request $request){
        $user_id=$request->request('user_id/d');
        $lat=$request->request('lat','39.904989');//纬度
        $lng=$request->request('lng','116.405285');//经度
        if(!$user_id){
            return $this->error('用户id不能为空');
        }
        $loaction=['lat'=>$lat,'lng'=>$lng];
        $data=$this->services['artistsServ']->artist_info($loaction,$user_id);
        return $this->success('ok',$data);
    }
}