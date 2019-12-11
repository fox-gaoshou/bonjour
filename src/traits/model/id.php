<?php


namespace bonjour\traits\model;


use bonjour\core\mysql;
use bonjour\format\res\res;


/**
 * @property string         $database_table
 * @property mysql          $mysql
 * */
trait model_select_by_id
{
    public function select_by_id(string $fields,int $id,bool $necessary=false)
    {
        $res = $this->mysql->select($fields)->from($this->database_table)->where([['id','=',$id, 'i']])->limit(1)->query();
        if ($res->code) return $res;
        if (($necessary == true) && ($res->qry->num_rows != 1)) return new res(1,'获取数据失败!');
        return $res;
    }
}


/**
 * @property string         $database_table
 * @property mysql          $mysql
 * */
trait model_update_by_id
{
    public function update_by_id(array $params,int $id,bool $necessary=false)
    {
        if(isset($params['id'])) return new res(1,'更新内容不能包含id');

        $res = $this->mysql->update($this->database_table)->set($params)->where([['id','=',$id,'i']])->limit(1)->query();
        if ($res->code) return $res;
        if (($necessary == true) && ($res->affected_rows != 1)) return new res(1,'更新数据失败，可能内容没有发生变化');
        return $res;
    }
}


/**
 * @property string         $database_table
 * @property mysql          $mysql
 * */
trait model_delete_by_id
{
    public function delete_by_id(int $id,bool $necessary=false)
    {
        $res = $this->mysql->delete()->from($this->database_table)->where([['id','=',$id,'i']])->query();
        if ($res->code) return $res;
        if (($necessary == true) && ($res->affected_rows != 1)) return new res(1,'删除数据失败，可能数据不存在!');
        return $res;
    }
}


/**
 * @property string         $database_table
 * @property mysql          $mysql
 * */
trait model_delete_in_id
{
    public function delete_in_id(array $id)
    {
        return $this->mysql->delete()->from($this->database_table)->where([['id','in',$id,'i']])->query();
    }
}