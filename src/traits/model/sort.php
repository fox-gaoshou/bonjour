<?php
namespace bonjour\traits\model;

use bonjour\format\res\res;

/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_sort
{
    public function select_data_of_max_sort($where=null)
    {
        $res = $this->mysql->select('coalesce(max(`sort`),0) as `sort`')->from($this->database_table)->where($where)->query();
        if($res->code) return $res;
        $sort = $res->qry->fetch_assoc()['sort'] + 1;
        return new res(0,'',$sort);
    }

    public function update_sort_by_id(int $id,int $sort,bool $necessary=false)
    {
        $res = $this->mysql->update($this->database_table)->set([['sort','=',$sort,'i']])->where([['id','=',$id,'i']])->query();
        if($res->code) return $res;
        if(($necessary == true) && ($res->affected_rows != 1)) return new res(1,'更新排序失败，可能内容没有发生变化，或者数据不存在!');
        return $res;
    }
}