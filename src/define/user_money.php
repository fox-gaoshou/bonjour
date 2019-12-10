<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/14
 * Time: 21:26
 */

define('user_money_details_deposit',                            1);         // 用户 发起操作 充值
define('user_money_details_deposit_on_manager',                 2);         // 管理员 发起操作 给用户上分
define('user_money_details_withdraw',                           3);         // 用户 发起操作 提款
define('user_money_details_withdraw_on_manager',                4);         // 管理员 发起操作 给用户下分
define('user_money_details_frozen_on_manager',                  5);         // 管理员 发起操作 给用户冻结金额
define('user_money_details_unfrozen_on_manager',                6);         // 管理员 发起操作 给用户接触冻结金额
define("user_money_details_to_send_back",                       7);         // 提款的金额，反还
define('user_money_details_from_promotion',                     8);         // 从推广钱包转入
define('user_money_details_transfer_to_third_platform',         9);         // 转出金币到平台

define('user_money_details_to_third_platform',                  10);        // 转出到三方平台
define('user_money_details_ti_third_platform',                  11);        // 从三方平台转入
define('user_money_details_to_third_platform_failed_send_back', 12);        // 转出到三方平台失败，金币返还

define('user_money_details_activity_sign_card',                 201);       // 活动模块-签到活动
define('user_money_details_activity_luck_wheel',                202);       // 活动模块-幸运轮盘
define('user_money_details_activity_rakeback',                  203);       // 活动模块-返水

define('user_money_details_type',array(
    [
        'title' => '全部',
        'value' => ''
    ],
    [
        'title' => '会员充值',
        'value' => user_money_details_deposit
    ],
    [
        'title' => '管理员充值',
        'value' => user_money_details_deposit_on_manager
    ],
    [
        'title' => '会员提款',
        'value' => user_money_details_withdraw
    ],
    [
        'title' => '管理员提款',
        'value' => user_money_details_withdraw_on_manager
    ],
    [
        'title' => '管理员冻结资金',
        'value' => user_money_details_frozen_on_manager
    ],
    [
        'title' => '管理员解冻资金',
        'value' => user_money_details_unfrozen_on_manager
    ],
    [
        'title' => '反还提款金额',
        'value' => user_money_details_to_send_back
    ],
    [
        'title' => '佣金转入',
        'value' => user_money_details_from_promotion
    ],
    [
        'title' => '游戏上分',
        'value' => user_money_details_to_third_platform
    ],
    [
        'title' => '游戏下分',
        'value' => user_money_details_ti_third_platform
    ],
    [
        'title' => '上分失败退回',
        'value' => user_money_details_to_third_platform_failed_send_back
    ],
    [
        'title' => '签到奖励',
        'value' => user_money_details_activity_sign_card
    ],
    [
        'title' => '轮盘抽奖',
        'value' => user_money_details_activity_luck_wheel
    ],
));