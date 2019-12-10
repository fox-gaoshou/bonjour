<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/6/20
 * Time: 13:39
 */

return [
    // 注册机
    'register' => [
        // 通信协议，注册机与网关之间的通信协议 register 必须是text协议
        'protocol' =>               'text',
        // 监听网段
        'listen' =>                 '0.0.0.0',
        // 监听端口
        'port' =>                   1238,
        // 局域网地址
        'local_net_address' =>      '192.168.17.243',
        // 公网地址，如果网关不是局域通信的时候，需要公网地址
        'public_net_address' =>     'xxx.xxx.xx.xxx',
    ],
    // 网关
    'gateway' => [
        // 0 使用本机连接，当网关与注册机是同一台服务器
        // 1 使用局域连接，当网关与注册机是同一个局域网
        // 2 使用远程连接，不介绍了
        'net_model' =>          1,
        // 通信协议，客服端与网关之间的通信协议
        'protocol' =>           'websocket',
        // 监听网段
        'listen' =>             '0.0.0.0',
        // 监听端口
        'port' =>               8282,
        'process_name' =>       'gateway_worker',
        'process_max' =>        4,
    ],
    // 业务处理机
    'business' => [
        // 0 使用本机连接，当网关与注册机是同一台服务器
        // 1 使用局域连接，当网关与注册机是同一个局域网
        // 2 使用远程连接，不介绍了
        'net_model' =>          0,
        // 进程名称
        'process_name' =>       'business_worker',
        // 进程数量
        'process_max' =>        4,
        // 事件处理器
        'event_handler' =>      'Events'
    ]
];