<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/6
 * Time: 14:39
 */

// 全局错误，唯一识别码

// 没有任何错误
define('br_errno_nothing',                                      0);
// 一般错误
// 一般错误的情况下，可以由控制层自行描述
// 一般情况下，后端都是返回一般错误给前端，然后加上描述，下面的错误代码，一般用于后台标识记录
// 除非特殊情况下，需要直接返回错误代码给前端进行相应的处理
define('br_errno_normal_error',                                 1);
// 未处理的错误
define('br_errno_unknow_error',                                 256);
// 执行sql错误
define('br_errno_sql_query_error',                              257);
// 请求过于频繁
define('br_errno_too_often',                                    258);
// http请求错误
define('br_errno_http_request_error',                           259);


define('br_errno_login_module',                                 1000);
// 登陆成功
define('br_errno_login_success',                                0);
// 账号或密码错误
define('br_errno_login_username_or_password_error',             br_errno_login_module+1);
// 账号已经被冻结
define('br_errno_login_account_is_frozen',                      br_errno_login_module+2);
// 验证码已经失效
define('br_errno_login_auth_code_timeout',                      br_errno_login_module+3);
// 验证码输入错误
define('br_errno_login_auth_code_error',                        br_errno_login_module+4);
// 输入的账号格式错误
define('br_errno_login_username_format_error',                  br_errno_login_module+5);
// 输入的密码格式错误
define('br_errno_login_password_format_error',                  br_errno_login_module+6);

// 会话模块
define('br_errno_session_module',                               2000);
// 会话超时
// 登录超时，多个不同的会话登录一个账号的时候，只有最后登录的有效，其他的都返回这个错误!
define('br_errno_session_timeout',                              br_errno_session_module+1);
// 会话验证错误
define('br_errno_session_auth_failed',                          br_errno_session_module+2);

// 操作模块
define('br_errno_operation_module',                             3000);
// 其他错误
// 当程序返回 other_error 进程，不再继续请求，记录错误的相关信息
// 等待人工处理
define('br_errno_operation_other_error',                        br_errno_operation_module+1);
// 数据出现异常情况
// 当程序返回 data_exception 可能是 guid 操作记录 与用户的 guid 操作分配 出现数据不一致
// 获取是用户恶意，发起操作
define('br_errno_operation_data_exception',                     br_errno_operation_module+2);
// 操作已经过期
// 可能是前端没有收到服务器的返回信息，实际服务器已经完成了此操作
// 或者是一个较早起的操作，恶意请求服务器
define('br_errno_operation_done_already',                       br_errno_operation_module+3);
// 当前端，发起一个需要带有GUID的操作，后台，会匹配相应的操作，是否当前的GUID，如果不匹配，提示前端，操作GUID，不匹配
// 一般出现这个情况是因为，前端与后端状态不同步
define('br_errno_operation_guid_not_match',                     br_errno_operation_module+4);
// 操作已经重复提交
define('br_errno_operation_guid_repeat_submit',                 br_errno_operation_module+5);

// 前一个操作正在运行当中
define('br_errno_operation_previous_guid_is_operating',         br_errno_operation_module+6);

// 操作 抢占任务失败，没有抢占成功，不需要返回给前端
// 无需要存储在数据库
define('br_errno_operation_can_not_occupy',                     br_errno_operation_module+10);
// 操作，远程响应订单成功
define('br_errno_operation_response_order_is_success',          br_errno_operation_module+11);
// 操作，远程响应订单失败
define('br_errno_operation_response_order_is_failed',           br_errno_operation_module+12);
// 操作，远程响应订单不存在
define('br_errno_operation_response_order_is_not_existing',     br_errno_operation_module+13);
// 操作，远程响应订单状态未知
define('br_errno_operation_response_order_unknown_status',      br_errno_operation_module+14);
// 操作，远程响应订单状态操作中
define("br_errno_operation_response_order_is_handling",         br_errno_operation_module+15);

// 操作，远程响应的数据格式错误
define('br_errno_operation_response_data_format_error',         br_errno_operation_module+20);
// 操作，远程响应数据ok
define('br_errno_operation_response_data_ok',                   br_errno_operation_module+21);
// 操作，远程响应数据ok，但是为空
define('br_errno_operation_response_data_ok_empty',             br_errno_operation_module+22);


