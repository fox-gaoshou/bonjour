<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/9
 * Time: 20:12
 */

namespace bonjour\extend;


use bonjour\core\bonjour;

class admin_cache
{
    public function hMSet(int $uid,array $values)
    {
        return bonjour::$redis->ins->hMSet(sprintf('admin:%s',$uid),$values);
    }
    public function hMGet(int $uid,array $fields)
    {
        return bonjour::$redis->ins->hMGet(sprintf('admin:%s',$uid),$fields);
    }
    public function del(int $uid)
    {
        $key = sprintf('admin:%u',$uid);
        return bonjour::$redis->ins->del($key);
    }
}