<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2018/12/7
 * Time: 18:43
 */

//define('operation_status_failed',                   2);     // 三方平台失败
//define('operation_status_third_platform_loss',      3);     // 三方平台丢失
define('operation_status',array(
//    operation_status_created =>             '等待操作',
//    operation_status_processing =>          '处理中',
//    operation_status_finish =>              '',
//    operation_status_handling =>        '操作处理中',
//    operation_status_success =>         '操作已经成功',
//    operation_status_handling =>        '操作失败',
//    operation_status_handling =>        '操作丢失'
));




// operation_user 的操作状态
define('operation_user_nothing',                    0);             // 用户没有开启操作
define('operation_user_has_got_guid',               1);             // 用户申请了guid，但是还未开始操作
define('operation_user_processing',                 2);             // 用户的操作任务，进行中

// operation_list 的操作状态
define('operation_status_created',                  0);             // 操作 已经生成，但是没有进行处理
define('operation_status_processing',               1);             // 操作 已经被抢占，正在处理
define('operation_status_finish',                   2);             // 操作 已经处理完成



//define('response_order_is_success',                 0);             // 订单成功
//define('response_order_is_failed',                  1);             // 订单失败
//define('response_order_is_not_existing',            2);             // 订单不存在
//define('response_order_unknow_status',              3);             // 订单未知状态
