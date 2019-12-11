<?php


namespace bonjour\traits\model;


/**
 * @property string                 $database_table
 * @property \bonjour\core\mysql    $mysql
 * */
trait model_select_by_diff_hour
{
    public function select_in_diff_hour(string $ss,array $diff_hour)
    {
        $where = array(
            ['diff_hour','in',$diff_hour,'i']
        );
        return $this->mysql->select($ss)->from($this->database_table)->where($where)->query();
    }
    public function select_by_diff_hour(string $ss,int $diff_hour)
    {
        $where = array(
            ['diff_hour','=',$diff_hour,'i']
        );
        return $this->mysql->select($ss)->from($this->database_table)->where($where)->query();
    }
    public function select_count_by_diff_hour(int $diff_hour)
    {
        $where = array(
            ['diff_hour','=',$diff_hour,'i']
        );
        return $this->mysql->select_count()->from($this->database_table)->where($where)->query();
    }

}

/**
 * @property string                 $database_table
 * @property \bonjour\core\mysql    $mysql
 * */
trait model_slicing
{
    public function select_in_slicing(string $ss,array $slicing)
    {
        $where = array(
            ['slicing','in',$slicing,'i']
        );
        return $this->mysql->select($ss)->from($this->database_table)->where($where)->query();
    }
    public function select_by_slicing(string $ss,int $slicing)
    {
        $where = array(
            ['slicing','=',$slicing,'i']
        );
        return $this->mysql->select($ss)->from($this->database_table)->where($where)->query();
    }
    public function select_count_by_slicing(int $slicing)
    {
        return $this->mysql->select_count()->from($this->database_table)->where([['slicing','=',$slicing,'i']])->query();
    }

    public function select_count_by_slicing_and_assoc_key(int $slicing,string $assoc_key)
    {
        $where = array(
            ['slicing','=',$slicing,'i'],
            ['assoc_key','=',$assoc_key,'s']
        );
        return $this->mysql->select_count()->from($this->database_table)->where($where)->query();
    }

    public function select_by_slicing_and_assoc_key(string $ss,int $slicing,string $assoc_key)
    {
        $where = array(
            ['slicing','=',$slicing,'i'],
            ['assoc_key','=',$assoc_key,'s']
        );
        return $this->mysql->select($ss)->from($this->database_table)->where($where)->query();
    }
}


/**
 * @property string $database_      table
 * @property \bonjour\core\mysql    $mysql
 * */
trait model_select_all_and_sort
{
    public function select_all_and_sort(string $ss)
    {
        return $this->mysql->select($ss)->from($this->database_table)->order_by('`sort` asc')->query();
    }

}

/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_select_all
{
    public function select_all(string $ss)
    {
        return $this->mysql->select($ss)->from($this->database_table)->query();
    }
}


/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_select_for_params_list
{
    public function select_for_params_list()
    {
        $ss = '`title`,`id` as `value`';
        return $this->mysql->select($ss)->from($this->database_table)->order_by('`sort` asc')->query();
    }
}

/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_select_in_id
{
    public function select_in_id(string $ss,array $id_list)
    {
        return $this->mysql->select($ss)->from($this->database_table)->where([['id','in',$id_list,'i']])->query();
    }
}

/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_select_in_uid
{
    public function select_in_uid(string $ss,array $id)
    {
        return $this->mysql->select($ss)->from($this->database_table)->where([['uid','in',$id,'i']])->query();
    }
}

/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_select_rows
{
    public function select_rows(string $ss,int $rows)
    {
        return $this->mysql->select($ss)->from($this->database_table)->limit($rows)->query();
    }
}

/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_select_rows_and_sort
{
    public function select_rows_and_sort(string $ss,int $rows)
    {
        return $this->mysql->select($ss)->from($this->database_table)->order_by('`id` asc')->limit($rows)->query();
    }
}