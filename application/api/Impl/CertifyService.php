<?php
/**
 * Created by PhpStorm.
 * User: fengxin
 * Date: 2021/3/23
 * Time: 10:06 AM
 */
namespace app\api\Impl;
use app\common\exception\BusinessException;
use app\common\model\AgentReal;
use think\Config;
use fast\Http;
use think\Env;
use think\Exception;
use think\Log;

/**
 * 认证服务类
 * Class CertifyService
 * @package app\api\Impl
 */
class CertifyService{
    protected $params=[];
    private $gender=['女'=>0,'男'=>1];
    public function __construct()
    {
        /**
         * 百度身份身份证识别配置
         */
        $this->params['baiduIdentity']=[
            'grant_type' => Config::get('BaiduIdentity.grant_type'),
            'client_id'  =>Config::get('BaiduIdentity.client_id'),
            'client_secret' => Config::get('BaiduIdentity.client_secret'),
            'api_url'       => Config::get('BaiduIdentity.api_url'),
        ];
    }
    /**
     * 经纪人实名认证
     * @param $frontimg
     * @param $backimg
     * @param $agent_id
     * @return bool
     * @throws BusinessException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function real_certify($frontimg,$backimg,$agent_id){
        $Http=new Http();
        $resInfo = $Http->sendRequest(config('BaiduFace.token_url'), $this->params['baiduIdentity']);
        if(!$resInfo['ret']){
            throw new BusinessException('请求百度接口失败');
        }
        $token = $resInfo['msg'];
        $token = json_decode($token,true);
        $access_token = $token['access_token'];
        $url = $this->params['baiduIdentity']['api_url'] . $access_token;
        try{
            $img = file_get_contents(Env::get('app.test_host').$frontimg);
            $img = base64_encode($img);
            $bodys = array(
                'id_card_side' => 'front',
                'image' => $img
            );
            //处理返回结果
            $res = $this->identity_request_post($url, $bodys);
            $res = json_decode($res,true);
            $inset_data=[];
            if($res['idcard_number_type']==1){//获取认证后的身份信息
                foreach ($res['words_result'] as $k=>$words){
                    if($k=='姓名'){
                        $inset_data['name']=$words['words'];
                    }
                    if($k=='性别'){
                        $inset_data['gender']=$this->gender[$words['words']];
                    }
                    if($k=='公民身份号码'){
                        $inset_data['identity_card']=$words['words'];
                    }
                    if(count($inset_data)==3) break;
                }
            }
            //获得姓名、号码、性别后插入实名认证表
            if (count($inset_data)==3){
                $inset_data['real_item']=json_encode(['name'=>'real','img_litst'=>[
                    'front'=>$frontimg,
                    'back'=>$backimg
                ]]);
                //存在即更新
                if($realinfo=AgentReal::where('agent_id',$agent_id)->find()){
                    $realinfo->save($inset_data);
                }else{
                    $inset_data['agent_id']=$agent_id;
                    AgentReal::create($inset_data);
                }
                return true;
            }
            return false;
        }catch (Exception $exception){
            Log::error('['.date('Y-m-d H:i:s').']经纪人实名认证-agent_id:'.$agent_id.':'.$exception->getMessage());
            return false;
        }
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
}