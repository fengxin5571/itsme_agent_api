<?php
/* *
    * 获取星座
    * 星座是按阳历来计算的
    * $month 阳历月份
    * $day  阳历日期
    * */
function get_xingzuo($month, $day)
{
    $xingzuo = '';
    // 检查参数有效性
    if ($month < 1 || $month > 12 || $day < 1 || $day > 31)
    {
        return $xingzuo;
    }

    if(($month == 1 && $day >= 20) || ($month == 2 && $day <= 18)) {
        $xingzuo = "水瓶座";
    }else if(($month == 2 && $day >= 19) || ($month == 3 && $day <= 20)) {
        $xingzuo = "双鱼座";
    }else if (($month == 3 && $day >= 21) || ($month == 4 && $day <= 19)) {
        $xingzuo = "白羊座";
    }else if (($month == 4 && $day >= 20) || ($month == 5 && $day <= 20)) {
        $xingzuo = "金牛座";
    }else if (($month == 5 && $day >= 21) || ($month == 6 && $day <= 21)) {
        $xingzuo = "双子座";
    }else if (($month == 6 && $day >= 22) || ($month == 7 && $day <= 22)) {
        $xingzuo = "巨蟹座";
    }else if (($month == 7 && $day >= 23) || ($month == 8 && $day <= 22)) {
        $xingzuo = "狮子座";
    }else if (($month == 8 && $day >= 23) || ($month == 9 && $day <= 22)) {
        $xingzuo = "处女座";
    }else if (($month == 9 && $day >= 23) || ($month == 10 && $day <= 23)) {
        $xingzuo = "天秤座";
    }else if (($month == 10 && $day >= 24) || ($month == 11 && $day <= 22)) {
        $xingzuo = "天蝎座";
    }else if (($month == 11 && $day >= 23) || ($month == 12 && $day <= 21)) {
        $xingzuo = "射手座";
    }else if (($month == 12 && $day >= 22) || ($month == 1 && $day <= 19)) {
        $xingzuo = "摩羯座";
    }

    return $xingzuo;
}

/* *
   * 生成邀请码
* */
function create_code()
{
    $code = 'abcdefghijklmnopqrstuvwxyz';
    $rand = $code[rand(0, 25)]
        . strtoupper(dechex(date('m')))
        . date('d') . substr(time(), -5)
        . substr(microtime(), 2, 5)
        . sprintf('%02d', rand(0, 99));
    for (
        $a = md5($rand, true),
        $s = '0123456789abcdefghijklmnopqrstuvwxyz',
        $d = '',
        $f = 0;
        $f < 5;
        $g = ord($a[$f]),
        $d .= $s[($g ^ ord($a[$f + 8])) - $g & 0x1F],
        $f++
    ) ;
    return $d;
}

//根据生日算年龄
function birthday($birthday){ 
    $age = strtotime($birthday); 
    if($age === false){ 
     return false; 
    } 
    list($y1,$m1,$d1) = explode("-",date("Y-m-d",$age)); 
    $now = strtotime("now"); 
    list($y2,$m2,$d2) = explode("-",date("Y-m-d",$now)); 
    $age = $y2 - $y1; 
    if((int)($m2.$d2) < (int)($m1.$d1)) 
     $age -= 1; 
    return $age; 
}