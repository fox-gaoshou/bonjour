<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/16
 * Time: 16:17
 */

namespace bonjour\extend;


use bonjour\core\bonjour;
use bonjour\format\res\res_redis_stable_task;


class redis_task
{
    public $group;
    public $sha;

    /**
     * @param string $group
     * @throws \Exception
     */
    public function init(string $group)
    {
        $this->group = $group;
        $this->sha = bonjour::$redis->ins->hGetAll('rs:stable_task');
        if(empty($this->sha)) throw new \Exception('Can not get rs.');
        return $this;
    }

    /**
     * 获取任务组的信息
     * @return res_redis_stable_task
     */
    public function get_group_info()
    {
        $key = sprintf('task:group:%s',$this->group);
        $exists = bonjour::$redis->ins->exists($key);
        if(is_bool($exists)) return new res_redis_stable_task(1,'获取任务组缓存信息失败!');
        if($exists == 0) return new res_redis_stable_task(1,'任务组缓存信息不存在!');
        $info = bonjour::$redis->ins->hGetAll($key);
        return new res_redis_stable_task(0,'',$info);
    }

    /**
     * 获取任务信息
     * @param string $task_id
     * @return res_redis_stable_task
     */
    public function get_task_info(string $task_id)
    {
        $key = sprintf('task:info:%s:%s',$this->group,$task_id);
        $exists = bonjour::$redis->ins->exists($key);
        if(is_bool($exists)) return new res_redis_stable_task(1,'获取任务缓存信息失败!');
        if($exists == 0) return new res_redis_stable_task(2,'任务缓存信息不存在!');
        $info = bonjour::$redis->ins->hGetAll($key);
        if(is_bool($info)) return new res_redis_stable_task(1,'获取缓存信息失败!');
        $res = new res_redis_stable_task();
        $res->task_info = (object)$info;
        return $res;
    }

    public function set_task_info(string $task_id,array $set)
    {
        $key = sprintf('task:info:%s:%s',$this->group,$task_id);
        $exists = bonjour::$redis->ins->exists($key);
        if(is_bool($exists)) return new res_redis_stable_task(1,'获取任务缓存信息失败!');
        if($exists == 0) return new res_redis_stable_task(1,'任务缓存信息不存在!');
        $data = (object)bonjour::$redis->ins->hMSet($key,$set);
        return new res_redis_stable_task(0,'',$data);
    }

    /**
     * @return res_redis_stable_task
     * @throws \Exception
     */
    public function check_suspend_task()
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group]);
        return json_decode($res);
    }

    /**
     * @param int $timeout
     * @return res_redis_stable_task
     * @throws \Exception
     */
    public function check_process_task(int $timeout=65)
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group,$timeout]);
        return json_decode($res);
    }

    /**
     * @param int $timeout
     * @return res_redis_stable_task
     * @throws \Exception
     */
    public function check_done_task(int $timeout=604800)
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group,$timeout]);
        return json_decode($res);
    }

    /**
     * @param string $task_id
     * @param int $p_max
     * @return res_redis_stable_task
     * @throws \Exception
     */
    public function new(string $task_id,int $p_max=16)
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group,$task_id,$p_max]);
        return json_decode($res);
    }

    /**
     * @param string $task_id
     * @param int $task_suspend_seconds
     * @param int $p_max
     * @return res_redis_stable_task
     * @throws \Exception
     */
    public function new_task_delay_to_queue(string $task_id,int $task_suspend_seconds,int $p_max=16)
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group,$task_id,$task_suspend_seconds,$p_max]);
        return json_decode($res);
    }

    /**
     * 从排队队列中，弹出一个任务ID
     *
     * code = 0
     * 成功弹出一个任务ID
     * code = 1
     * 可能遇到错误
     * code = 2
     * 当前排队队列中，没有任务
     *
     * @return res_redis_stable_task
     * @throws \Exception
     */
    public function get()
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group]);
        return json_decode($res);
    }

    /**
     * 设置任务的处理次数上限
     *
     * @param string $task_id
     * @param int $p_max
     * @return res_redis_stable_task
     * @throws \Exception
     */
    public function set_p_max(string $task_id,int $p_max)
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group,$task_id,$p_max]);
        return json_decode($res);
    }



    /**
     * 消费端，标记任务释放
     *
     * 任务释放的时候，如果任务没有达到
     *
     * @param $task_id
     * @param $task_suspend_seconds
     * @return res_redis_stable_task
     * @throws \Exception
     */
    public function consumer_task_release($task_id,$task_suspend_seconds)
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group,$task_id,$task_suspend_seconds]);
        return json_decode($res);
    }

    /**
     * 消费端，标记任务结束
     * @param $task_id
     * @return res_redis_stable_task
     * @throws \Exception
     */
    public function consumer_task_done($task_id)
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group,$task_id]);
        return json_decode($res);
    }

    /**
     * 消费端，标记任务异常
     * @param $task_id
     * @param string $exc_note
     * @return mixed
     * @throws \Exception
     */
    public function consumer_task_except($task_id,string $exc_note)
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group,$task_id,$exc_note]);
        return json_decode($res);
    }

    /**
     * @param string $task_id
     * @param string $note
     * @return res_redis_stable_task
     * @throws \Exception
     */
    public function manager_task_suspend(string $task_id,string $note)
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group,$task_id,$note]);
        return json_decode($res);
    }

    /**
     * @param string $task_id
     * @param int $incr_p_max
     * @return res_redis_stable_task
     * @throws \Exception
     */
    public function manager_task_queue(string $task_id,int $incr_p_max)
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group,$task_id,$incr_p_max]);
        return json_decode($res);
    }

    /**
     * @param string $task_id
     * @param string $note
     * @return res_redis_stable_task
     * @throws \Exception
     */
    public function manager_task_except(string $task_id,string $note)
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group,$task_id,$note]);
        return json_decode($res);
    }

    /**
     * 管理员，删除已经完成的任务
     *
     * @param string $task_id
     * @return mixed
     * @throws \Exception
     */
    public function manager_delete_done_task(string $task_id)
    {
        if(isset($this->sha[__FUNCTION__]) == false) throw new \Exception(sprintf("Has not the method %s",__FUNCTION__));
        $res = bonjour::$redis->ins->evalSha($this->sha[__FUNCTION__],[$this->group,$task_id]);
        return json_decode($res);
    }

    public function select_count_on_queue_task()
    {
        $key = sprintf("task:queue:%s",$this->group);
        $count = bonjour::$redis->ins->lLen($key);
        if(is_bool($count)) return 0;
        return $count;
    }

    public function select_count_on_except()
    {
        $key = sprintf("task:except:%s",$this->group);
        $count = bonjour::$redis->ins->zCount($key,0,-1);
        if(is_bool($count)) return 0;
        return $count;
    }
    public function select_on_except(int $page)
    {
        $key = sprintf("task:except:%s",$this->group);
        $list = bonjour::$redis->ins->zRange($key,$page*20,-1);
        if(is_bool($list)) return [];
        return $list;
    }

    public function select_all_except_task()
    {
        $key = sprintf("task:except:%s",$this->group);
        $list = bonjour::$redis->ins->zRange($key,0,-1);
        if(is_bool($list)) return [];
        return $list;
    }
    public function select_all_done_task()
    {
        $key = sprintf("task:done:%s",$this->group);
        $list = bonjour::$redis->ins->zRange($key,0,-1);
        if(is_bool($list)) return [];
        return $list;
    }
}