<?php


namespace app\common\model;


use think\Model;

class UserPhrase extends Model
{
    protected $autoWriteTimestamp = 'int';

    protected $createTime = false;
    protected $updateTime = false;

    protected $append = [
        'type_text'
    ];

    public function getTypeTextAttr($value, $data)
    {
        if ($data['status'] == 1) {
            return '主页点喜欢';
        }
        if ($data['status'] == 2) {
            return '推荐也点喜欢';
        }
        if ($data['status'] == 3) {
            return '任务点聊天';
        }
        if ($data['status'] == 4) {
            return '任务点喜欢';
        }
        if ($data['status'] == 5) {
            return '发动态文案';
        }
        if ($data['status'] == 5) {
            return '专属推荐点喜欢';
        }
    }
}