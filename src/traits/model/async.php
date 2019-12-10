<?php


namespace bonjour\traits\model;


use bonjour\core\mysql;
use bonjour\format\res\res;


/**
 * @property string         $database_table
 * @property mysql          $mysql
 * */
trait model_async
{
    public function check(string $dt,int $id)
    {
        return $this->mysql->insert($this->database_table,[['dt',$dt,'s'],['id',$id,'i']])->query();
    }
    public function select_data_of_last_id(string $dt)
    {
        $res = $this->mysql->select('`id`')->from($this->database_table)->where([['dt','=',$dt,'s']])->order_by('`id` desc')->limit(1)->query();
        if($res->code) return $res;
        if($res->qry->num_rows != 1)
        {
            $last_id = 0;
        }else{
            $last_id = $res->qry->fetch_assoc()['id'];
        }
        return new res(0,'',$last_id);
    }
}
