<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/6
 * Time: 11:49
 */

namespace bonjour\extend;


use bonjour\core\bonjour;
use bonjour\format\res\res;

class rdc
{
    private $mysqli;
    private $redis;
    public function __construct()
    {
        $this->mysqli =     &bonjour::$mysql->ins;
        $this->redis =      &bonjour::$redis->ins;
    }

    public function delGroup($group)
    {
        $rKey =             sprintf('bonjour:config:manager:%s',$group);
        $this->redis->delete($rKey);
    }

    public function delConfig($group,$configKey)
    {
        $rKey =             sprintf('bonjour:config:manager:%s:%s',$group,$configKey);
        $this->redis->delete($rKey);
    }

    public function hGetGroup($group,$field = 'value')
    {
        $res =              new res();
        $rKey =             sprintf('bonjour:config:manager:%s',$group);
        $rVal =             $this->redis->hGet($rKey,$field);
        if($rVal === false)
        {
            $sql = sprintf("select %s from br_config_manager_group where `key`='%s'",$field,$group);
            $qry = $this->mysqli->query($sql);
//            $qry = $qry->qry;
            if($this->mysqli->errno)
            {
                $res->code =       br_errno_sql_query_error;
                $res->error =       '获取配置组失败!';
                $res->log =         array(
                    'errno' =>      $this->mysqli->errno,
                    'error' =>      $this->mysqli->error,
                    'sql' =>        $sql
                );
                return $res;
            }
            if($qry->num_rows != 1)
            {
                $res->code =       1;
                $res->error =       '获取配置组失败!';
                $res->log =         sprintf("%s 字段不存在!",$field);
                return $res;
            }
            $data = $qry->fetch_assoc();
            $rVal = $data[$field];
            $this->redis->hSet($rKey,$field,$rVal);
        }

        $res->data = $rVal;
        return $res;
    }

    public function hmGetGroup($group,array $fields)
    {
        $res =              new res();
        $rKey =             sprintf('bonjour:config:manager:%s',$group);
        $rVal =             $this->redis->hMGet($rKey,$fields);
        $missFields =       array();

        foreach ($rVal as $key=>$val)
        {
            if($val === false) $missFields[] = "`{$key}`";
        }
        if(!empty($missFields))
        {
            $sql = sprintf("select %s from br_config_manager_group where `key`='%s' limit 1",implode(',',$missFields),$group);
            $qry = $this->mysqli->query($sql);
//            $qry = $qry->qry;
            if($this->mysqli->errno)
            {
                $res->code =       br_errno_sql_query_error;
                $res->error =       '执行发生错误!';
                $res->log =         array(
                    'errno' =>      $this->mysqli->errno,
                    'error' =>      $this->mysqli->error,
                    'sql' =>        $sql
                );
                return $res;
            }
            if($qry->num_rows != 1)
            {
                $res->code =       1;
                $res->error =       '获取配置组失败!';
                $res->log =         sprintf("group %s 不存在!",$group);
                return $res;
            }
            $data = $qry->fetch_assoc();
            $rVal = array_merge($rVal,$data);
            $this->redis->hMSet($rKey,$data);
        }
        $res->data = $rVal;
        return $res;
    }

    public function hGetConfig($group,$configKey,string $field = 'value')
    {
        $res =              new res();
        $rKey =             sprintf('bonjour:config:manager:%s:%s',$group,$configKey);
        $rVal =             $this->redis->hGet($rKey,$field);
        if(is_bool($rVal))
        {
            $res = $this->hGetGroup($group,'id');
            if($res->code) return $res;
            $groupID = $res->data;

            $sql = sprintf("select `%s` from bonjour.br_config_manager where `group_id`=%u and `key`='%s' limit 1",$field,$groupID,$configKey);
            $qry = $this->mysqli->query($sql);
//            $qry = $qry->qry;
            if($this->mysqli->errno)
            {
                $res->code =       br_errno_sql_query_error;
                $res->error =       '执行发生错误!';
                $res->log =         array(
                    'errno' =>      $this->mysqli->errno,
                    'error' =>      $this->mysqli->error,
                    'sql' =>        $sql
                );
                return $res;
            }
            if($qry->num_rows != 1)
            {
                $res->code =       1;
                $res->error =       '获取配置失败';
                $res->log =         sprintf("group_id:%u key:%s 配置不存在! sql:%s",$groupID,$configKey,$sql);
                return $res;
            }
            $data = $qry->fetch_assoc();
            $rVal = $data[$field];
            $this->redis->hSet($rKey,$field,$rVal);
        }
        $res->data = $rVal;
        return $res;
    }

    public function hmGetConfig(string $group,string $configKey,array $fields)
    {
        $res =              new res();
        $rKey =             sprintf('bonjour:config:manager:%s:%s',$group,$configKey);
        $rVal =             $this->redis->hMGet($rKey,$fields);
        $missFields =       array();

        foreach ($rVal as $key=>$val)
        {
            if($val === false) $missFields[] = "`{$key}`";
        }
        if(!empty($missFields))
        {
            $res = $this->hGetGroup($group,'id');
            if($res->code) return $res;
            $groupID = $res->data;

            $sql = sprintf("select %s from br_config_manager where `group_id`=%u and `key`='%s' limit 1",implode(',',$missFields),$groupID,$configKey);
            $qry = $this->mysqli->query($sql);
//            $qry = $qry->qry;
            if($this->mysqli->errno)
            {
                $res->code =       br_errno_sql_query_error;
                $res->error =       '执行发生错误!';
                $res->log =         array(
                    'errno' =>      $this->mysqli->errno,
                    'error' =>      $this->mysqli->error,
                    'sql' =>        $sql
                );
                return $res;
            }
            if($qry->num_rows != 1)
            {
                $res->code =       1;
                $res->error =       '获取配置失败!';
                $res->log =         sprintf("group %s key %s 不存在!",$group,$configKey);
                return $res;
            }
            $data = $qry->fetch_assoc();
            $rVal = array_merge($rVal,$data);
            $this->redis->hMSet($rKey,$data);
        }
        $res->data = $rVal;
        return $res;
    }
}