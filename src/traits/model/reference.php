<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/7/6
 * Time: 16:26
 */

namespace bonjour\traits\model;

use bonjour\format\res\res;

/**
 * @property string                 $database_table
 * @property \bonjour\core\mysql    $mysql
 * */
trait model_reference
{
    public function incr_reference_by_id(int $id)
    {
        $update = array(
            ['reference','+=',1,'i']
        );
        $res = $this->mysql->update($this->database_table)->set($update)->where([['id','=',$id,'i']])->query();
        if($res->code) return $res;
        if($res->affected_rows != 1) new res(1,'增加引用量失败，可能目标数据不存在!');
        return $res;
    }

    public function decr_reference_by_id(int $id)
    {
        $update = array(
            ['reference','-=',1,'i']
        );
        $where = array(
            ['id','=',$id,'i'],
            ['reference','>=',1,'i']
        );
        $res = $this->mysql->update($this->database_table)->set($update)->where($where)->query();
        if($res->code) return $res;
        if($res->affected_rows != 1) return new res(1,'减少引用量失败，可能目标数据不存在！或者引用量等于0');
        return $res;
    }

    public function delete_by_id_and_check_reference(int $id)
    {
        $where = array(
            ['id','=',$id,'i'],
            ['reference','=',0,'i']
        );
        $res = $this->mysql->delete()->from($this->database_table)->where($where)->query();
        if($res->code) return $res;
        if($res->affected_rows != 1) return new res(1,'删除失败，可能数据不存在，或者引用量不等于0');
        return $res;
    }
}