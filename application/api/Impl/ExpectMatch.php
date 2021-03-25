<?php


namespace app\api\Impl;

use think\Db;

/**
 * 期望值
 */
class ExpectMatch
{
    public $user_id = 0;

    public function __construct($user_id)
    {
        $this->user_id = $user_id;
    }

    // 匹配人数
    public function user_number()
    {
        $users = $this->users();

        return count($users);
    }

    // 匹配标签数
    public function label_number($include_yourself = true)
    {
        $labels = $this->labels($include_yourself);

        return count($labels);
    }

    // 匹配用户集合
    public function users()
    {
        $labels = $this->labels();

        // 查询含有标签用户，去重
        $user_expect_ids = Db::name('user_expect')->field('user_id')->whereIn('expect_id', $labels)->where('user_id', '<>', $this->user_id)->column('user_id');
        if (empty($user_expect_ids)) {
            return [];
        }
        $user_ids = array_unique($user_expect_ids);

        return $user_ids;
    }

    /**
     * 匹配标签集合
     * @param bool $include_yourself 是否包含自己的标签
     * 比如自己的标签是10，20
     * 匹配到的标签有100，200，300
     * 如果$include_yourself = false只会返回[100,200,300]
     * 如果$include_yourself = true会返回[10,20,100,200,300]
     * @return array
     */
    public function labels($include_yourself = true)
    {
        $user_expect_ids = Db::name('user_expect')->field('expect_id')->where('user_id', $this->user_id)->column('expect_id');
        if (empty($user_expect_ids)) {
            return [];
        }

        // 查询关联标签，去重
        $name_id2s = Db::name('expect_join')->field('name_id2')->whereIn('name_id1', $user_expect_ids)->column('name_id2');
        $join_ids = array_unique($name_id2s);

        // 插入自己的标签
        if ($include_yourself) {
            foreach ($user_expect_ids as $expect_id) {
                array_unshift($join_ids, $expect_id);
            }
        }

        return $join_ids;
    }

    /**
     * 标签重合项
     * @param string $sort desc重合项大到小，asc重合项小到大
     * @return array 二维数组
     * [
     *      [
     *          'user_id' => int 40 匹配到的用户id
     *          'count' => int 6 标签重合项
     *          'labels' => [91,85,66] 重合的标签集合
     *      ]
     * ]
     */
    public function coincide($sort = 'desc')
    {
        $users = $this->users();
        $labels = $this->labels();
        $arr = [];
        foreach ($users as $index => $user_id) {
            $this->user_id = $user_id;
            $arr[$user_id] = array_intersect($labels, $this->labels());
        }
        uasort($arr, function ($a, $b) use ($sort) {
            if (count($a) == count($b)) return 0;
            if ($sort == 'desc') {
                return count($a) > count($b) ? -1 : 1;
            } else {
                return count($a) < count($b) ? -1 : 1;
            }
        });
        foreach ($arr as $index => $item) {
            $r[] = [
                'user_id' => $index,
                'count' => count($item),
                'labels' => $item,
            ];
        }
        if (empty($r)) {
            return [];
        }
        return $r;
    }

    // todo 还有一个复杂的重合项方法待实现
    // todo 交友目的类型的标签，重合多的往前放
    // todo 专门有一个筛选交友目的重合项的
}