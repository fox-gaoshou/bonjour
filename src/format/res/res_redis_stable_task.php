<?php


namespace bonjour\format\res;


/**
 * @property \bonjour\format\redis\group_info_of_stable_task    $group_info
 * @property \bonjour\format\redis\task_info_of_stable_task     $task_info
 * */
class res_redis_stable_task extends res_basic
{
    public $group_info;
    public $task_info;
    public function __construct(int $code=0, string $message='', $data = '', $log = '')
    {
        parent::__construct('redis_stable_task', $code, $message, $data, $log);
    }
}