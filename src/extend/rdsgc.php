<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/6
 * Time: 16:25
 */

namespace bonjour\extend;


use bonjour\core\bonjour;
use bonjour\format\res\res;

class rdsgc
{
    private $mysqli;
    private $redis;
    public function __construct()
    {
        $this->mysqli =     &bonjour::$mysql;
        $this->redis =      &bonjour::$redis->ins;
    }

    private function hGetAllFromDatabase($group,$key)
    {
        $res = new res();
        $sql = 'select * from bonjour.br_sys_global_config where `group`=? and `key`=? limit 1';
        $bindParam = array('ss',$group,$key);
        $res = $this->mysqli->prepare($sql,$bindParam);
        if($res->code) return $res;
//        $obj = $res->data;
//        var_dump($res);
        $stmt = $res->data;
//        exit;
//        $res = $this->mysqli->execute($obj);
//        if($res->qry->num_rows != 1)
//        {
//            return new res(1,'获取全局配置数据不存在!',array(
//                'sql' =>            $sql,
//                'bindParam' =>      $bindParam
//            ));
//        }
//        $rVal = $res->qry->fetch_assoc();
//        return new res(0,'',null,$rVal);
//        $stmt = $this->mysqli->prepare($sql);
//        if($res->code) return $res;
//        $stmt = $res->data->stmt;

        if(empty($stmt))
        {
            $res->errtype =         br_errtype_sql;
            $res->code =           br_errno_sql_query_error;
            $res->error =           '执行发生错误!';
            $res->log =             array(
                'error' =>          '预处理错误!',
                'sql' =>            $sql
            );
            return $res;
        }
        $bindParam = array('ss',$group,$key);
        $stmt->bind_param(...$bindParam);
        $stmt->execute();
        if($stmt->errno)
        {
            $res->errtype =         br_errtype_sql;
            $res->code =           br_errno_sql_query_error;
            $res->error =           '执行发生错误!';
            $res->log =             array(
                'errno' =>          $stmt->errno,
                'error' =>          $stmt->error,
                'sql' =>            $sql,
                'bindParam' =>      $bindParam
            );
            $stmt->close();
            return $res;
        }
        $qry = $stmt->get_result();
        $stmt->close();
        if($qry->num_rows != 1)
        {
            $res->errtype =         br_errtype_normal;
            $res->code =           1;
            $res->error =           '获取全局配置数据不存在!';
            $res->log =             array(
                'sql' =>            $sql,
                'bindParam' =>      $bindParam
            );
            return $res;
        }
        $rVal = $qry->fetch_assoc();
        $res->data = $rVal;
        return $res;
    }

    public function hGet($group,$key,$field)
    {
        $res = new res();

        $rKey = sprintf('sysGlobalConfig:%s:%s',$group,$key);
        $rVal = $this->redis->hGet($rKey,$field);
        if($rVal === false)
        {
            $res = $this->hGetAllFromDatabase($group,$key);
            if($res->code) return $res;
            if(isset($res->data[$field]) == false)
            {
                $res->errtype =     br_errtype_normal;
                $res->code =       1;
                $res->error =       sprintf('%s 字段不存在!',$field);
                return $res;
            }
            $this->redis->hMSet($rKey,$res->data);
            $rVal = $res->data[$field];
        }
        $res->data = $rVal;
        return $res;
    }

    public function hGetAll($group,$key)
    {
        $res = new res();

        $rKey = sprintf('sysGlobalConfig:%s:%s',$group,$key);
        $rVal = $this->redis->hGetAll($rKey);
        if($rVal === false)
        {
            $res->errtype =         br_errtype_redis;
            $res->code =           1;
            $res->error =           '执行发生错误!';
            $res->log =             'rdsgc hmGet返回内容为false 可能redis连接已经断开!';
            return $res;
        }
        if(empty($rVal))
        {
            $res = $this->hGetAllFromDatabase($group,$key);
            if($res->code) return $res;
            $rVal = $res->data;
            $this->redis->hMSet($rKey,$rVal);
            return $res;
        }
        $res->data = $rVal;
        return $res;
    }

    public function del($group,$key)
    {
        $rKey = sprintf('sysGlobalConfig:%s:%s',$group,$key);
        $this->redis->del($rKey);
    }
}