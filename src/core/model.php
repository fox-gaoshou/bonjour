<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/14
 * Time: 10:29
 */

namespace bonjour\core;


use bonjour\format\res\res;
use bonjour\format\res\res_mysql;


/**
 *
 * 适用于数据库操作模型
 * 每个数据库操作模型，只针对单独一个表的操作。
 *
 * @property \bonjour\core\mysql $mysql
 *
 * */
class model
{
    public $database_table;

    public function select_all($ss='*')
    {
        return $this->mysql->select($ss)->from($this->database_table)->query();
    }

    public function count(&$count,$where=null)
    {
        $res = $this->mysql->select_count()->from($this->database_table)->where($where)->query();
        if($res->code) return $res;
        $count = $res->data;
        return new res();
    }

    /**
     * 通用插入
     * $field，允许添加到数据的字段
     * $data，['key'=>'val','key1'=>'val1'] 一般直接$_POST
     *
     * @param array             $field
     * @param array             $data
     *
     * @return res|res_mysql
     * @throws
     * */
    public function common_insert(array &$field,array &$data)
    {
        $insert = array();
        foreach ($field as $key=>$val)
        {
            if(isset($data[$key])) $insert[] = [$key,$data[$key],$val];
        }
        if(empty($insert))
        {
            return new res(1,'没有任何可以添加的数据');
        }else{
            return $this->mysql->insert($this->database_table,$insert)->query();
        }
    }

    /**
     * 通用更新
     * $field，允许更新到数据的字段
     * $data，['key'=>'val','key1'=>'val1'] 一般直接$_POST
     *
     * @param array             $field
     * @param array             $data
     * @param array             $where
     * @param bool              $necessary
     *
     * @return res|res_mysql
     * @throws
     * */
    public function common_update(&$field,&$data,$where,$necessary=false)
    {
        $update = array();
        foreach ($field as $key=>$val)
        {
            if(isset($data[$key])) $update[] = [$key,'=',$data[$key],$val];
        }
        if(empty($update))
        {
            return new res_mysql(1,'没有任何可以更新的数据');
        }else{
            $res = $this->mysql->update($this->database_table)->set($update)->where($where)->query();
            if($res->code) return $res;
            if(($necessary == true) && ($res->affected_rows <= 0)) return new res_mysql(1,'更新失败，可能内容没有发送变化!');
            return $res;
        }
    }

    public function update_by_id_low_level(int $id,array $params,bool $necessary=false)
    {
        if(isset($params['id'])) return new res(1,'更新内容不能包含id');
        $res = $this->mysql->update($this->database_table)->set($params)->where([['id','=',$id,'i']])->limit(1)->query();
        if ($res->code) return $res;
        if (($necessary == true) && ($res->affected_rows != 1)) return new res(1,'更新数据失败，可能内容没有发生变化');
        return $res;
    }

    public function __get($name)
    {
        switch ($name)
        {
            case 'mysql':
                return $this->mysql = &bonjour::$mysql;
                break;
        }
    }
}