<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/14
 * Time: 22:05
 */

namespace bonjour\traits\model;


use bonjour\format\mysql\prepare;
use bonjour\format\res\res;
use bonjour\format\res\res_mysql;


/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_pp_select_by_field
{
    /**
     * 根据字段的值，查询出一条数据
     *
     * @param int $action
     * 0 = 初始化
     * 1 = 执行
     * 2 = 关闭
     * @param string $ss
     * 需要执行的 select 查询语句
     * @param string $field
     * 查询条件的字段
     * @param $value
     * 查询条件的值
     * @param bool $necessary
     * 是否必须的查询
     * @return res|res_mysql
     * @throws \Exception
     * */
    public function pp_select_by_field(int $action=0,string $ss=null,string $field=null,&$value=null,bool $necessary=false)
    {
        /* @var prepare $prepare */
        static $prepare;
        static $is_needed;

        switch ($action)
        {
            case 0:
                switch (gettype($field))
                {
                    case 'string':
                        $type = 's';
                        break;
                    case 'double':
                    case 'float':
                        $type = 'd';
                        break;
                    case 'integer':
                        $type = 'i';
                        break;
                    default:
                        throw new \Exception('不支持字段的数据类型');
                }
                $res = $this->mysql->select($ss)->from($this->database_table)->where([[$field,'=',&$value,$type]])->limit(1)->prepare();
                if($res->code) return $res;
                $prepare =      $res->data;
                $is_needed =    $necessary;
                return $res;
                break;
            case 1:
                $res = $this->mysql->execute($prepare);
                if(($is_needed == true) && ($res->qry->num_rows != 1)) return new res(1,'获取数据失败!',null,$prepare);
                break;
            case 2:
                $this->mysql->prepare_close($prepare);
                break;
        }
        return new res();
    }
}

/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_pp_select_by_id
{
    public function pp_select_by_id(int $action=0,string $ss=null,&$id=null,bool $necessary=false)
    {
        /* @var prepare $prepare */
        static $prepare;
        static $is_needed;

        switch ($action)
        {
            case 0:
                $res = $this->mysql->select($ss)->from($this->database_table)->where([['id','=',&$id,'i']])->limit(1)->prepare();
                if($res->code) return $res;
                $prepare =      $res->data;
                $is_needed =    $necessary;
                break;
            case 1:
                $res = $this->mysql->execute($prepare);
                if($res->code) return $res;
                if(($is_needed == true) && ($res->qry->num_rows != 1)) return new res(1,'获取数据失败!',null,$prepare);
                return $res;
                break;
            case 2:
                $this->mysql->prepare_close($prepare);
                break;
        }

        return new res();
    }
}

/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_pp_update_by_id
{
    public function pp_update_by_id(int $action=0,array &$update=null,array &$where = null,bool $necessary=false)
    {
        /* @var prepare $prepare */
        static $prepare;
        static $is_needed;

        switch ($action)
        {
            case 0:
                $res = $this->mysql->update($this->database_table)->set($update)->where($where)->limit(1)->prepare();
                if($res->code) return $res;
                $prepare =      $res->data;
                $is_needed =    $necessary;
                break;
            case 1:
                $res = $this->mysql->execute($prepare);
                if($res->code) return $res;
                if(($is_needed == true) && ($res->affected_rows != 1)) return new res(1,'更新数据失败!可能内容没有发生变化',null,$prepare);
                return $res;
                break;
            case 2:
                $this->mysql->prepare_close($prepare);
                break;
        }

        return new res();
    }
}

/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_pp_update_update_time_by_id
{
    public function pp_update_update_time_by_id(int $action=0,int &$id = null,bool $necessary=false)
    {
        /* @var prepare $prepare */
        static $prepare;
        static $is_needed;

        switch ($action)
        {
            case 0:
                $res = $this->mysql->update($this->database_table)->set("update_time=NOW()")->where([['id','=',&$id,'i']])->limit(1)->prepare();
                if($res->code) return $res;
                $prepare =      $res->data;
                $is_needed =    $necessary;
                break;
            case 1:
                $res = $this->mysql->execute($prepare);
                if($res->code) return $res;
                if(($is_needed == true) && ($res->affected_rows != 1)) return new res(1,'更新数据失败!可能内容没有发生变化',null,$prepare);
                return $res;
                break;
            case 2:
                $this->mysql->prepare_close($prepare);
                break;
        }

        return new res();
    }
}

/**
 * @property string $database_table
 * @property \bonjour\core\mysql $mysql
 * */
trait model_pp_update_last_update_time_by_id
{
    public function pp_update_last_update_time_by_id(int $action=0,int &$id = null,bool $necessary=false)
    {
        /* @var prepare $prepare */
        static $prepare;
        static $is_needed;

        switch ($action)
        {
            case 0:
                $res = $this->mysql->update($this->database_table)->set("last_update_time=NOW()")->where([['id','=',&$id,'i']])->prepare();
                if($res->code) return $res;
                $prepare =      $res->data;
                $is_needed =    $necessary;
                break;
            case 1:
                $res = $this->mysql->execute($prepare);
                if($res->code) return $res;
                if(($is_needed == true) && ($res->affected_rows != 1)) return new res(1,'更新数据失败!可能内容没有发生变化',null,$prepare);
                return $res;
                break;
            case 2:
                $this->mysql->prepare_close($prepare);
                break;
        }

        return new res();
    }
}
