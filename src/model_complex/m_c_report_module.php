<?php


namespace bonjour\model_complex;


use bonjour\core\bonjour;
use bonjour\model\bonjour\bonjour_user;
use bonjour\model\bonjour\bonjour_user_money;


class m_c_report_module
{
    public function gen_general_day_report(int $Ymd)
    {
        $res = bonjour::$container->get(report_module_general_day_report::class)->insert_where_not_exists($Ymd);
        if($res->code) return $res;

        // 会员数量
        $res = bonjour::$container->get(bonjour_user::class)->count();
        if($res->code) return $res;
        $number_of_members = $res->data;

        // 当天会员的注册数量
        $s_time = sprintf("%u 00:00:00",$Ymd);
        $e_time = sprintf("%u 23:59:59",$Ymd);
        $res = bonjour::$container->get(bonjour_user::class)->count([['addtime','between',$s_time,$e_time,'i']]);
        if($res->code) return $res;
        $number_of_new_members = $res->data;

        // 所有会员的总余额
        // 所有会员的总冻结资金
        $res = bonjour::$container->get(bonjour_user_money::class)->select_all('coalesce(sum(`balance`),0) as `balance`,coalesce(sum(`frozen_amount`),0) as `frozen_amount`');
        if($res->code) return $res;
        $temp = $res->qry->fetch_assoc();
        $amount_of_all_members_balance = $temp['balance'];
        $amount_of_all_members_frozen = $temp['frozen_amount'];

        // 首次充值会员数量
        // 首次充值会员的总额
        $where = array(
            ['time_of_first_success_ti','between',strtotime($s_time),strtotime($e_time),'i']
        );
        $res = bonjour::$mysql->select('count(*) as `count`,coalesce(sum(`amount_of_first_success_ti`),0) as `amount`')->from(m_bank_user::$dt)->where($where)->query();
        if($res->code) return $res;
        $temp = $res->qry->fetch_assoc();
        $number_of_members_first_deposit = $temp['count'];
        $amount_of_members_first_deposit = $temp['amount'];

        // 首次提款会员数量
        // 首次提款会员的总额
        $where = array(
            ['time_of_first_success_to','between',strtotime($s_time),strtotime($e_time),'i']
        );
        $res = bonjour::$mysql->select('count(*) as `count`,coalesce(sum(`amount_of_first_success_to`),0) as `amount`')->from(m_bank_user::$dt)->where($where)->query();
        if($res->code) return $res;
        $temp = $res->qry->fetch_assoc();
        $number_of_members_first_withdrawal = $temp['count'];
        $amount_of_members_first_withdrawal = $temp['amount'];

        // 充值的会员数量
        $where = array(
            ['']
        );
        $res = bonjour::$mysql->select()->from(m_bank_ti_order::$dt)->where()->query();
        if($res->code) return $res;
        $temp = $res->qry->fetch_assoc();
    }
}