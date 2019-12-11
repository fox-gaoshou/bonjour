<?php


namespace bonjour\traits\model;


use bonjour\format\mysql\prepare;
use bonjour\format\res\res;


/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_update_by_user_id
{
    public function update_by_user_id(array $params,int $user_id,bool $necessary=false)
    {
        if(isset($params['user_id'])) return new res(1,'更新内容不能包含user_id');

        $res = $this->mysql->update($this->database_table)->set($params)->where([['user_id','=',$user_id,'i']])->limit(1)->query();
        if($res->code) return $res;
        if(($necessary == true) && ($res->affected_rows != 1)) return new res(1,'更新数据失败，可能内容没有发生变化');
        return $res;
    }
}


/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_delete_by_user_id
{
    public function delete_by_user_id(int $user_id,bool $necessary=false)
    {
        $res = $this->mysql->delete()->from($this->database_table)->where([['user_id','=',$user_id,'i']])->query();
        if($res->code) return $res;
        if(($necessary == true) && ($res->affected_rows != 1)) return new res(1,'删除数据失败，可能数据不存在!');
        return $res;
    }
}


/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_pp_update_by_user_id
{
    public function pp_select_by_uid(int $action=0,$ss=null,int &$uid=null,bool $necessary=false)
    {
        /* @var prepare $pp */
        static $pp;
        static $is_needed;

        switch ($action)
        {
            case 0:
                $res = $this->mysql->select($ss)->from($this->database_table)->where([['uid','=',&$uid,'i']])->limit(1)->prepare();
                if($res->code) return $res;
                $pp =           $res->data;
                $is_needed =    $necessary;
                break;
            case 1:
                $res = $this->mysql->execute($mysql);
                if($res->code) return $res;
                if(($is_needed == true) && ($res->qry->num_rows != 1)) return new res(1,'查询数据失败，可能数据不存在!');
                return $res;
                break;
            case 2:
                $this->mysql->prepare_close($pp);
                break;
        }

        return new res();
    }
}


/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_pp_select_by_user_id
{
    public function pp_select_by_user_id(int $action=0,$ss=null,int &$user_id=null,bool $necessary=false)
    {
        /* @var prepare $pp */
        static $pp;
        static $is_needed;

        switch ($action)
        {
            case 0:
                $res = $this->mysql->select($ss)->from($this->database_table)->where([['user_id','=',&$user_id,'i']])->limit(1)->prepare();
                if($res->code) return $res;
                $pp =           $res->data;
                $is_needed =    $necessary;
                break;
            case 1:
                $res = $this->mysql->execute($pp);
                if($res->code) return $res;
                if(($is_needed == true) && ($res->affected_rows != 1)) return new res(1,'查询数据失败!，可能数据不存在!');
                return $res;
                break;
            case 2:
                $this->mysql->prepare_close($pp);
                break;
        }
        return new res();
    }
}


