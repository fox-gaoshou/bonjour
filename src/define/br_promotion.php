<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/11
 * Time: 11:24
 */

define('promotion_award_type_member_register',              1);     // 会员注册 推荐人 增加佣金
define('promotion_award_type_member_firstDeposit',          2);     // 会员首充 推荐人 增加佣金
define('promotion_award_type_member_deposit',               3);     // 会员充值 推荐人 增加佣金
define('promotion_award_type_member_withdrawal',            4);     // 会员提款 推荐人 增加佣金
define('promotion_award_type_member_firstWithdrawal',       5);     // 会员首次提款 推荐人增加佣金
define('promotion_award_type_member_bet',                   6);     // 会员投注 推荐人 增加佣金
define('promotion_award_type',array(
    [
        'title' => '会员注册',
        'value' => promotion_award_type_member_register
    ],
    [
        'title' => '会员首充',
        'value' => promotion_award_type_member_firstDeposit
    ],
    [
        'title' => '会员充值',
        'value' => promotion_award_type_member_deposit
    ],
    [
        'title' => '会员提款',
        'value' => promotion_award_type_member_withdrawal
    ],
    [
        'title' => '会员投注',
        'value' => promotion_award_type_member_bet
    ]
));
