<?php


namespace bonjour\obj;


use bonjour\core\bonjour;
use bonjour\model\admin_module\admin_module_user;

class obj_admin_module
{
    public $database;
    public function set_database_table(string $database)
    {
        $this->database = $database;
        return $this;
    }
    public function insert_root(int $pid,string $nickname,string $username,string $password)
    {
        $table = $this->database . '.user';

        $insert = array(
            ['id',1,'i'],
            ['pid',$pid,'i'],
            ['nickname',$nickname,'s'],
            ['username',$username,'s'],
            ['password',md5($password),'s']
        );
        $res = bonjour::$mysql->insert($table,$insert)->query();
        if($res->code) return $res;
    }
    public function insert_user(int $pid,string $nickname,string $username,string $password)
    {
        $table = $this->database . '.user';

        $insert = array(
            ['pid',$pid,'i'],
            ['nickname',$nickname,'s'],
            ['username',$username,'s'],
            ['password',md5($password),'s']
        );
        $res = bonjour::$mysql->insert($table,$insert)->query();
        if($res->code) return $res;
    }
}