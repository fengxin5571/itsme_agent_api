<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\exception\UploadException;
use app\common\library\Upload;
use app\common\model\Area;
use app\common\model\UserPhrase;
use app\common\model\Version;
use fast\Random;
use think\Config;
use think\Db;
use think\Hook;
use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Faceid\V20180301\FaceidClient;
use TencentCloud\Faceid\V20180301\Models\DetectAuthRequest;
use TencentCloud\Faceid\V20180301\Models\GetDetectInfoRequest;
use think\Cache;
use fast\Http;

/**
 * 公共接口
 */
class Common extends Api
{
    protected $noNeedLogin = ['init', 'get_version', 'getProvince', 'getCity', 'update_user_permission'];
    protected $noNeedRight = '*';

    /**
     * 加载初始化
     */
    public function init()
    {
        //配置信息
        $upload = Config::get('upload');
        //如果非服务端中转模式需要修改为中转
        if ($upload['storage'] != 'local' && isset($upload['uploadmode']) && $upload['uploadmode'] != 'server') {
            //临时修改上传模式为服务端中转
            set_addon_config($upload['storage'], ["uploadmode" => "server"], false);

            $upload = \app\common\model\Config::upload();
            // 上传信息配置后
            Hook::listen("upload_config_init", $upload);

            $upload = Config::set('upload', array_merge(Config::get('upload'), $upload));
        }

        $upload['cdnurl'] = $upload['cdnurl'] ? $upload['cdnurl'] : cdnurl('', true);
        $upload['uploadurl'] = preg_match("/^((?:[a-z]+:)?\/\/)(.*)/i", $upload['uploadurl']) ? $upload['uploadurl'] : url($upload['storage'] == 'local' ? '/api/common/upload' : $upload['uploadurl'], '', false, true);

        // 获取最新版本数据
        $version_data = Version::get(function ($query) {
            $query->order('id', 'desc');
        });

        $content = [
            // 'versiondata' => Version::check($version),
            'versiondata' => $version_data,
            'uploaddata'  => $upload,
            'coverdata'   => Config::get("cover"),
            'age_between' => config('age'),
            'worth_between' => config('worth'),
            'phrase' => UserPhrase::all(['status' => 1]), // 常用语
        ];
        $this->success('', $content);
    }

    /**
     * 上传初始化
     */
    public function uploadinit()
    {
        //配置信息
        $upload = Config::get('upload');
        //如果非服务端中转模式需要修改为中转
        if ($upload['storage'] != 'local' && isset($upload['uploadmode']) && $upload['uploadmode'] != 'server') {
            //临时修改上传模式为服务端中转
            set_addon_config($upload['storage'], ["uploadmode" => "server"], false);

            $upload = \app\common\model\Config::upload();
            // 上传信息配置后
            Hook::listen("upload_config_init", $upload);

            $upload = Config::set('upload', array_merge(Config::get('upload'), $upload));
        }

        $upload['cdnurl'] = $upload['cdnurl'] ? $upload['cdnurl'] : cdnurl('', true);
        $upload['uploadurl'] = preg_match("/^((?:[a-z]+:)?\/\/)(.*)/i", $upload['uploadurl']) ? $upload['uploadurl'] : url($upload['storage'] == 'local' ? '/api/common/upload' : $upload['uploadurl'], '', false, true);
        $this->success('', $upload);
    }

    /**
     * 腾讯云人脸初始化
     *
     * @param string $ImageBase64 照片
     */
    public function TencentCloudFace(){
        try {
            $cred = new Credential(config('TencentCloud.SecretId'), config('TencentCloud.SecretKey'));
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("faceid.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new FaceidClient($cred, config('TencentCloud.Region'), $clientProfile);

            $req = new DetectAuthRequest();
            $ImageBase64 = $this->request->request('ImageBase64');
            $params = array(
                "RuleId" => config('TencentCloud.RuleId'),
                "ImageBase64" => $ImageBase64
            );
            $req->fromJsonString(json_encode($params));
            $resp = $client->DetectAuth($req);
            $arr_resp = json_decode($resp->toJsonString(),true);
            $this->success('请求成功！', $arr_resp);
        }
        catch(TencentCloudSDKException $e) {
            $this->error($e);
        }
    }

    /**
     * 腾讯云⼈脸核身
     */
    public function TencentCloudFaceGetDetectInfo(){
        try {
            $cred = new Credential(config('TencentCloud.SecretId'), config('TencentCloud.SecretKey'));
            $httpProfile = new HttpProfile();
            $httpProfile->setEndpoint("faceid.tencentcloudapi.com");

            $clientProfile = new ClientProfile();
            $clientProfile->setHttpProfile($httpProfile);
            $client = new FaceidClient($cred, config('TencentCloud.Region'), $clientProfile);

            $req = new GetDetectInfoRequest();
            $BizToken = $this->request->request('BizToken');
            $params = array(
                "RuleId" => config('TencentCloud.RuleId'),
                "BizToken" => $BizToken,
                //"Region" => config('TencentCloud.Region'),
            );
            $req->fromJsonString(json_encode($params));
            $resp = $client->GetDetectInfo($req);
            $arr_resp = json_decode($resp->toJsonString(),true);
            $this->success('请求成功！', $arr_resp);
        }
        catch(TencentCloudSDKException $e) {
            $this->error($e);
        }
    }

    /**
     * 百度人脸检测
     * @ApiMethod (POST)
     * @param File $file 文件流
     */
    public function BaiduFace(){
        $token = $this->BaiduFace_token();
        $token = $token['msg'];
        $token = json_decode($token,true);
        $access_token = $token['access_token'];
        $params['image'] = $this->request->request('image');
        $params['image_type'] = 'BASE64';
        $params['face_field'] = 'gender,age';
        $params = json_encode($params);
        $Http = new Http();
        $res = $Http->sendRequest(config('BaiduFace.face_url').'?access_token='.$access_token, $params);
        $res = json_decode($res['msg'],true);
        $this->success('请求成功！',$res);
    }

    /**
     * 百度人脸对比
     * @ApiMethod (POST)
     * @param File $file 文件流
     */
    public function BaiduFace_bd(){
        $token = $this->BaiduFace_token();
        $token = $token['msg'];
        $token = json_decode($token,true);
        $access_token = $token['access_token'];
        $image1 = $this->request->request('image1');
        $image2 = $this->request->request('image2');
        $params = [];
        $params[0]['image'] = $image1;
        $params[0]['image_type'] = 'BASE64';
        $params[1]['image'] = $image2;
        $params[1]['image_type'] = 'BASE64';
        $params = json_encode($params);
        //print_r($params);exit;
        $Http = new Http();
        $res = $Http->sendRequest(config('BaiduFace.face_bd_url').'?access_token='.$access_token, $params);
        $res = json_decode($res['msg'],true);
        $this->success('请求成功！',$res);
    }

    /**
     * 百度人脸检测token
     * @ApiMethod (POST)
     * @param File $file 文件流
     */
    private function BaiduFace_token(){
        $Http = new Http();
        $params['grant_type'] = 'client_credentials';
        $params['client_id'] = config('BaiduFace.api_key');
        $params['client_secret'] = config('BaiduFace.secret_key');
        $res = $Http->sendRequest(config('BaiduFace.token_url'), $params);
        return $res;
    }

    /**
     * 上传文件
     * @ApiMethod (POST)
     * @param File $file 文件流
     */
    public function upload()
    {
        Config::set('default_return_type', 'json');
        //必须设定cdnurl为空,否则cdnurl函数计算错误
        Config::set('upload.cdnurl', '');
        $chunkid = $this->request->post("chunkid");
        if ($chunkid) {
            if (!Config::get('upload.chunking')) {
                $this->error(__('Chunk file disabled'));
            }
            $action = $this->request->post("action");
            $chunkindex = $this->request->post("chunkindex/d");
            $chunkcount = $this->request->post("chunkcount/d");
            $filename = $this->request->post("filename");
            $method = $this->request->method(true);
            if ($action == 'merge') {
                $attachment = null;
                //合并分片文件
                try {
                    $upload = new Upload();
                    $attachment = $upload->merge($chunkid, $chunkcount, $filename);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success(__('Uploaded successful'), ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
            } elseif ($method == 'clean') {
                //删除冗余的分片文件
                try {
                    $upload = new Upload();
                    $upload->clean($chunkid);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            } else {
                //上传分片文件
                //默认普通上传文件
                $file = $this->request->file('file');
                try {
                    $upload = new Upload($file);
                    $upload->chunk($chunkid, $chunkindex, $chunkcount);
                } catch (UploadException $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            }
        } else {
            $attachment = null;
            //默认普通上传文件
            $file = $this->request->file('file');
            try {
                $upload = new Upload($file);
                $attachment = $upload->upload();
            } catch (UploadException $e) {
                $this->error($e->getMessage());
            }

            $this->success(__('Uploaded successful'), ['url' => $attachment->url, 'fullurl' => cdnurl($attachment->url, true)]);
        }

    }

    /**
     * 获取省
     */
    public function getProvince()
    {
        $Province = Area::getProvince();
        $this->success('请求成功！', $Province);
    }

    /**
     * 获取市
     * @param string $pid 省id
     */
    public function getCity()
    {
        $pid = $this->request->request('pid');
        $City = Area::getCity($pid);
        $this->success('请求成功！', $City);
    }

    /**
     * 省市区写入redis
     * @param string $pid 省id
     */
    public function setpcd()
    {
        $level = $this->request->request('level');
        $namearr = [1 => 'geoitsme:province', 2 => 'geoitsme:city', 3 => 'geoitsme:district'];
        $geoname = isset($namearr[$level]) ? $namearr[$level] : $namearr[3];
        $area = Area::field('id,lng,lat')->where('level',$level)->select();
        $redis = Cache::store('redis')->handler();
        foreach($area as $k=>$v){
            $redis->GEOADD($geoname,$v['lng'],$v['lat'],$v['id']);
        }
        print_r($area);exit;
        // 读取范围内的ID
        /* $redis = Cache::store('redis')->handler();
        $georadiuslist = [];
        if (method_exists($redis, 'georadius')) {
            $georadiuslist = $redis->GEOADD(Sicily 13.361389 38.115556 "Palermo");
        } */
        $this->success('请求成功！');
    }

    /**
     * 所有用户添加权限
     */
    public function userAddPermission(){
        $userArr = Db::name("user")->select();
        foreach ($userArr as $k=>$v){
             if($v['gender']==1){
                 $userType = 2;
             }else{
                 $userType = 3;
             }
            $configUserPermission = Db::name('config_user_permission')->where('type',$userType)->find();
            $time = time();
            //写入个人用户数据
            $userPermissionData = [
                'user_id' => $v['id'],
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
            $userInfoId = Db::name('user_permission')->field("id")->where(['user_id'=>$v['id']])->find();
            if($userInfoId){
                continue;
            }
            $insertUserPersion = Db::name('user_permission')->insert($userPermissionData);
            if($insertUserPersion){
                //用户权限数据写入redis数据
                $userPermissionDataJson = json_encode($userPermissionData);
                $redisUserPermission = \app\common\model\User::setUserPermission("set",$v['id'],$userPermissionDataJson);
            }
        }
    }

    /**
     * APP启动页
     */
    public function loading_screen()
    {
        $data = Db::name('loading_screen')->field('image')->where('switch', 1)->order('weigh', 'desc')->select();
        $this->success('ok', $data);
    }

    /**
     * 更新会员权限
     */
    public function update_user_permission()
    {
        $pwd = $this->request->param('pwd');
        $type = $this->request->param('type');
        $user_id = $this->request->param('user_id');
        if ($pwd != md5('updateuserpermission')) {
            $this->error();
        }
        if ($type == 5) {
            \app\api\Impl\UserCheckPermissionImpl::updateUserPermission($user_id, $type);
        }
        $this->success();
    }

    /**
     * 检查用户期望值标签和用户基本信息是否填写完全
     * 期望值标签是页面：你希望遇到怎样的人（遇见怎样的人），你是怎样的人（描述自己）
     * 基本信息为：头像，生日，身高，体重，常住地，个人介绍
     */
    public function check_expect_and_userinfo()
    {
        $user_id = $this->auth->id;
        $gender = Db::name('user')->field('gender')->where('id', $user_id)->value('gender');

        // 期望值
        $type_ids = Db::name('expect_type_step')->field('type_id')->where(['sex' => $gender, 'step' => ['<>', 3]])->column('type_id');
        $expect_arr = [];
        foreach ($type_ids as $index => $type_id) {
            $expect_ids = Db::name('expect')->field('id')->where('type_id', $type_id)->column('id');
            $expect_arr[$type_id] = $expect_ids;
        }

        // 检查用户期望值
        $data['user_expect'] = true;
        foreach ($expect_arr as $type_id => $expect_arr) {
            $user_expect_count = Db::name('user_expect')->where('user_id', $user_id)->whereIn('expect_id', $expect_arr)->count();
            if ($user_expect_count == 0) {
                $data['user_expect'] = false;
                break;
            }
        }

        // 检查用户基本资料
        $data['user_info'] = true;
        $user_data = Db::name('user')->field(['avatar', 'birthday', 'height', 'weight', 'province', 'city', 'bio'])->where('id', $user_id)->find();
        if (empty($user_data['avatar'])) $data['user_info'] = false;
        if (empty($user_data['birthday'])) $data['user_info'] = false;
        if (empty($user_data['height'])) $data['user_info'] = false;
        if (empty($user_data['weight'])) $data['user_info'] = false;
        if (empty($user_data['province'])) $data['user_info'] = false;
        if (empty($user_data['city'])) $data['user_info'] = false;
        if (empty($user_data['bio'])) $data['user_info'] = false;

        $this->success('ok', $data);
    }
}
