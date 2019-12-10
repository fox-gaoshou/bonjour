<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2018/12/7
 * Time: 20:58
 */

namespace bonjour\lib;


use bonjour\core\bonjour;
use bonjour\core\br_plugin_redis_dev;

class lib_user_cache
{
//    use br_plugin_redis_dev;

    public function hMSet($uid,array $values)
    {
        return bonjour::$lib->redis->connection->hMSet(sprintf("user:%s",$uid),$values);
    }
    public function hMGet($uid,array $keys)
    {
        return bonjour::$lib->redis->connection->hMGet(sprintf("user:%s",$uid),$keys);
    }
    public function del($uid)
    {
        return bonjour::$lib->redis->connection->del(sprintf("user:%s",$uid));
    }

    public function bindUidOfBankModule($fd,$pid,$uid)
    {
        $this->redis->multi();
        $this->redis->hSet(sprintf("user:%s:%s",$pid,$uid),'bank_module_fd',$fd);
        $this->redis->set(sprintf("bank_module_user_fd:%s",$fd),$uid);
        $this->redis->exec();
    }
    public function unbindUidOfBankModule($pid,$uid)
    {
        $this->redis->multi();
        $this->redis->hDel(sprintf("user:%s:%s",$pid,$uid),'bank_module_fd');
        $this->redis->del(sprintf("bank_module_user_fd:%s"));
        $this->redis->exec();
    }
    public function get_bank_module_fd($pid,$uid)
    {
        return $this->redis->hGet(sprintf("user:%s:%s",$pid,$uid),'bank_module_fd');
    }
    public function get_bank_module_uid($fd)
    {
        $str = $this->redis->get(sprintf("bank_module_user_fd:%s",$fd));
        return explode(':',$str);
    }

    public function bindUID($fd,$pid,$uid)
    {
        $key = sprintf("user:%s:%s",$pid,$uid);
        $ct = $this->redis->hIncrBy($key,'sw_ct',1);
        if(is_bool($ct)) return false;
        $this->redis->multi();
        $this->redis->hSet($key,'sw_fd',$fd);
        $this->redis->set(sprintf("sw_fd:%s:%s",$fd,$ct),sprintf("%s:%s",$pid,$uid));
        if(!$this->redis->exec()) return false;
        return true;
    }
    public function unbindUID($fd)
    {
        $this->redis->del(sprintf("sw_fd:%s:*",$fd));
    }
    public function sw_fd($uid)
    {
        $res = $this->hMGet($uid,array('sw_fd','sw_ct'));
        if(is_bool($res)) return false;

    }
}