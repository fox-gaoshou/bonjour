<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/7/2
 * Time: 14:37
 */


// common
define('common_module_cache_response_failed',array(
    'obj' =>        'common',
    'cmd' =>        'err_handler',
    'errno' =>      1,
    'error' =>      '服务器缓存相应失败!',
    'data' =>       ''
));

// session module
// 可能又其他设置登录并连接websocket，导致当前的weboskcet关闭
define('session_module_session_be_occupy',array(
    'obj' =>        'session',
    'cmd' =>        'err_handler',
    'errno' =>      2,
    'error' =>      '用户会话被其他设备占用!',
    'data' =>       ''
));
// 在进行会话验证的时候，token是无效的
define('session_module_token_is_invalid',array(
    'obj' =>        'session',
    'cmd' =>        'err_handler',
    'errno' =>      3,
    'error' =>      '令牌错误',
    'data' =>       ''
));
// 在进行会话验证的时候，会话ID是无效的
define('session_module_session_id_is_invalid',array(
    'obj' =>        'session',
    'cmd' =>        'err_handler',
    'errno' =>      4,
    'error' =>      '会话ID已经失效',
    'data' =>       ''
));