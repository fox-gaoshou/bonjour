<?php


namespace bonjour\format\redis;


/**
 * @property int            $q_counter                  // 进入队列的 任务次数
 * @property string         $last_queue_task_id         // 最后进入队列的任务ID
 * @property int            $last_queue_task_time       // 最后进入队列的任务时间
 * @property int            $p_counter                  // 进入处理集合的 任务次数
 * @property string         $last_process_task_id       // 最后进入处理集合的 任务ID
 * @property int            $last_process_task_time     // 最后进入处理集合的 任务时间
 *
 * */
class group_info_of_stable_task
{}