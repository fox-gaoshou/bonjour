<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/6/28
 * Time: 19:42
 */

namespace bonjour\obj;


use bonjour\core\bonjour;
use bonjour\format\res\res;
use bonjour\model\nexus_module\pn_task;

class obj_people_nexus
{
    public static $channel_people_nexus_insert = '';
    public static $channel_people_nexus_update = 'people_nexus_update';

    public function get_task()
    {
        return bonjour::$redis->ins->rPop('people_nexus_tree_task_list');
    }
    public function insert(int $parent_id,int $user_id,int $operator_id,string $note='')
    {
        $res = bonjour::$container->get(pn_task::class)->insert(0,0,$parent_id,$user_id,$operator_id,$note);
        if($res->code) return $res;
        $task_id = $res->insert_id;

        bonjour::$redis->ins->lPush('people_nexus_tree_task_list',$task_id);
        return new res();
    }
    public function update(int $new_parent_id,int $user_id,int $operator_id,string $note = '')
    {
        $res = bonjour::$container->get(pn_task::class)->insert(0,1,$new_parent_id,$user_id,$operator_id,$note);
        if($res->code) return $res;
        $task_id = $res->insert_id;

        bonjour::$redis->ins->lPush('people_nexus_tree_task_list',$task_id);
        return new res();
    }
}