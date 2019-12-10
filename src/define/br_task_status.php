<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/4/13
 * Time: 13:38
 */

define("br_task_status_is_waiting",         2);
define("br_task_status_is_processing",      3);
define("br_task_status_is_pausing",         4);
define("br_task_status_was_paused",         5);
define("br_task_status_was_finished",       6);


define("br_task_status_no_handle",          0);
define("br_task_status_processing",         1);
define("br_task_status_done",               2);


define("br_order_status_processing",                    0);         // 处理中
define("br_order_status_success",                       1);         // 成功
define("br_order_status_failed",                        2);         // 失败
define("br_order_status_third_not_exists_the_order",    3);         // 三方不存在此订单号
define('br_order_status_unknown',                       4);         // 未知状态