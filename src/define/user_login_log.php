<?php
/**
 * Created by PhpStorm.
 * User: tiantian
 * Date: 2018/12/5
 * Time: 15:15
 */
define('user_login_log_success',                                  0);         // 登录成功
define('user_login_log_account_or_pwd_error',                     1);         // 账号/密码错误（包括被冻结等）
define('user_login_log_authcode_error',                           2);         // 验证码错误
define('user_login_log_frozen',                                   3);         // 验证码错误
define('user_login_log_type',array(
    [
        'title' =>  '全部',
        'value' =>  ''
    ],
    [
        'title' =>  '登录成功',
        'value' =>  user_login_log_success
    ],
    [
        'title' =>  '账号/密码错误',
        'value' =>  user_login_log_account_or_pwd_error
    ],
    [
        'title' =>  '验证码错误',
        'value' =>  user_login_log_authcode_error
    ],
    [
        'title' =>  '账号冻结',
        'value' =>  user_login_log_frozen
    ],
));