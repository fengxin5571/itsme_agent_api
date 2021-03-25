<?php

namespace app\api\controller;

use app\api\Impl\UserCheckPermissionImpl;
use app\common\controller\Api;
use app\common\model\ConfigGift;
use app\common\model\ConfigGold;
use app\common\model\ConfigVip;
use app\common\model\User as usermodel;
use app\common\model\UserGiftOrder;
use app\common\model\UserGoldOrder;
use app\common\model\UserMoneyLog;
use app\common\model\UserVip;
use app\common\model\UserWorthLog;
use think\Db;
use app\common\model\User;
use think\Log;
use app\common\model\Area;
use pay\wx\WxPayApi;
use pay\wx\WxPayUnifiedOrder;
use app\common\model\UserVipOrder;
use think\Queue;


/**
 * 支付
 */
class Pay extends Api
{
    protected $noNeedLogin = ['wx_notify', 'ali_notify'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 微信支付
     */
    public function wx_pay()
    {
        $param = $this->request->param();
        // 验证参数
        $this->_param_check($param);
        // 获取价格和名字
        $data = $this->_get_price_name($param);
        $total = $data['amount'];
        // 商品名称
        $subject = $data['name'];
        // 订单号（时间戳+4位随机数）
        $out_trade_no = date('YmdHis', time()) . mt_rand(1000, 9999);
        // 发起支付
        $unifiedOrder = new WxPayUnifiedOrder();
        $unifiedOrder->SetAttach($param['type']);
        $unifiedOrder->SetBody($subject);//商品或支付单简要描述
        $unifiedOrder->SetOut_trade_no($out_trade_no);
        $unifiedOrder->SetTotal_fee($total);
        $unifiedOrder->SetTrade_type("APP");
        $result = WxPayApi::unifiedOrder($unifiedOrder);
        if (is_array($result)) {
            // 写入订单表
            $order_id = $this->_add_order($param['type'], [
                'out_trade_no' => $out_trade_no,
                'user_id'      => $this->auth->id,
                'id'           => $data['config_id'],
                'num'          => $param['num'] ?? 1,
                'pay_type'     => 1
            ]);
            if ($order_id) {
                // $this->success('请求成功！', ['order_id' => $order_id]);
                $result['out_trade_no'] = $out_trade_no;
                $result['type'] = $param['type'];
                $result['pay_type'] = 1;
                $this->success('请求成功！', $result);
            }
        }
        $this->error('请求失败！');
    }

    /**
     * 微信支付回调
     */
    public function wx_notify()
    {
        $xmlData = file_get_contents("php://input");

        // xml转数组
        $xml = simplexml_load_string($xmlData, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xmlJson = json_encode($xml);
        $xmlArr = json_decode($xmlJson, true);
        $xmlArr['pay_type'] = 1;

        $this->_notify_operation($xmlArr);
        echo "SUCCESS";
    }

    /**
     * 支付宝
     */
    public function ali_pay()
    {
        // header('Content-type: text/plain');
        import('pay.ali.aop.AopClient');
        import('pay.ali.aop.request.AlipayTradeAppPayRequest');

        $param = $this->request->param();
        // 验证参数
        $this->_param_check($param);
        // 获取价格和名字
        $data = $this->_get_price_name($param);
        // 获取支付金额
        $total = $data['amount'] / 100;

        $aop = new \AopClient();
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = "2021002122632417";
        $aop->rsaPrivateKey = 'MIIEowIBAAKCAQEAkT4QB1vD/K9rXInftcs0kbSh8Wl1ghwg6PCVdK8GkpnifueURVo1vIkekLxwO1NcObmh0CmjGzLbryBg7d1dN7ib/BWTGU7Jny5/NQAqJ+5PH5U+JTfNNjxNlVv2cuAwCjZYcRN36Jb+JyX4/4djxgUvaYky1hDkmBOyyIdevG63ukdAf+rMIfRERgsv/LiV+F/bFZZvnKKb8E1eeIn+DspGcI/DwD7h22GDt0YQEGvfBJ1Ve9GPGIzldz1X/ki6y792d6mKRY7rU/pw5WMGhFQghQg8J3tRaW/hBCzMXCtqyFZwFFIzE5rN+GHtJKkx4UeYlCM26rSMA4URGjdWPQIDAQABAoIBAC/Aku6IajBq0EaVgfq4O6loFRJVFPBZJSzQ5KJ/ZQ7QD1qf8uy2UxKQln8JpXCr4JeOA7h36AtqNjYm1BeAf0Rxqr8/rKRWdg2i8OnRCwsj29nLbKo55XteajegC7ALK2BAK+XJe9P5yMfAQVRqBBC35bWyCQe+eBnT6CTr9ObIlQs6uitWyIOaDdom4JgSn/CzGdJeWZLfXdXPGUxLUV1AQ4OJiqxbpwuGP26p+RUHPQQWtjLBLMLVpsiUUNhmUxxVlqrqwyDP23SjOXPzBjJqVKJatwg1Q9t/w936r8sSQyjqAtIk0s0mcESCdsVd34SJ5BWbUhCTPsdtNzyR4xUCgYEAzZMp2lQbyXaoHmqCqBSqK6jA4Xi0tJnLzjwzFhUGbeAuthK8ZqkJ2fIGpdcbYTSHqMcX2CTMZYSbXatqNUtYyMOMpZDFoy+uONIMIrDXTi2UnYJ/TX0focrEHYMIlqVmXRVpkHVhVjEFSVmzQFnmknxL7+MNADhethkhd85nxnMCgYEAtN5k2MM7i463heOmuTmg8YxzhSA5ZrBfrRmXSx3fPbicJh327s/2+8doTfxhGd8DKU9VlP1NYYN4tIaCgYlaDHKSnM9A+lmBK7xFOdC9W2+h7W6Ke+536hzgHnHYETHSFY/i3GlBPQzdnnAZn9sKnGtYrghDx/2cEmfaPxwnlI8CgYEAmXUTtxE2NOvIj/v+UK4sYa71XNqYOoDcLLWvhPpo9Dh3Zh8SWKgy3GjZIY8ztxpZclo8qHK/ycB1ojTFcccvHZ8sLKOnhSugqHXT7UmJT6ii2fmCQjv3EvWj9EvOa3ZItY+4X8ffw1GQmrLFJnJ1tj/nB8m7+MAbo1+bJi4ENZUCgYB2z9y5U86Kx46hIkGETWn/Ir9EBT4Pye6fvD7Zdl8OoXXZyDdM+0oIbR6ElDSJFSlzeo0CmT66vu2M0Qtr4nlH0f+jiLXrft5Oh5eF+ixZo0Rgvwuzi5w0KxHCjhBcgzi5N1LUbUQQwaHXkVYT34Th6dZQRFeaSWu734LLkEgj2wKBgHFyLfHUgf1QMU9+3Yg+GTfMHRPf0DZJJBdMHlNDmmUZbxJ/iuo7qybjW6gren0ssEaElDZVdCrI4XvgGooHhxOeSAHTgTBHr/00j5f/ochfq5fUXDP5qbzzossHu37HdDjxUBGIr35MybFYDCfn09/MZ/mL/o9DY2AMdWxQGbsL';
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkT4QB1vD/K9rXInftcs0kbSh8Wl1ghwg6PCVdK8GkpnifueURVo1vIkekLxwO1NcObmh0CmjGzLbryBg7d1dN7ib/BWTGU7Jny5/NQAqJ+5PH5U+JTfNNjxNlVv2cuAwCjZYcRN36Jb+JyX4/4djxgUvaYky1hDkmBOyyIdevG63ukdAf+rMIfRERgsv/LiV+F/bFZZvnKKb8E1eeIn+DspGcI/DwD7h22GDt0YQEGvfBJ1Ve9GPGIzldz1X/ki6y792d6mKRY7rU/pw5WMGhFQghQg8J3tRaW/hBCzMXCtqyFZwFFIzE5rN+GHtJKkx4UeYlCM26rSMA4URGjdWPQIDAQAB';
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new \AlipayTradeAppPayRequest();
        // 异步通知地址
        // $notify_url = urlencode('http://client.yzxnet.com/api/pay/ali_notify');
        $notify_url = 'http://client.yzxnet.com/api/pay/ali_notify';
        // 订单标题
        $subject = $data['name'];
        // 订单详情
        $body = $data['name'];
        // 订单号（时间戳+4位随机数+类型）
        $out_trade_no_a = date('YmdHis', time()) . mt_rand(1000, 9999);
        $out_trade_no = $out_trade_no_a . "/" . $param['type'];
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $bizcontent = "{\"body\":\"" . $body . "\","
            . "\"subject\": \"" . $subject . "\","
            . "\"out_trade_no\": \"" . $out_trade_no . "\","
            . "\"timeout_express\": \"30m\","
            . "\"total_amount\": \"" . $total . "\","
            . "\"product_code\":\"QUICK_MSECURITY_PAY\""
            . "}";
        $request->setNotifyUrl($notify_url);
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);

        // 写入订单表
        $order_id = $this->_add_order($param['type'], [
            'out_trade_no' => $out_trade_no_a,
            'user_id'      => $this->auth->id,
            'id'           => $data['config_id'],
            'num'          => $param['num'] ?? 1,
            'pay_type'     => 2
        ]);

        // 注意：这里不需要使用htmlspecialchars进行转义，直接返回即可
        trace(urldecode($response), '支付宝支付');
        $this->success('ok', [
            'orderInfo'    => $response,
            'type'         => $param['type'],
            'pay_type'     => 2,
            'out_trade_no' => $out_trade_no_a
        ]);
    }

    /**
     * 支付宝回调
     */
    public function ali_notify()
    {
        trace($_POST, '支付宝回调post');
        import('pay.ali.aop.AopClient');
        import('pay.ali.aop.request.AlipayTradeAppPayRequest');
        $aop = new \AopClient();
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkT4QB1vD/K9rXInftcs0kbSh8Wl1ghwg6PCVdK8GkpnifueURVo1vIkekLxwO1NcObmh0CmjGzLbryBg7d1dN7ib/BWTGU7Jny5/NQAqJ+5PH5U+JTfNNjxNlVv2cuAwCjZYcRN36Jb+JyX4/4djxgUvaYky1hDkmBOyyIdevG63ukdAf+rMIfRERgsv/LiV+F/bFZZvnKKb8E1eeIn+DspGcI/DwD7h22GDt0YQEGvfBJ1Ve9GPGIzldz1X/ki6y792d6mKRY7rU/pw5WMGhFQghQg8J3tRaW/hBCzMXCtqyFZwFFIzE5rN+GHtJKkx4UeYlCM26rSMA4URGjdWPQIDAQAB';//支付宝公钥
        $flag = $aop->rsaCheckV1($_POST, NULL, "RSA2");
        $arr = explode('/', $_POST['out_trade_no']);
        $param = [
            'attach'         => intval($arr[1]),
            'out_trade_no'   => $arr[0],
            'total_fee'      => floatval($_POST['total_amount']) * 100,
            'time_end'       => $_POST['gmt_payment'],
            'result_code'    => 'SUCCESS',
            'transaction_id' => $_POST['trade_no'],
            'pay_type'       => 2,
            'notify_arr'     => $_POST
        ];
        $this->_notify_operation($param);
        echo "success";
    }

    /**
     * 获取订单状态
     */
    public function get_order_status()
    {
        $param = $this->request->param();
        $validate = new \think\Validate([
            'type'         => 'require',
            'pay_type'     => 'require',
            'out_trade_no' => 'require',
        ]);
        if (!$validate->check($param)) {
            $this->error($validate->getError());
        }
        if ($param['type'] == 1) {
            $name = 'user_vip_order';
        }
        if ($param['type'] == 2) {
            $name = 'user_gold_order';
        }
        if ($param['type'] == 3) {
            $name = 'user_gift_order';
        }

        $data = Db::name($name)->where(['pay_type' => $param['pay_type'], 'out_trade_no' => $param['out_trade_no']])->find();
        if ($data['status'] == 1) {
            $this->success('支付成功');
        }
        if ($data['status'] == 2) {
            $this->success('支付失败');
        }
        if ($data['status'] == 3) {
            $this->success('待支付');
        }
    }

    /**
     * 回调操作
     */
    private function _notify_operation($xmlArr)
    {
        trace($xmlArr, '$xmlArr');
        /**
         * 统一实例化不同类型的订单，产品配置
         */
        if ($xmlArr['attach'] == 1) {
            $order_model = new UserVipOrder();
            $config_model = new ConfigVip();
            $config_field = 'vip_id';
        }
        if ($xmlArr['attach'] == 2) {
            $order_model = new UserGoldOrder();
            $config_model = new ConfigGold();
            $config_field = 'gold_id';
        }
        if ($xmlArr['attach'] == 3) {
            $order_model = new UserGiftOrder();
            $config_model = new ConfigGift();
            $config_field = 'gift_id';
        }
        $user_model = new User();
        $worth_log_model = new UserWorthLog();

        /**
         * 查询
         * 订单，用户信息，购买的产品配置，用户id
         */
        $order_data = $order_model->where(['out_trade_no' => $xmlArr['out_trade_no']])->find();
        $user_id = $order_data['user_id'];
        $config_data = $config_model->where(['id' => $order_data[$config_field]])->find();
        $user_data = $user_model->where(['id' => $user_id])->find();

        /**
         * 根据不同类型更新所需
         */
        if ($xmlArr['result_code'] == 'SUCCESS') {

            if ($xmlArr['attach'] == 1) {
                // 更新会员vip状态
                $user_model->editIsVip($user_id, 1);
                // 更新会员vip明细
                $userVipModel = new UserVip();
                $userVipModel->edit($user_id, $config_data);
                // 更新会员权限（user_permission和redis的userpermission）
                UserCheckPermissionImpl::editUserPermission($user_id, $config_data['id']);
            }
            if ($xmlArr['attach'] == 2) {
                // 更新会员余额
                $user_model->save(['money' => $user_data['money'] + $config_data['num']], ['id' => $user_data['id']]);
                // 添加会员余额变动日志
                $userMoneyLog = new UserMoneyLog();
                $userMoneyLog->save([
                    'user_id' => $user_id,
                    'money'   => $config_data['num'],
                    'before'  => $user_data['money'],
                    'after'   => $user_data['money'] + $config_data['num'],
                    'type'    => 4,
                    'type_id' => $config_data['id']
                ]);
            }
            // if ($xmlArr['attach'] == 3) {
            // }
        }

        /**
         * 记录身价日志
         */
        if ($xmlArr['result_code'] == 'SUCCESS') {
            if ($xmlArr['attach'] == 1) {
                $initData['type'] = 2;
            }
            if ($xmlArr['attach'] == 2) {
                $initData['type'] = 3;
            }
            if ($xmlArr['attach'] == 3) {
                $initData['type'] = 4;
            }
            $initData = array_merge([
                'user_id' => $user_id,
                'worth'   => $config_data['up_worth'],
                'before'  => $user_data['worth'],
                'after'   => $config_data['up_worth'] + $user_data['worth'],
                'type_id' => $config_data['id']
            ], $initData);
            $worth_log_model->save($initData);
        }

        /**
         * 会员redis更新
         */
        if ($xmlArr['result_code'] == 'SUCCESS') {
            $user = usermodel::get($user_id);
            // 更新身价
            $user->worth = $config_data['up_worth'] + $user_data['worth'];
            if ($xmlArr['attach'] == 1) {
                // 更新vip状态
                $user->is_vip = 1;
            }
            if ($xmlArr['attach'] == 2) {
                // 更新余额
                $user->money = $user_data['money'] + $config_data['num'];
            }
            // if ($xmlArr['attach'] == 3) {
            // }
            usermodel::setUserInfo($user);
        }

        /**
         * 回调支付成功必须要统一更新的
         */
        if ($xmlArr['result_code'] == 'SUCCESS') {
            // 更新身价
            $user_model->editWorth($user_id, $config_data['up_worth']);
        }
        // 更新订单数据
        $order_model->edit($xmlArr);
    }

    /**
     * 检查参数
     */
    private function _param_check($param)
    {
        $validate = new \think\Validate([
            'type' => 'require',
            'id'   => 'require'
        ]);
        if (!$validate->check($param)) {
            $this->error($validate->getError());
        }
        if ($param['type'] == 3) {
            if (empty($param['num'])) {
                $this->error('num不能为空');
            }
        }
    }

    /**
     * 获取价格和名字
     */
    private function _get_price_name($param)
    {
        // vip
        if ($param['type'] == 1) {
            $config_vip = Db::name('config_vip')->where(['id' => $param['id'], 'state' => 1])->find();
            $amount = $config_vip['discount_price'];
            $name = $config_vip['name'];
            $id = $config_vip['id'];
        }
        // 金币
        if ($param['type'] == 2) {
            $configGoldModel = new ConfigGold();
            $configGoldData = $configGoldModel->where(['id' => $param['id']])->find();
            $amount = $configGoldData['discount_price'];
            $name = $configGoldData['num'] . "个金币";
            $id = $configGoldData['id'];
        }
        // 礼物
        if ($param['type'] == 3) {
            $gift_config_data = Db::name('config_gift')->where('id', $param['id'])->find();
            $amount = $param['num'] * $gift_config_data['discount_price'];
            $name = $param['num'] . "个" . $gift_config_data['name'];
            $id = $gift_config_data['id'];
        }

        $data = [
            'amount'    => $amount,
            'name'      => "购买" . $name,
            'config_id' => $id
        ];
        return $data;
    }

    /**
     * 创建订单
     */
    private function _add_order($type, $data)
    {
        // vip
        if ($type == 1) {
            $model = new UserVipOrder();
            $order_id = $model->add([
                'out_trade_no' => $data['out_trade_no'],
                'user_id'      => $data['user_id'],
                'vip_id'       => $data['id'],
                'pay_type'     => $data['pay_type']
            ]);
        }
        // 礼物
        if ($type == 2) {
            $model = new UserGoldOrder();
            $order_id = $model->add([
                'out_trade_no' => $data['out_trade_no'],
                'user_id'      => $data['user_id'],
                'gold_id'      => $data['id'],
                'pay_type'     => $data['pay_type']
            ]);
        }
        // 金币
        if ($type == 3) {
            $model = new UserGiftOrder();
            $order_id = $model->add([
                'out_trade_no' => $data['out_trade_no'],
                'user_id'      => $data['user_id'],
                'gift_id'      => $data['id'],
                'num'          => $data['num'],
                'pay_type'     => $data['pay_type']
            ]);
        }
        return $order_id;
    }
}
