<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Sms as Smslib;
use app\common\model\User;
use think\Hook;
use think\Queue;
use think\Log;
use app\common\model\Agent;
/**
 * 手机短信接口
 */
class Sms extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    /**
     * 发送验证码
     *
     * @param string $mobile 手机号
     * @param string $event 事件名称
     */
    public function send()
    {
        $mobile = $this->request->request("mobile");
        $event = $this->request->request("event");
        $event = $event ? $event : 'register';

        if (!$mobile || !\think\Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('手机号不正确'));
        }
        $last = Smslib::get($mobile, $event);
        if ($last && time() - $last['createtime'] < 300) {
            $this->error(__('发送频繁1'));
        }
        $ipSendTotal = \app\common\model\Sms::where(['ip' => $this->request->ip()])->whereTime('createtime', '-1 hours')->count();
        if ($ipSendTotal >= 5) {
            $this->error(__('发送频繁2'));
        }
        if ($event) {
            $agent=Agent::getByMobile($mobile);
            if ($event == 'register' && $agent) {
                //已被注册
                $this->error(__('已被注册'));
            } elseif (in_array($event, ['changemobile']) && $agent) {
                //被占用
                $this->error(__('已被占用'));
            } elseif (in_array($event, ['changepwd','agent_mobilelogin','resetpwd']) && !$agent) {
                //未注册
                $this->error(__('未注册'));
            }
        }
        if (!Hook::get('sms_send')) {
            $this->error(__('请在后台插件管理安装短信验证插件'));
        }
        //创建任务： 项目命名空间\模块\文件夹\控制器@方法
        $job = "app\job\controller\Sms@smspush";//$job = 任务名称
        $last_info = [$mobile,$event];//$last_info = 插入参数
        $queueName = 'smssend';//null 指定任务名称，没有则使用默认
        $smssend = Queue::push($job,$last_info, $queueName);
        if( $smssend !== false ){
            Log::write('发送短信'.$event.'任务创建成功！');
            $this->success('发送成功！');
        }else{
            Log::write('发送短信'.$event.'任务创建失败！');
            $this->error(__('发送失败！'));
        }
        /* $ret = Smslib::send($mobile, null, $event);
        if ($ret) {
            $this->success(__('发送成功'));
        } else {
            $this->error(__('发送失败，请检查短信配置是否正确'));
        } */
    }

    /**
     * 检测验证码
     *
     * @param string $mobile 手机号
     * @param string $event 事件名称
     * @param string $captcha 验证码
     */
    public function check()
    {
        $mobile = $this->request->request("mobile");
        $event = $this->request->request("event");
        $event = $event ? $event : 'register';
        $captcha = $this->request->request("captcha");

        if (!$mobile || !\think\Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('手机号不正确'));
        }
        if ($event) {
            $userinfo = User::getByMobile($mobile);
            if ($event == 'register' && $userinfo) {
                //已被注册
                $this->error(__('已被注册'));
            } elseif (in_array($event, ['changemobile']) && $userinfo) {
                //被占用
                $this->error(__('已被占用'));
            } elseif (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo) {
                //未注册
                $this->error(__('未注册'));
            }
        }
        $ret = Smslib::check($mobile, $captcha, $event);
        if ($ret) {
            $this->success(__('成功'));
        } else {
            $this->error(__('验证码不正确'));
        }
    }
}
