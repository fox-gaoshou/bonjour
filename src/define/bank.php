<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/9
 * Time: 22:05
 */

define('bank_ti_order_type_normal',                 0);     // 充值订单类型，普通订单
define('bank_ti_order_type_gift',                   1);     // 充值订单类型，优惠订单

define('bank_ti_order_status_pass',                 1);     // 充值订单状态，通过
define('bank_ti_order_status_reject',               2);     // 充值订单状态，拒绝

define("bank_to_order_status_pass",                 1);     // 转出订单通过
define("bank_to_order_status_reject",               2);     // 转出订单拒绝
define("bank_to_order_status_returnMoney",          3);     // 转出订单已经反还金币给用户
define("bank_to_order_status_sentToThird",          4);     // 转出订单，发送到三方处理