<?php
/**
 * Created by PhpStorm.
 * User: fengxin
 * Date: 2021/3/23
 * Time: 10:28 AM
 */
namespace app\common\exception;
use think\Exception;

class  BusinessException extends Exception{
    public function __construct($message = "", $code = 0, $data = [])
    {
        $this->message = $message;
        $this->code = $code;
        $this->data = $data;
    }
}