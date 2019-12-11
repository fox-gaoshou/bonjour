<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/9
 * Time: 20:15
 */

namespace bonjour\extend;


use bonjour\core\bonjour;

class agent_cache
{
    public function hMSet(int $uid,array $values)
    {
        return bonjour::$redis->ins->hMSet(sprintf('agent:%s',$uid),$values);
    }
    public function hMGet(int $uid,array $fields)
    {
        return bonjour::$redis->ins->hMGet(sprintf('agent:%s',$uid),$fields);
    }
}