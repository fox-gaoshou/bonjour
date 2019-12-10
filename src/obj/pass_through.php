<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/6/18
 * Time: 11:13
 */

namespace bonjour\obj;


use bonjour\core\bonjour;
use bonjour\format\res\res;

class pass_through
{
    public $table_name;
    public $primary_key;
    public $primary_type;
    public function get(string $table_name,array $conditions,$field)
    {
//        $res = new res();
//        foreach ($conditions as $c)
//        $cache_key = sprintf('%s:',$this->table_name,$this->primary_key);
//        $data = bonjour::$redis->ins->hGet($cache_key,$field);
//        if(is_bool($data))
//        {
//            $where = array([$this->primary_key,'=',$primary_value,$this->primary_type]);
//            $res = bonjour::$mysql->select('*')->from($this->table_name)->where($where)->query();
//            if($res->code) return $res;
//            if($res->qry->num_rows != 1)
//            {
//                $res->code =           1;
//                $res->error =           '查找的数据不存在!';
//                $res->log =             array(
//                    'table_name' =>     $this->table_name,
//                    'primary_key' =>    $this->primary_key,
//                    'field' =>          $field,
//                );
//                return $res;
//            }
//            $temp_data = $res->qry->fetch_assoc();
//            if(isset($temp_data[$field]) == false)
//            {
//                $res->code =           1;
//                $res->error =           '数据的字段不存在!';
//                $res->log =             array(
//                    'table_name' =>     $this->table_name,
//                    'field' =>          $field
//                );
//                return $res;
//            }
//        }
    }
    public function del()
    {
    }
}