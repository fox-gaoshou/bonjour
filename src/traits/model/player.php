<?php


namespace bonjour\traits\model;



use bonjour\format\res\res;


/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_select_by_player_id
{
    public function select_by_player_id(string $fields,int $player_id,bool $necessary=false)
    {
        $res = $this->mysql->select($fields)->from($this->database_table)->where([['player_id','=',$player_id, 'i']])->query();
        if ($res->code) return $res;
        if (($necessary == true) && ($res->qry->num_rows != 1)) return new res(1,'查询数据失败，可能数据不存在!');
        return $res;
    }
}


/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_pp_select_by_player_id
{
    public function pp_select_by_uid(int $action=0,$ss=null,int &$player_id=null,bool $necessary=false)
    {
        /* @var prepare $pp */
        static $pp;
        static $is_needed;

        switch ($action)
        {
            case 0:
                $res = $this->mysql->select($ss)->from($this->database_table)->where([['player_id','=',&$player_id,'i']])->prepare();
                if($res->code) return $res;
                $pp =           $res->data;
                $is_needed =    $necessary;
                break;
            case 1:
                $res = $this->mysql->execute($mysql);
                if($res->code) return $res;
                if(($is_needed == true) && ($res->qry->num_rows != 1)) return new res(1,'查询数据失败，可能数据不存在!',null,$pp);
                return $res;
                break;
            case 2:
                $this->mysql->prepare_close($pp);
                break;
        }

        return new res();
    }
}
