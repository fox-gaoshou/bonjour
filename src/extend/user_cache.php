<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/6
 * Time: 14:40
 */

namespace bonjour\extend;


use bonjour\core\bonjour;

class user_cache
{
    public      $redis;
    private     $rKey = 'user_heartbeat';

    public function __construct()
    {
        $this->redis = &bonjour::$redis->ins;
    }
    public function hMSet($uid,array $values)
    {
        return $this->redis->hMSet(sprintf('user:%s',$uid),$values);
    }

    public function hGet($uid,$key)
    {
        return $this->redis->hGet(sprintf('user:%s',$uid),$key);
    }
    public function hMGet($uid,array $keys)
    {
        return $this->redis->hMGet(sprintf('user:%s',$uid),$keys);
    }
    public function del($uid)
    {
        return $this->redis->del(sprintf('user:%s',$uid));
    }

    // 设置用户最新的心跳时间
    public function heartbeat($uid)
    {
        $this->redis->zAdd($this->rKey,time(),$uid);
        return true;
    }

    // 查询一定时间的在线人数
    public function heartbeat_count($sTime,$eTime)
    {
        return $this->redis->zCount($this->rKey,$sTime,$eTime);
    }

    //查询用户最新心跳时间
    public function last_heartbeat($uid)
    {
        return $this->redis->zScore($this->rKey,$uid);
    }
}