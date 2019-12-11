<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/28
 * Time: 18:40
 */

namespace bonjour\extend;


use bonjour\core\bonjour;

class api_cache
{
    public function get_version(string $api)
    {
        $version = bonjour::$redis->ins->get(sprintf('api:%s:version',$api));
        if($version === false) $version = 1;
        return $version;
    }
    public function incr_version(string $api)
    {
        bonjour::$redis->ins->incr(sprintf('api:%s:version',$api));
    }
}