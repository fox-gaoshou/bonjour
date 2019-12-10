<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/3/1
 * Time: 21:51
 */

namespace bonjour\obj;


use bonjour\core\br_plugin_db_dev;
use bonjour\core\br_plugin_redis_dev;

class obj_user
{
//    use br_plugin_db_dev;
//    use br_plugin_redis_dev;

    public $usrHeartbeatTimeKey = "usr_heartbeat";

    /**
     * 设置用户的最后心跳时间
     *
     * @param $uid
     * 用户UID
     *
     * */
//    public function setLastHeartbeatTime($uid)
//    {
//        $this->redis->zAdd($this->usrHeartbeatTimeKey,time(),$uid);
//    }

    /**
     * 获取用户的最后心跳时间
     *
     * @param $uid
     * 用户UID
     *
     * @return bool | float
     *
     *
     * */
//    public function getLastHeartbeatTime($uid)
//    {
//        return $this->redis->zScore($this->usrHeartbeatTimeKey,$uid);
//    }

    /**
     * 统计在线用户的数量
     *
     * @param $sTime
     * @param $eTime
     *
     * @return int
     * 返回用户的数量
     * */
//    public function countOnlineUsers(int $sTime,int $eTime)
//    {
//        return $this->redis->zCount($this->usrHeartbeatTimeKey,$sTime,$eTime);
//    }
}