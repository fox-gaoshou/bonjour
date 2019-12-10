<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/1/29
 * Time: 15:06
 */

namespace bonjour\lib;


use bonjour\core\br_plugin_redis_dev;

class lib_user_statistics
{
    use br_plugin_redis_dev;

    private $rKey = "usr_heartbeat";
    public function heartbeat($uid)
    {
        $this->redis->zAdd($this->rKey,time(),$uid);
    }
    public function count($sTime,$eTime)
    {
        return $this->redis->zCount($this->rKey,$sTime,$eTime);
    }

    public function setHeartbeatTime($uid)
    {
        $this->redis->zAdd($this->rKey,time(),$uid);
    }
    public function getHeartbeatTime($uid)
    {
        return $this->redis->zScore($this->rKey,$uid);
    }
//    public function count($sTime,$eTime,$timeout=10)
//    {
//        /* @var \bonjour\core\res $res */
//        $res = (object)array('errno'=>0);
//
//        $tempKey = sprintf("%s_%s_%s",$this->rKey,$sTime,$eTime);
//        $count = $this->redis->get($tempKey);
//        if(is_bool($count))
//        {
//            $count = $this->redis->zCount($this->rKey,$sTime,$eTime);
//            $this->redis->set($tempKey,$count,$timeout);
//        }
//        $res->data = $count;
//        return $res;
//    }
}