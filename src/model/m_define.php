<?php


namespace bonjour\model;


use bonjour\core\bonjour;
use bonjour\core\model;
use bonjour\format\res\res;
use bonjour\traits\model\model_reference;
use bonjour\traits\model\model_select_by_id;


bonjour::$traits->include_model('id');
bonjour::$traits->include_model('reference');


class m_define extends model
{
    use model_select_by_id;
    use model_reference;

    public function insert(string $group,string $value,string $title)
    {
        $insert = array(
            ['group',$group,'s'],
            ['title',$title,'s'],
            ['value',$value,'s']
        );
        return $this->mysql->insert($this->database_table,$insert)->query();
    }

    public function select_by_group_and_page(string $ss,string $group,int $page)
    {
        $res = $this->mysql->select($ss)->from($this->database_table)->where([['group','=',$group,'s']])->page($page)->query();
        if($res->code) return $res;
        return $res;
    }

    public function select_by_group_and_value(string $ss,string $group,string $value,bool $necessary=false)
    {
        $res = $this->mysql->select($ss)->from($this->database_table)->where([['group','=',$group,'s'],['value','=',$value,'s']])->query();
        if(($necessary == true) && ($res->qry->num_rows != 1)) return new res(1,sprintf("定义不存在! group=%s value=%s",$group,$value));
        return $res;
    }
}