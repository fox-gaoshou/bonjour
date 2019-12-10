<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/6
 * Time: 13:52
 */

namespace bonjour\lib;

use bonjour\comm;

class ip
{
    public function isBlack($ip)
    {
        $ip2long = ip2long($ip);
        return comm::$DataCenter->redis->hExists("blackIP",$ip2long);
    }
    public function check($pid,$socketIP)
    {
        // 判断是否在黑名单内
        if($this->isBlack($socketIP))
        {
        }
    }
}