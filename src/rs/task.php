<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/4/25
 * Time: 21:56
 */

namespace rs;

use bonjour\core\bonjour;


require_once (__DIR__."/../../vendor/autoload.php");
require_once (__DIR__."/../../bonjour/autoload.php");
require_once (__DIR__."/../../app/autoload.php");


(new class{
    private $status_queue;
    private $status_done;
    private $status_process;
    private $status_suspend;
    private $status_except;

    private $p_max;
    private $script_local_res = "local res = {type='redis_stable_task',code=0,message='',data='',log=''};";

    public function __construct()
    {
        /*
         * 添加一个新任务的时候，自动赋值剩余的处理次数
         * 如果任务消耗完，处理次数，结束时，会被自动转移到异常集合
         *
         * */
        $this->p_max = 16;

        $this->status_queue = stable_task_line_status_queue;
        $this->status_process = stable_task_line_status_process;
        $this->status_done = stable_task_line_status_done;
        $this->status_suspend = stable_task_line_status_suspend;
        $this->status_except = stable_task_line_status_except;

        bonjour::$redis->connect();

        $new = bonjour::$redis->ins->script('load',$this->new());
        if($new === false) throw new \Exception('new script is error!');
        $get = bonjour::$redis->ins->script('load',$this->get());
        if($get === false) throw new \Exception('get script is error!');

        $set_p_max = bonjour::$redis->ins->script('load',$this->set_p_max());
        if($set_p_max === false) throw new \Exception('set_p_max script is error!');

        $new_task_delay_to_queue = bonjour::$redis->ins->script('load',$this->new_task_delay_to_queue());
        if($new === false) throw new \Exception('new_task_delay_to_queue script is error!');

        $consumer_task_release = bonjour::$redis->ins->script('load',$this->consumer_task_release());
        if($consumer_task_release === false) throw new \Exception('consumerProcessRelease script is error!');
        $consumer_task_done = bonjour::$redis->ins->script('load',$this->consumer_task_done());
        if($consumer_task_done === false) throw new \Exception('consumer_task_done script is error!');
        $consumer_task_except = bonjour::$redis->ins->script('load',$this->consumer_task_except());
        if($consumer_task_except === false) throw new \Exception('consumer_task_except script is error!');

        $manager_task_queue = bonjour::$redis->ins->script('load',$this->manager_task_queue());
        if($manager_task_queue === false) throw new \Exception('queue script is error!');
        $manager_task_suspend = bonjour::$redis->ins->script('load',$this->manager_task_suspend());
        if($manager_task_suspend === false) throw new \Exception('manager_task_suspend script is error!');

        $manager_task_except = bonjour::$redis->ins->script('load',$this->manager_task_except());
        if($manager_task_except === false) throw new \Exception('manager_task_except script is error!');

        $manager_delete_done_task = bonjour::$redis->ins->script('load',$this->manager_delete_done_task());
        if($manager_delete_done_task === false) throw new \Exception('manager_delete_done_task script is error!');

        $check_suspend_task = bonjour::$redis->ins->script('load',$this->check_suspend_task());
        if($check_suspend_task == false) throw new \Exception('check_suspend_task script is error!');

        $check_process_task = bonjour::$redis->ins->script('load',$this->check_process_task());
        if($check_process_task == false) throw new \Exception('check_process_task script is error!');

        $check_done_task = bonjour::$redis->ins->script('load',$this->check_done_task());
        if($check_done_task == false) throw new \Exception('check_done_task script is error!');

        bonjour::$redis->ins->del('rs:stable_task');
        bonjour::$redis->ins->hMSet('rs:stable_task',array(
            'new' => $new,
            'get' => $get,
            'set_p_max' => $set_p_max,
            'new_task_delay_to_queue' => $new_task_delay_to_queue,
            'consumer_task_release' => $consumer_task_release,
            'consumer_task_done' => $consumer_task_done,
            'consumer_task_except' => $consumer_task_except,
            'manager_task_queue' => $manager_task_queue,
            'manager_task_suspend' => $manager_task_suspend,
            'manager_task_except' => $manager_task_except,
            'manager_delete_done_task' => $manager_delete_done_task,
            'check_suspend_task' => $check_suspend_task,
            'check_process_task' => $check_process_task,
            'check_done_task' => $check_done_task
        ));
        var_dump('OJBK');
    }

    // 新添加任务
    private function new()
    {
        $script = "
            {$this->script_local_res}
            local task_group = ARGV[1];
            local task_id = ARGV[2];
            local task_p_max = tonumber(ARGV[3]);
            local task_group_info_key = 'task:group:' .. task_group;
            local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            local task_queue_key = 'task:queue:' .. task_group;
            --local task_process_key = 'task:process:' .. task_group;
            --local task_suspend_key = 'task:suspend:' .. task_group;
            --local task_except_key = 'task:except:' .. task_group;
            --local task_done_key = 'task:done:' .. task_group;
            local time = redis.call('time');
            local timestamp = tonumber(time[1]);
            
            redis.replicate_commands();
            
            -- 判断任务是否存在
            local exists = redis.call('exists',task_info_key);
            if (type(exists) == 'boolean') then return redis.error_reply('exists command return value is false'); end;
            if (exists == 1) then
                res['code'] = 1;
                res['message'] = 'This task is existing already!';
                return cjson.encode(res);
            end;
            
            -- 任务添加到队列
            redis.call('lpush',task_queue_key,task_id);
            
            -- 任务组记录进入的流量
            redis.call('hincrby',task_group_info_key,'q_counter',1);
            redis.call('hmset',task_group_info_key,'last_queue_task_id',task_id,'last_queue_task_time',timestamp);
            
            -- 设置任务信息
            redis.call('hmset',task_info_key,
                'q_counter',1,
                'q_time',timestamp,
                
                'p_counter',0,
                'p_max',{$this->p_max},
                'p_time',0,
                
                's_counter',0,
                's_time',0,
                's_note','',
                's_exp',0,
                
                'exc_counter',0,
                'exc_time',0,
                'exc_note','',
                
                'apply',0,
                'apply_time',0,
                'apply_note','',
                
                'status',{$this->status_queue},
                'd_time',0,
                'a_time',timestamp
            );
            
            res['data'] = {task_id=task_id,a_time=timestamp};
            return cjson.encode(res);
        ";
        return $script;
    }

    // 新任务-不进入排队，立即进入暂停列表
    private function new_task_delay_to_queue()
    {
        $script = "
            {$this->script_local_res}
            local task_group = ARGV[1];
            local task_id = ARGV[2];
            local task_suspend_seconds = tonumber(ARGV[3]);
            local task_p_max = tonumber(ARGV[4]);
            local task_group_info_key = 'task:group:' .. task_group;
            local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            local task_queue_key = 'task:queue:' .. task_group;
            --local task_process_key = 'task:process:' .. task_group;
            local task_suspend_key = 'task:suspend:' .. task_group;
            --local task_except_key = 'task:except:' .. task_group;
            --local task_done_key = 'task:done:' .. task_group;
            local time = redis.call('time');
            local timestamp = tonumber(time[1]);
            
            redis.replicate_commands();
            
            -- 判断任务是否存在
            local exists = redis.call('exists',task_info_key);
            if (type(exists) == 'boolean') then return redis.error_reply('exists command return value is false'); end;
            if (exists == 1) then
                res['code'] = 1;
                res['message'] = 'This task is existing already!';
                return cjson.encode(res);
            end;
            
            -- 任务需要进入暂停状态
            -- 计算暂停的超时时间
            local s_exp = timestamp + task_suspend_seconds;
            
            -- 添加任务，有序集合（暂停中）
            redis.call('zadd',task_suspend_key,s_exp,task_id);
            
            -- 修改任务组的信息
            redis.call('hincrby',task_group_info_key,'s_counter',1);
            redis.call('hmset',task_group_info_key,'last_suspend_task_id',task_id,'last_suspend_task_time',timestamp);
            
            -- 设置任务信息
            redis.call('hmset',task_info_key,
                'q_counter',0,
                'q_time','',
                
                'p_counter',0,
                'p_max',{$this->p_max},
                'p_time',0,
                
                's_counter',1,
                's_time',timestamp,
                's_note','新任务-延时执行',
                's_exp',s_exp,
                
                'exc_counter',0,
                'exc_time',0,
                'exc_note','',
                
                'apply',0,
                'apply_time',0,
                'apply_note','',
                
                'status',{$this->status_suspend},
                'd_time',0,
                'a_time',timestamp
            );
            
            res['data'] = {task_id=task_id,a_time=timestamp};
            res['message'] = '新任务进入暂停状态，暂停秒数：' .. task_suspend_seconds;
            return cjson.encode(res);
        ";
        return $script;
    }

    // 从排队中获取任务
    private function get()
    {
        $script = "
            {$this->script_local_res}
            local task_group = ARGV[1];
            --local task_id = ARGV[2];
            local task_group_info_key = 'task:group:' .. task_group;
            --local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            local task_queue_key = 'task:queue:' .. task_group;
            local task_process_key = 'task:process:' .. task_group;
            --local task_suspend_key = 'task:suspend:' .. task_group;
            --local task_except_key = 'task:except:' .. task_group;
            --local task_done_key = 'task:done:' .. task_group;
            local time = redis.call('time');
            local timestamp = tonumber(time[1]);
            
            redis.replicate_commands();
            
            -- 弹出任务记录
            local task_id =      redis.call('rpop',task_queue_key);
            if (type(task_id) == 'boolean') then
                res['code'] = 2;
                res['message'] = 'no task';
                return cjson.encode(res);
            end;
            local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            
            -- 判断任务是否存在
            local exists = redis.call('exists',task_info_key);
            if (type(exists) == 'boolean') then return redis.error_reply('exists command return value is false'); end;
            if (exists == 0) then return redis.error_reply('task info is not existing. task_id=' .. task_id); end;
            
            -- 修改任务组信息
            redis.call('hincrby',task_group_info_key,'p_counter',1);
            redis.call('hmset',task_group_info_key,'last_process_task_id',task_id,'last_process_task_time',timestamp);
            
            -- 修改任务信息
            redis.call('hincrby',task_info_key,'p_counter',1);
            redis.call('hmset',task_info_key,'status',{$this->status_process},'p_time',timestamp);
            
            -- 任务添加到集合（处理中）
            redis.call('zadd',task_process_key,timestamp,task_id);
            
            -- 回复内容
            res['data'] = task_id;
            return cjson.encode(res);
        ";

        return $script;
    }

    // 后台管理人员可以通过此方法，让任务重新排队
    private function manager_task_queue()
    {
        $reQueue = "
            -- 检查是否超过处理次数上限
            p_max = p_max + task_incr_p_max;
            if (p_counter >= p_max) then
                res['code'] = 1;
                res['message'] = '超过处理次数上限，不能再重新排队';
                return cjson.encode(res);
            end;
            
            -- 重新排队处理
            -- 修改任务组的信息
            redis.call('hincrby',task_group_info_key,'q_counter',1);
            redis.call('hmset',task_group_info_key,'last_queue_task_id',task_id,'last_queue_task_time',timestamp);
            
            -- 设置任务的信息
            redis.call('hincrby',task_info_key,'q_counter',1);
            redis.call('hmset',task_info_key,'status',{$this->status_queue},'q_time',timestamp,'p_max',p_max);
            
            -- 任务添加到队列
            redis.call('lpush',task_queue_key,task_id);
        ";
        $script = "
            {$this->script_local_res}
            local task_group = ARGV[1];
            local task_id = ARGV[2];
            local task_incr_p_max = tonumber(ARGV[3]);
            local task_group_info_key = 'task:group:' .. task_group;
            local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            local task_queue_key = 'task:queue:' .. task_group;
            local task_process_key = 'task:process:' .. task_group;
            local task_suspend_key = 'task:suspend:' .. task_group;
            local task_except_key = 'task:except:' .. task_group;
            local task_done_key = 'task:done:' .. task_group;
            local time = redis.call('time');
            local timestamp = tonumber(time[1]);
            
            redis.replicate_commands();
            
            -- 判断任务是否存在
            local exists = redis.call('exists',task_info_key);
            if (type(exists) == 'boolean') then return redis.error_reply('exists command return value is false'); end;
            if (exists == 0) then
                res['code'] = 1;
                res['message'] = 'Missing the task ' .. task_id;
                return cjson.encode(res);
            end;
            
            -- 检查任务的状态
            local taskInfo =    redis.call('hmget',task_info_key,'status','p_counter','p_time','p_max');
            local status =      tonumber(taskInfo[1]);
            local p_counter =    tonumber(taskInfo[2]);
            local p_time =       tonumber(taskInfo[3]);
            local p_max =        tonumber(taskInfo[4]);
            
            if (status == {$this->status_queue}) then
                res['code'] = 1;
                res['message'] = '任务已经在排队中，不能重复排队!';
                return cjson.encode(res);
            elseif (status == {$this->status_done}) then
                res['code'] = 1;
                res['message'] = '任务已经结束，不能再次操作!';
                return cjson.encode(res);
            elseif (status == {$this->status_process}) then
                -- 检查是否处理超时
                if ((p_time + 65) >= timestamp) then
                    res['code'] = 1;
                    res['message'] = '任务没有处理超时，不能立即进入排队!';
                    return cjson.encode(res);
                end;
                
                {$reQueue}
                
                -- 移除任务在 有序集合（任务处理中） 的信息
                redis.call('zrem',task_process_key,task_id);
                
                return cjson.encode(res);
            elseif (status == {$this->status_suspend}) then
                {$reQueue}
                
                -- 移除任务，有序集合（暂停中）
                redis.call('zrem',task_suspend_key,task_id);
                
                return cjson.encode(res);
            elseif (status == {$this->status_except}) then
                {$reQueue}
                
                -- 移除任务，有序集合（异常中）
                redis.call('zrem',task_except_key,task_id);
                
                return cjson.encode(res);
            else
                return redis.error_reply('Unknown task status. task_id=' .. task_id .. ' status=' .. status);
            end;
        ";
        return $script;
    }

    private function set_p_max()
    {
        $script = "
            {$this->script_local_res}
            local task_group = ARGV[1];
            local task_id = ARGV[2];
            local p_max = tonumber(ARGV[3]);
            --local task_group_info_key = 'task:group:' .. task_group;
            local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            --local task_queue_key = 'task:queue:' .. task_group;
            --local task_process_key = 'task:process:' .. task_group;
            --local task_suspend_key = 'task:suspend:' .. task_group;
            --local task_except_key = 'task:except:' .. task_group;
            --local task_done_key = 'task:done:' .. task_group;
            --local time = redis.call('time');
            --local timestamp = tonumber(time[1]);
            
            redis.replicate_commands();
            
            -- 判断任务是否存在
            local exists = redis.call('exists',task_info_key);
            if (type(exists) == 'boolean') then return redis.error_reply('exists key the return value is false'); end;
            if (exists == 0) then
                res['code'] = 1;
                res['message'] = 'Missing the task ' .. task_id;
                return cjson.encode(res);
            end;
            
            -- 检查任务的状态
            local status = redis.call('hget',task_info_key,'status');
            status = tonumber(status);
            if (status == {$this->status_done}) then
                res['code'] = 1;
                res['message'] = '任务已经结束，不能操作!';
                return cjson.encode(res);
            end;
            
            redis.call('hset',task_info_key,'p_max',p_max);
            return cjson.encode(res);
        ";

        return $script;
    }

    // 每次消费者处理完成后，调用此脚本，对任务进行重新进入队列
    // 如果后台管理人员，发起申请，会优先处理申请的状态，而忽略最终的状态
    private function consumer_task_release()
    {
        $script = "
            {$this->script_local_res}
            local task_group = ARGV[1];
            local task_id = ARGV[2];
            local task_suspend_seconds = tonumber(ARGV[3]);
            local task_group_info_key = 'task:group:' .. task_group;
            local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            local task_queue_key = 'task:queue:' .. task_group;
            local task_process_key = 'task:process:' .. task_group;
            local task_suspend_key = 'task:suspend:' .. task_group;
            local task_except_key = 'task:except:' .. task_group;
            local task_done_key = 'task:done:' .. task_group;
            local time = redis.call('time');
            local timestamp = tonumber(time[1]);
            
            redis.replicate_commands();
            
            -- 判断任务是否存在
            local exists = redis.call('exists',task_info_key);
            if (type(exists) == 'boolean') then return redis.error_reply('exists command return value is false'); end;
            if (exists == 0) then return redis.error_reply('task info is not existing. task_id=' .. task_id); end;
            
            -- 检查任务的状态
            local taskInfo =    redis.call('hmget',task_info_key,'status','p_counter','p_max','apply','apply_note');
            local status =      tonumber(taskInfo[1]);
            local p_counter =   tonumber(taskInfo[2]);
            local p_max =       tonumber(taskInfo[3]);
            local apply =       tonumber(taskInfo[4]);
            local apply_note =  taskInfo[5];
            
            -- 判断当前状态
            if (status ~= $this->status_process) then
                local msg = 'Important Error: the status of the task is not processing! when the consumer program to call func consumer_task_release.';
                msg = msg .. ' task_id=' .. task_id .. ' : status' .. status;
                return redis.error_reply(msg);
            end;
            
            if ((apply == 0) or (apply == {$this->status_queue})) then
                -- 没有任何申请，默认重新排队
                -- 检查处理次数
                
                p_counter = p_counter + 1;
                if (p_counter > p_max) then
                    -- 修改任务组的信息
                    redis.call('hincrby',task_group_info_key,'exc_counter',1);
                    redis.call('hmset',task_group_info_key,'last_except_task_id',task_id,'last_except_task_time',timestamp);
                    
                    -- 设置任务的信息
                    redis.call('hincrby',task_info_key,'exc_counter',1);
                    redis.call('hmset',task_info_key,'status',{$this->status_except},'p_counter',p_counter,'exc_note','超过处理次数','exc_time',timestamp);
                    
                    -- 添加任务，有序集合（异常中）
                    redis.call('zadd',task_except_key,timestamp,task_id);
                    
                    res['message'] = '处理次数超过上限，任务进入异常状态!';
                elseif (task_suspend_seconds > 0) then
                    -- 任务需要进入暂停状态
                    local s_exp = timestamp + task_suspend_seconds;
                    
                    -- 修改任务组的信息
                    redis.call('hincrby',task_group_info_key,'s_counter',1);
                    redis.call('hmset',task_group_info_key,'last_suspend_task_id',task_id,'last_suspend_task_time',timestamp);
                    
                    -- 设置任务的信息
                    redis.call('hincrby',task_info_key,'s_counter',1);
                    redis.call('hmset',task_info_key,'status',{$this->status_suspend},'s_time',timestamp,'s_exp',s_exp);
                    
                    -- 添加任务，有序集合（暂停中）
                    redis.call('zadd',task_suspend_key,s_exp,task_id);
                    
                    res['message'] = '任务进入暂停状态，暂停秒数：' .. task_suspend_seconds;
                else
                    -- 重新排队处理
                    -- 修改任务组的信息
                    redis.call('hincrby',task_group_info_key,'q_counter',1);
                    redis.call('hmset',task_group_info_key,'last_queue_task_id',task_id,'last_queue_task_time',timestamp);
                    
                    -- 设置任务的信息
                    redis.call('hincrby',task_info_key,'q_counter',1);
                    redis.call('hmset',task_info_key,'status',{$this->status_queue},'q_time',timestamp);
                    
                    -- 任务添加到队列
                    redis.call('lpush',task_queue_key,task_id);
                    
                    res['message'] = '任务重新投放到队列中，等待处理!';
                end;
            elseif (apply == {$this->status_suspend}) then
                -- 申请暂停
                -- 修改任务组的信息
                redis.call('hincrby',task_group_info_key,'s_counter',1);
                redis.call('hmset',task_group_info_key,'last_suspend_task_id',task_id,'last_suspend_task_time',timestamp);
                
                -- 设置任务的信息
                redis.call('hincrby',task_info_key,'s_counter',1);
                redis.call('hmset',task_info_key,'status',{$this->status_suspend},'s_note',apply_note,'s_time',timestamp);
                
                -- 添加任务，有序集合（暂停中）
                redis.call('zadd',task_suspend_key,timestamp,task_id);
                
                res['message'] = '管理人员申请任务进入暂停状态，处理成功!';
            elseif (apply == {$this->status_except}) then
                -- 申请异常
                -- 修改任务组的信息
                redis.call('hincrby',task_group_info_key,'exc_counter',1);
                redis.call('hmset',task_group_info_key,'last_except_task_id',task_id,'last_except_task_time',timestamp);
                
                -- 设置任务信息
                redis.call('hincrby',task_info_key,'exc_counter',1);
                redis.call('hmset',task_info_key,'status',{$this->status_except},'exc_note',apply_note,'exc_time',timestamp);
                
                -- 添加任务，有序集合（异常中）
                redis.call('zadd',task_except_key,timestamp,task_id);
                
                res['message'] = '管理人员申请任务进入异常状态，处理成功!';
            elseif (apply == {$this->status_done}) then
                -- 申请完成
                -- 修改任务组的信息
                redis.call('hincrby',task_group_info_key,'dCounter',1);
                redis.call('hmset',task_group_info_key,'last_done_task_id',task_id,'lastDoneTaskTime',timestamp);
                
                -- 设置任务的信息
                redis.call('hmset',task_info_key,'status',{$this->status_done},'d_time',timestamp,'apply',0,'apply_note','',apply_time,0);
                
                -- 添加任务，有序集合（已经结束）
                redis.call('zadd',task_done_key,timestamp,task_id);
                
                res['message'] = '管理人员申请任务进入结束状态，处理成功!';
            else
                return redis.error_reply('Unknown status of the task. task_id=' .. task_id .. ' taskStatus=' .. status);
            end;
            
            -- 设置任务信息
            redis.call('hmset',task_info_key,'apply',0,'apply_note','','apply_time',0);
            
            -- 移除任务在 有序集合（任务处理中） 的信息
            redis.call('zrem',task_process_key,task_id);
            
            return cjson.encode(res);
        ";
        return $script;
    }

    // 消费者直接完成任务
    private function consumer_task_done()
    {
        $script = "
            {$this->script_local_res}
            local task_group = ARGV[1];
            local task_id = ARGV[2];
            local task_group_info_key = 'task:group:' .. task_group;
            local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            --local task_queue_key = 'task:queue:' .. task_group;
            local task_process_key = 'task:process:' .. task_group;
            --local task_suspend_key = 'task:suspend:' .. task_group;
            --local task_except_key = 'task:except:' .. task_group;
            local task_done_key = 'task:done:' .. task_group;
            local time = redis.call('time');
            local timestamp = tonumber(time[1]);
            
            redis.replicate_commands();
            
            -- 判断任务是否存在
            local exists = redis.call('exists',task_info_key);
            if (type(exists) == 'boolean') then return redis.error_reply('exists command return value is false'); end;
            if (exists == 0) then return redis.error_reply('task info is not existing. task_id=' .. task_id); end;
            
            -- 检查任务的状态
            local status =      redis.call('hget',task_info_key,'status');
            status =            tonumber(status);
            
            -- 判断当前状态
            if (status ~= $this->status_process) then
                local msg = 'Important Error: the status of the task is not processing! when the consumer program to call func consumerProcessEndToQueue.';
                msg = msg .. ' task_id=' .. task_id .. ' : status' .. status;
                return redis.error_reply(msg);
            end;
            
            -- 修改任务组的信息
            redis.call('hincrby',task_group_info_key,'dCounter',1);
            redis.call('hmset',task_group_info_key,'last_done_task_id',task_id,'lastDoneTaskTime',timestamp);
            
            -- 设置任务的信息
            redis.call('hmset',task_info_key,'status',{$this->status_done},'apply',0,'apply_note','','apply_time',0,'d_time',timestamp);
            
            -- 添加任务，有序集合（已经结束）
            redis.call('zadd',task_done_key,timestamp,task_id);
            
            -- 移除任务，有序集合（任务处理中）
            redis.call('zrem',task_process_key,task_id);
            
            res['data'] = '成功结束任务';
            return cjson.encode(res);
        ";
        return $script;
    }

    // 消费者，使任务进入异常
    private function consumer_task_except()
    {
        $script = "
            {$this->script_local_res}
            local task_group = ARGV[1];
            local task_id = ARGV[2];
            local exc_note = ARGV[3];
            local task_group_info_key = 'task:group:' .. task_group;
            local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            --local task_queue_key = 'task:queue:' .. task_group;
            local task_process_key = 'task:process:' .. task_group;
            --local task_suspend_key = 'task:suspend:' .. task_group;
            local task_except_key = 'task:except:' .. task_group;
            --local task_done_key = 'task:done:' .. task_group;
            local time = redis.call('time');
            local timestamp = tonumber(time[1]);
            
            redis.replicate_commands();
            
            -- 判断任务是否存在
            local exists = redis.call('exists',task_info_key);
            if (type(exists) == 'boolean') then return redis.error_reply('exists command return value is false'); end;
            if (exists == 0) then
                res['code'] = 1;
                res['message'] = 'Missing the task ' .. task_id;
                return cjson.encode(res);
            end;
            
            -- 检查任务的状态
            local status =      redis.call('hget',task_info_key,'status');
            status =            tonumber(status);
            
            -- 判断当前状态
            if (status ~= $this->status_process) then
                local msg = 'Important Error: the status of the task is not processing! when the consumer program to call func consumerProcessEndToQueue.';
                msg = msg .. ' task_id=' .. task_id .. ' : status' .. status;
                return redis.error_reply(msg);
            end;
            
            -- 修改任务组的信息
            redis.call('hincrby',task_group_info_key,'exc_counter',1);
            redis.call('hmset',task_group_info_key,'last_except_task_id',task_id,'last_except_task_time',timestamp);
            
            -- 设置任务的信息
            redis.call('hmset',task_info_key,'status',{$this->status_except},'exc_note',exc_note,'exc_time',timestamp,'apply',0,'apply_note','',apply_time,0);
            
            -- 添加任务，有序集合（已经结束）
            redis.call('zadd',task_except_key,timestamp,task_id);
            
            -- 移除任务在 有序集合（任务处理中） 的信息
            redis.call('zrem',task_process_key,task_id);
            
            return cjson.encode(res);
        ";
        return $script;
    }

    // 后台管理人员可以通过此方法，申请暂停
    private function manager_task_suspend()
    {
        $script = "
            {$this->script_local_res}
            local task_group = ARGV[1];
            local task_id = ARGV[2];
            local apply_note = ARGV[3];
            local task_group_info_key = 'task:group:' .. task_group;
            local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            local task_queue_key = 'task:queue:' .. task_group;
            local task_process_key = 'task:process:' .. task_group;
            local task_suspend_key = 'task:suspend:' .. task_group;
            local task_except_key = 'task:except:' .. task_group;
            local task_done_key = 'task:done:' .. task_group;
            local time = redis.call('time');
            local timestamp = tonumber(time[1]);
            
            redis.replicate_commands();
            
            -- 判断任务是否存在
            local exists = redis.call('exists',task_info_key);
            if (type(exists) == 'boolean') then return redis.error_reply('exists key the return value is false'); end;
            if (exists == 0) then
                res['code'] = 1;
                res['message'] = 'Missing the task ' .. task_id;
                return cjson.encode(res);
            end;
            
            -- 检查任务的状态
            local status = redis.call('hget',task_info_key,'status');
            status = tonumber(status);
            
            if (status == {$this->status_queue}) then
                -- 设置任务的信息
                redis.call('hmset',task_info_key,'apply',{$this->status_suspend},'apply_time',timestamp,'apply_note',apply_note);
                return cjson.encode(res);
            elseif (status == {$this->status_done}) then
                res['code'] = 1;
                res['message'] = '任务已经结束，不能操作!';
                return cjson.encode(res);
            elseif (status == {$this->status_process}) then
                -- 设置任务的信息
                redis.call('hmset',task_info_key,'apply',{$this->status_suspend},'apply_time',timestamp,'apply_note',apply_note);
                return cjson.encode(res);
            elseif (status == {$this->status_suspend}) then
                res['code'] = 1;
                res['message'] = '任务已经再暂停中，不能重复操作!';
                return cjson.encode(res);
            elseif (status == {$this->status_except}) then
                res['code'] = 1;
                res['message'] = '任务在异常中，不能操作!';
                return cjson.encode(res);
            else
                return redis.error_reply('Unknown task status. task_id=' .. task_id .. ' status=' .. status);
            end;
        ";

        return $script;
    }

    private function manager_task_except()
    {
        $script = "
            {$this->script_local_res}
            local task_group = ARGV[1];
            local task_id = ARGV[2];
            local exc_note = ARGV[3];
            local task_group_info_key = 'task:group:' .. task_group;
            local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            --local task_queue_key = 'task:queue:' .. task_group;
            local task_process_key = 'task:process:' .. task_group;
            --local task_suspend_key = 'task:suspend:' .. task_group;
            local task_except_key = 'task:except:' .. task_group;
            --local task_done_key = 'task:done:' .. task_group;
            local time = redis.call('time');
            local timestamp = tonumber(time[1]);
            
            redis.replicate_commands();
            
            -- 判断任务是否存在
            local exists = redis.call('exists',task_info_key);
            if (type(exists) == 'boolean') then return redis.error_reply('exists command return value is false'); end;
            if (exists == 0) then
                res['code'] = 1;
                res['message'] = 'Missing the task ' .. task_id;
                return cjson.encode(res);
            end;
            
            -- 检查任务的状态
            local status = redis.call('hget',task_info_key,'status');
            status = tonumber(status);
            
            if (status == {$this->status_queue}) then
                -- 设置任务的信息
                redis.call('hmset',task_info_key,'apply',{$this->status_except},'apply_time',timestamp,'apply_note',apply_note);
                return cjson.encode(res);
            elseif (status == {$this->status_done}) then
                res['code'] = 1;
                res['message'] = '任务已经结束，不能操作!';
                return cjson.encode(res);
            elseif (status == {$this->status_process}) then
                -- 设置任务的信息
                redis.call('hmset',task_info_key,'apply',{$this->status_except},'apply_time',timestamp,'apply_note',apply_note);
                return cjson.encode(res);
            elseif (status == {$this->status_suspend}) then
                -- 设置任务的信息
                redis.call('hmset',task_info_key,'apply',{$this->status_except},'apply_time',timestamp,'apply_note',apply_note);
                return cjson.encode(res);
            elseif (status == {$this->status_except}) then
                res['code'] = 1;
                res['message'] = '任务已经再异常中，不能重复操作!';
                return cjson.encode(res);
            else
                return redis.error_reply('Unknown task status. task_id=' .. task_id .. ' status=' .. status);
            end;
        ";
        return $script;
    }

    // 定时任务调用此方法，检查暂停的任务，重新回归到排队中
    private function check_suspend_task()
    {
        $script = "
            -- version 
            {$this->script_local_res}
            local task_group = ARGV[1];
            local task_group_info_key = 'task:group:' .. task_group;
            --local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            local task_queue_key = 'task:queue:' .. task_group;
            --local task_process_key = 'task:process:' .. task_group;
            local task_suspend_key = 'task:suspend:' .. task_group;
            --local task_except_key = 'task:except:' .. task_group;
            --local task_done_key = 'task:done:' .. task_group;
            local time = redis.call('time');
            local timestamp = tonumber(time[1]);
            
            redis.replicate_commands();
            
            -- 没有数据的时候，redis依然返回array类型
            local taskList = redis.call('zrangebyscore',task_suspend_key,0,timestamp);
            
            local task_info_key;
            for i,task_id in ipairs(taskList) do
                task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
                
                -- 修改任务组的信息
                redis.call('hincrby',task_group_info_key,'q_counter',1);
                redis.call('hmset',task_group_info_key,'last_queue_task_id',task_id,'last_queue_task_time',timestamp);
                
                -- 设置任务的信息
                redis.call('hincrby',task_info_key,'q_counter',1);
                redis.call('hmset',task_info_key,'status',{$this->status_queue},'q_time',timestamp);
                
                -- 任务添加到队列
                redis.call('lpush',task_queue_key,task_id);
                
                 -- 移除任务 有序集合（暂停中）
                redis.call('zrem',task_suspend_key,task_id);
            end;
            
            res['data'] = #taskList;
            return cjson.encode(res);
        ";
        return $script;
    }

    // 定时任务调用此方法，检查处理超时的任务，重新回归到排队中，或者超过处理次数上限进入异常
    private function check_process_task()
    {
        $script = "
            -- version 
            {$this->script_local_res}
            local task_group = ARGV[1];
            local task_timeout = tonumber(ARGV[2]);
            local task_group_info_key = 'task:group:' .. task_group;
            --local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            local task_queue_key = 'task:queue:' .. task_group;
            local task_process_key = 'task:process:' .. task_group;
            --local task_suspend_key = 'task:suspend:' .. task_group;
            local task_except_key = 'task:except:' .. task_group;
            --local task_done_key = 'task:done:' .. task_group;
            
            -- 获取当前时间
            local time = redis.call('time');
            local timestamp = tonumber(time[1]);
            
            -- 当前时间减去超时的秒数，大于此时间的任务，属于超时
            local timeout = timestamp-task_timeout;
            
            redis.replicate_commands();
            
            -- 没有数据的时候，redis依然返回array类型
            local taskList = redis.call('zrangebyscore',task_process_key,0,timeout);
            
            local task_info_key;
            for i,task_id in ipairs(taskList) do
                task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
                
                -- 检查任务的状态
                local task_info =   redis.call('hmget',task_info_key,'status','p_counter','p_max','apply','apply_note');
                local status =      tonumber(task_info[1]);
                local p_counter =   tonumber(task_info[2]);
                local p_max =       tonumber(task_info[3]);
                
                -- 如果处理次数超过上限，进入异常状态
                if (p_counter >= p_max) then
                    -- 修改任务组的信息
                    redis.call('hincrby',task_group_info_key,'exc_counter',1);
                    redis.call('hmset',task_group_info_key,'last_except_task_id',task_id,'last_except_task_time',timestamp);
                    
                    -- 设置任务的信息
                    redis.call('hincrby',task_info_key,'exc_counter',1);
                    redis.call('hmset',task_info_key,'status',{$this->status_except},'exc_note','超过处理上限次数','exc_time',timestamp);
                    
                    -- 添加任务，有序集合（异常中）
                    redis.call('zadd',task_except_key,timestamp,task_id);
                    
                    -- 移除任务 有序集合（处理中）
                    redis.call('zrem',task_process_key,task_id);
                else
                    -- 修改任务组的信息
                    redis.call('hincrby',task_group_info_key,'q_counter',1);
                    redis.call('hmset',task_group_info_key,'last_queue_task_id',task_id,'last_queue_task_time',timestamp);
                    
                    -- 设置任务的信息
                    redis.call('hincrby',task_info_key,'q_counter',1);
                    redis.call('hmset',task_info_key,'status',{$this->status_queue},'q_time',timestamp);
                    
                    -- 任务添加到队列
                    redis.call('lpush',task_queue_key,task_id);
                    
                    -- 移除任务 有序集合（处理中）
                    redis.call('zrem',task_process_key,task_id);
                end;
            end;
            
            res['data'] = #taskList;
            return cjson.encode(res);
        ";
        return $script;
    }

    // 定时任务，清空已经完成的任务
    private function check_done_task()
    {
        $script = "
            -- version 
            {$this->script_local_res}
            local task_group = ARGV[1];
            local task_timeout = tonumber(ARGV[2]);
            local task_group_info_key = 'task:group:' .. task_group;
            --local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            --local task_queue_key = 'task:queue:' .. task_group;
            --local task_process_key = 'task:process:' .. task_group;
            --local task_suspend_key = 'task:suspend:' .. task_group;
            --local task_except_key = 'task:except:' .. task_group;
            local task_done_key = 'task:done:' .. task_group;
            
            -- 获取当前时间
            local time = redis.call('time');
            local timestamp = tonumber(time[1]);
            
            -- 当前时间减去超时的秒数，大于此时间的任务，属于超时
            local timeout = timestamp - task_timeout;
            
            redis.replicate_commands();
            
            -- 没有数据的时候，redis依然返回array类型
            local taskList = redis.call('zrangebyscore',task_done_key,0,timeout);
            
            local task_info_key;
            for i,task_id in ipairs(taskList) do
                task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
                
                -- 清空任务信息
                redis.call('del',task_info_key);
                
                -- 移除任务 有序集合（已经完成）
                redis.call('zrem',task_done_key,task_id);
            end;
            
            -- 返回taskList数组的 条目数
            res['data'] = #taskList;
            return cjson.encode(res);
        ";
        return $script;
    }

    // 删除指定的已经完成的任务
    private function manager_delete_done_task()
    {
        $script = "
            {$this->script_local_res}
            local task_group = ARGV[1];
            local task_id = ARGV[2];
            local task_group_info_key = 'task:group:' .. task_group;
            local task_info_key = 'task:info:' .. task_group .. ':' .. task_id;
            --local task_queue_key = 'task:queue:' .. task_group;
            --local task_process_key = 'task:process:' .. task_group;
            --local task_suspend_key = 'task:suspend:' .. task_group;
            --local task_except_key = 'task:except:' .. task_group;
            local task_done_key = 'task:done:' .. task_group;
            --local time = redis.call('time');
            --local timestamp = tonumber(time[1]);
            
            redis.replicate_commands();
            
            -- 判断任务是否存在
            local exists = redis.call('exists',task_info_key);
            if (type(exists) == 'boolean') then return redis.error_reply('exists command return value is false'); end;
            if (exists == 0) then
                res['code'] = 1;
                res['message'] = 'Missing the task ' .. task_id;
                return cjson.encode(res);
            end;
            
            -- 检查任务的状态
            local status = redis.call('hget',task_info_key,'status');
            status = tonumber(status);
            
            if (status ~= {$this->status_done}) then
                res['code'] = 1;
                res['message'] = '任务还没有已经结束，不能操作!';
                return cjson.encode(res);
            end;
            
            -- 清空任务信息
            redis.call('del',task_info_key);
            
            -- 移除任务 有序集合（已经完成）
            redis.call('zrem',task_done_key,task_id);
            
            res['code'] = 0;
            res['message'] = '删除任务ID(' .. task_id .. ')成功!';
            return cjson.encode(res);
        ";
        return $script;
    }
});