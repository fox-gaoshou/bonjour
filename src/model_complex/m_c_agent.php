<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2018/12/31
 * Time: 19:00
 */

namespace bonjour\model_complex;


use bonjour\core\bonjour;
use bonjour\format\res\res;


class m_c_agent
{
    public function stats_all_member(&$tree,int $chief_id,int $s_ymd,int $e_ymd,int $s_time,int $e_time)
    {
        $sql = sprintf("
            select
            '全部' as `user_level`,
            coalesce(count(*),0) as `number_of_members`,
            coalesce(count(case when br_user.`reg_time` >= %u and br_user.`reg_time` <= %u then 1 else null end),0) as `number_of_new_members`
            from
            nexus_module.user_nexus_tree as pn_unt
            left join member_module.user as br_user
            on br_user.id = pn_unt.user_id
            where 
            pn_unt.chief_id = %u
        ",$s_time,$e_time,$chief_id);
        $qry = bonjour::$mysql->ins->query($sql);
        if(bonjour::$mysql->ins->errno)
        {
            return new res(1,'执行发生错误!',bonjour::$mysql->ins->error);
        }
        $tree = array_merge($tree,$qry->fetch_assoc());

        return new res();
    }
    public function stats_all_member_group_by_user_level(&$tree,int $chief_id,int $s_ymd,int $e_ymd,int $s_time,int $e_time)
    {
        $sql = sprintf("
            select
            br_user.id as `id`,
            pn_unt.user_level as `user_level`,
            coalesce(count(*),0) as `number_of_members`,
            coalesce(count(case when br_user.`reg_time` >= %u and br_user.`reg_time` <= %u then 1 else null end),0) as `number_of_new_members`
            from
            nexus_module.user_nexus_tree as pn_unt
            left join member_module.user as br_user
            on br_user.id = pn_unt.user_id
            where 
            pn_unt.chief_id = %u
            group by pn_unt.user_level
        ",$s_time,$e_time,$chief_id);
        $qry = bonjour::$mysql->ins->query($sql);
        if(bonjour::$mysql->ins->errno)
        {
            return new res(1,'执行发生错误!',bonjour::$mysql->ins->error);
        }
        foreach ($qry as $row) $tree[$row['user_level']] = $row;
        return new res();
    }

    public function stats_all_member_bank_data(&$tree,int $chief_id,int $s_ymd,int $e_ymd,int $s_time,int $e_time)
    {
        // 所有会员-首充人数
        $all_member_first_deposit_people_counter = 0;
        // 所有会员-首充总额
        $all_member_first_deposit_amount = 0;

        // 所有会员-充值人数
        $all_member_deposit_people_counter = 0;
        // 所有会员-充值订单数
        $all_member_deposit_order_counter = 0;
        // 所有会员-充值总额
        $all_member_deposit_amount = 0;

        // 所有会员-首提人数
        $all_member_first_withdrawal_people_counter = 0;
        // 所有会员-首提总额
        $all_member_first_withdrawal_amount = 0;
        // 所有会员-提款人数
        $all_member_withdrawal_people_counter = 0;
        // 所有会员-提款订单数量
        $all_member_withdrawal_order_counter = 0;
        // 所有会员-提款总额
        $all_member_withdrawal_amount = 0;

        // 新会员-首充人数
        $new_member_first_deposit_people_counter = 0;
        // 新会员-首充总额
        $new_member_first_deposit_amount = 0;
        // 新会员-充值人数
        $new_member_deposit_people_counter = 0;
        // 新会员-充值订单数
        $new_member_deposit_order_counter = 0;
        // 新会员-充值总额
        $new_member_deposit_amount = 0;

        // 新会员-首提人数
        $new_member_first_withdrawal_people_counter = 0;
        // 新会员-首提总额
        $new_member_first_withdrawal_amount = 0;
        // 新会员-提款人数
        $new_member_withdrawal_people_counter = 0;
        // 新会员-提款订单数
        $new_member_withdrawal_order_counter = 0;
        // 新会员-提款总额
        $new_member_withdrawal_amount = 0;

        $sql = sprintf("
            select
            coalesce(bank_user.`amount_of_first_success_ti`,0)               as `first_ti_amount`,
            coalesce(bank_user.`amount_of_first_success_to`,0)               as `first_to_amount`,
            coalesce(bank_user.`time_of_first_success_ti`,0)                 as `first_ti_time`,
            coalesce(bank_user.`time_of_first_success_to`,0)                 as `first_to_time`,
            coalesce(sum(bank_user_day_report.`amount_of_success_ti`),0)     as `ti_amount`,
            coalesce(sum(bank_user_day_report.`amount_of_success_to`),0)     as `to_amount`,
            coalesce(sum(bank_user_day_report.`number_of_success_ti`),0)     as `ti_order_counter`,
            coalesce(sum(bank_user_day_report.`number_of_success_to`),0)     as `to_order_counter`,
            br_user.reg_time as `reg_time`
            from
            nexus_module.user_nexus_tree as pn_unt
            left join member_module.user as br_user
            on br_user.id = pn_unt.user_id
            left join bank_module.user as bank_user
            on bank_user.id = pn_unt.user_id
            left join bank_module.user_day_report as bank_user_day_report
            on bank_user_day_report.user_id = pn_unt.user_id
            where
            pn_unt.chief_id = %u and bank_user_day_report.Ymd between %u and %u
            group by bank_user_day_report.user_id
        ",$chief_id,$s_ymd,$e_ymd);
        $qry = bonjour::$mysql->ins->query($sql);
        if(bonjour::$mysql->ins->errno)
        {
            return new res(1,'执行发生错误!',bonjour::$mysql->ins->error);
        }
        foreach ($qry as $row)
        {
            // 所有会员-首充人数
            // 所有会员-首充总额
            if(($row['first_ti_time'] >= $s_time) && ($row['first_ti_time'] <= $e_time))
            {
                $all_member_first_deposit_people_counter++;
                $all_member_first_deposit_amount += (float)$row['first_ti_amount'];
            }
            // 所有会员-充值人数
            // 所有会员-充值订单数
            // 所有会员-充值总额
            if($row['ti_order_counter'] > 0)
            {
                $all_member_deposit_people_counter++;
                $all_member_deposit_order_counter += (int)$row['ti_order_counter'];
                $all_member_deposit_amount += (float)$row['ti_amount'];
            }

            // 所有会员-首提人数
            // 所有会员-首提总额
            if(($row['first_to_time'] >= $s_time) && ($row['first_to_time'] <= $e_time))
            {
                $all_member_first_withdrawal_people_counter++;
                $all_member_first_withdrawal_amount += (float)$row['first_to_amount'];
            }
            // 所有会员-提款人数
            // 所有会员-提款订单数
            // 所有会员-提款总额
            if($row['to_order_counter'] > 0)
            {
                $all_member_withdrawal_people_counter++;
                $all_member_withdrawal_order_counter += (int)$row['to_order_counter'];
                $all_member_withdrawal_amount += (float)$row['to_amount'];
            }

            if(($row['reg_time'] >= $s_time) && ($row['reg_time']) <= $e_time)
            {
                // 新会员-首充人数
                // 新会员-首充总额
                if(($row['first_ti_time'] >= $s_time) && ($row['first_ti_time'] <= $e_time))
                {
                    $new_member_first_deposit_people_counter++;
                    $new_member_first_deposit_amount += (float)$row['first_ti_amount'];
                }
                // 新会员-充值人数
                // 新会员-充值订单数
                // 新会员-充值总额
                if($row['ti_order_counter'] > 0)
                {
                    $new_member_deposit_people_counter++;
                    $new_member_deposit_order_counter += (int)$row['ti_order_counter'];
                    $new_member_deposit_amount += (float)$row['ti_amount'];
                }

                // 新会员-首提人数
                // 新会员-首提总额
                if(($row['first_to_time'] >= $s_time) && ($row['first_to_time'] <= $e_time))
                {
                    $new_member_first_withdrawal_people_counter++;
                    $new_member_first_withdrawal_amount += (float)$row['first_to_amount'];
                }
                // 新会员-提款人数
                // 新会员-提款订单数
                // 新会员-提款总额
                if($row['to_order_counter'] > 0)
                {
                    $new_member_withdrawal_people_counter++;
                    $new_member_withdrawal_order_counter += (int)$row['to_order_counter'];
                    $new_member_withdrawal_amount += (float)$row['to_amount'];
                }
            }
        }

        $tree = array_merge($tree,array(
            'all_member_first_deposit_people_counter' => $all_member_first_deposit_people_counter,
            'all_member_first_deposit_amount' => $all_member_first_deposit_amount,
            'all_member_deposit_people_counter' => $all_member_deposit_people_counter,
            'all_member_deposit_order_counter' => $all_member_deposit_order_counter,
            'all_member_deposit_amount' => $all_member_deposit_amount,
            'all_member_first_withdrawal_people_counter' => $all_member_first_withdrawal_people_counter,
            'all_member_first_withdrawal_amount' => $all_member_first_withdrawal_amount,
            'all_member_withdrawal_people_counter' => $all_member_withdrawal_people_counter,
            'all_member_withdrawal_order_counter' => $all_member_withdrawal_order_counter,
            'all_member_withdrawal_amount' => $all_member_withdrawal_amount,
            'new_member_first_deposit_people_counter' => $new_member_first_deposit_people_counter,
            'new_member_first_deposit_amount' => $new_member_first_deposit_amount,
            'new_member_deposit_people_counter' => $new_member_deposit_people_counter,
            'new_member_deposit_order_counter' => $new_member_deposit_order_counter,
            'new_member_deposit_amount' => $new_member_deposit_amount,
            'new_member_first_withdrawal_people_counter' => $new_member_first_withdrawal_people_counter,
            'new_member_first_withdrawal_amount' => $new_member_first_withdrawal_amount,
            'new_member_withdrawal_people_counter' => $new_member_withdrawal_people_counter,
            'new_member_withdrawal_order_counter' => $new_member_withdrawal_order_counter,
            'new_member_withdrawal_amount' => $new_member_withdrawal_amount,
        ));
        return new res();
    }
    public function stats_all_member_bank_data_group_by_user_level(&$tree,int $chief_id,int $s_ymd,int $e_ymd,int $s_time,int $e_time)
    {
        $sql = sprintf("
            select
            coalesce(bank_user.`amount_of_first_success_ti`,0)              as `first_ti_amount`,
            coalesce(bank_user.`amount_of_first_success_to`,0)              as `first_to_amount`,
            coalesce(bank_user.`time_of_first_success_ti`,0)                as `first_ti_time`,
            coalesce(bank_user.`time_of_first_success_to`,0)                as `first_to_time`,
            coalesce(sum(bank_user_day_report.`amount_of_success_ti`),0)    as `ti_amount`,
            coalesce(sum(bank_user_day_report.`amount_of_success_to`),0)    as `to_amount`,
            coalesce(sum(bank_user_day_report.`number_of_success_ti`),0)    as `ti_order_counter`,
            coalesce(sum(bank_user_day_report.`number_of_success_to`),0)    as `to_order_counter`,
            br_user.reg_time                                                as `reg_time`,
            pn_unt.user_level                                               as `user_level`
            from
            nexus_module.user_nexus_tree as pn_unt
            left join member_module.user as br_user
            on br_user.id = pn_unt.user_id
            left join bank_module.user as bank_user
            on bank_user.id = pn_unt.user_id
            left join bank_module.user_day_report as bank_user_day_report
            on bank_user_day_report.user_id = pn_unt.user_id
            where
            pn_unt.chief_id = %u and bank_user_day_report.Ymd between %u and %u
            group by bank_user_day_report.user_id
        ",$chief_id,$s_ymd,$e_ymd);
        $qry = bonjour::$mysql->ins->query($sql);
        if(bonjour::$mysql->ins->errno)
        {
            return new res(1,'执行发生错误!',bonjour::$mysql->ins->error);
        }
        foreach ($tree as $index => $row)
        {
            $tree[$index] = array_merge($tree[$index],array(
                'all_member_first_deposit_people_counter' => 0,
                'all_member_first_deposit_amount' => 0,
                'all_member_deposit_people_counter' => 0,
                'all_member_deposit_order_counter' => 0,
                'all_member_deposit_amount' => 0,
                'all_member_first_withdrawal_people_counter' => 0,
                'all_member_first_withdrawal_amount' => 0,
                'all_member_withdrawal_people_counter' => 0,
                'all_member_withdrawal_order_counter' => 0,
                'all_member_withdrawal_amount' => 0,
                'new_member_first_deposit_people_counter' => 0,
                'new_member_first_deposit_amount' => 0,
                'new_member_deposit_people_counter' => 0,
                'new_member_deposit_order_counter' => 0,
                'new_member_deposit_amount' => 0,
                'new_member_first_withdrawal_people_counter' => 0,
                'new_member_first_withdrawal_amount' => 0,
                'new_member_withdrawal_people_counter' => 0,
                'new_member_withdrawal_order_counter' => 0,
                'new_member_withdrawal_amount' => 0,
            ));
        }

        foreach ($qry as $row)
        {
            $user_level = $row['user_level'];

            // 所有会员-首充人数
            // 所有会员-首充总额
            if(($row['first_ti_time'] >= $s_time) && ($row['first_ti_time'] <= $e_time))
            {
                $tree[$user_level]['all_member_first_deposit_people_counter']++;
                $tree[$user_level]['all_member_first_deposit_amount'] += (float)$row['first_ti_amount'];
            }
            // 所有会员-充值人数
            // 所有会员-充值订单数
            // 所有会员-充值总额
            if($row['ti_order_counter'] > 0)
            {
                $tree[$user_level]['all_member_deposit_people_counter']++;
                $tree[$user_level]['all_member_deposit_order_counter'] += (float)$row['ti_order_counter'];
                $tree[$user_level]['all_member_deposit_amount'] += (float)$row['ti_amount'];
            }

            // 所有会员-首提人数
            // 所有会员-首提总额
            if(($row['first_to_time'] >= $s_time) && ($row['first_to_time'] <= $e_time))
            {
                $tree[$user_level]['all_member_first_withdrawal_people_counter']++;
                $tree[$user_level]['all_member_first_withdrawal_amount'] += (float)$row['first_to_amount'];
            }
            // 所有会员-提款人数
            // 所有会员-提款订单数
            // 所有会员-提款总额
            if($row['to_order_counter'] > 0)
            {
                $tree[$user_level]['all_member_withdrawal_people_counter']++;
                $tree[$user_level]['all_member_withdrawal_order_counter'] += (float)$row['to_order_counter'];
                $tree[$user_level]['all_member_withdrawal_amount'] += (float)$row['to_amount'];
            }

            if(($row['reg_time'] >= $s_time) && ($row['reg_time']) <= $e_time)
            {
                // 新会员-首充人数
                // 新会员-首充总额
                if(($row['first_ti_time'] >= $s_time) && ($row['first_ti_time'] <= $e_time))
                {
                    $tree[$user_level]['new_member_first_deposit_people_counter']++;
                    $tree[$user_level]['new_member_first_deposit_amount'] += (float)$row['first_ti_amount'];
                }
                // 新会员-充值人数
                // 新会员-充值订单数
                // 新会员-充值总额
                if($row['ti_order_counter'] > 0)
                {
                    $tree[$user_level]['new_member_deposit_people_counter']++;
                    $tree[$user_level]['new_member_deposit_order_counter'] += (float)$row['ti_order_counter'];
                    $tree[$user_level]['new_member_deposit_amount'] += (float)$row['ti_amount'];
                }

                // 新会员-首提人数
                // 新会员-首提总额
                if(($row['first_to_time'] >= $s_time) && ($row['first_to_time'] <= $e_time))
                {
                    $tree[$user_level]['new_member_first_withdrawal_people_counter']++;
                    $tree[$user_level]['new_member_first_withdrawal_amount'] += (float)$row['first_to_amount'];
                }
                // 新会员-提款人数
                // 新会员-提款订单数
                // 新会员-提款总额
                if($row['to_order_counter'] > 0)
                {
                    $tree[$user_level]['new_member_withdrawal_people_counter']++;
                    $tree[$user_level]['new_member_withdrawal_order_counter'] += (float)$row['to_order_counter'];
                    $tree[$user_level]['new_member_withdrawal_amount'] += (float)$row['to_amount'];
                }
            }
        }
        return new res();
    }

    public function stats_all_member_game_data(&$tree,int $chief_id,int $s_ymd,int $e_ymd,int $s_time,int $e_time)
    {
        $all_member_game_people_counter =  0;
        $all_member_game_bet =      0;
        $all_member_game_bonus =    0;
        $all_member_game_profit =   0;

        $new_member_game_people_counter =  0;
        $new_member_game_bet =      0;
        $new_member_game_bonus =    0;
        $new_member_game_profit =   0;

        $sql = sprintf("
            select
            coalesce(sum(player_game_record_day_stats.bet),0)       as `bet`,
            coalesce(sum(player_game_record_day_stats.profit),0)    as `profit`,
            coalesce(sum(player_game_record_day_stats.revenue),0)   as `revenue`,
            br_user.reg_time                                        as `reg_time`
            from
            nexus_module.user_nexus_tree as pn_unt
            left join member_module.user as br_user
            on br_user.id = pn_unt.user_id
            left join game_module_poker_chess.player_game_record_day_stats as player_game_record_day_stats
            on player_game_record_day_stats.player_id = pn_unt.user_id
            where 
            pn_unt.chief_id = %u and player_game_record_day_stats.Ymd between %u and %u
            group by player_game_record_day_stats.player_id
        ",$chief_id,$s_ymd,$e_ymd);

        $qry = bonjour::$mysql->ins->query($sql);
        if(bonjour::$mysql->ins->errno)
        {
            return new res(1,'执行发生错误!',bonjour::$mysql->ins->error);
        }

        foreach ($qry as $row)
        {
            $user_reg_time = $row['reg_time'];

            $all_member_game_people_counter++;
            $all_member_game_bet +=         (float)$row['bet'];
//            $all_member_game_bonus +=       (float)$row['revenue'];
            $all_member_game_profit +=      (float)$row['profit'];

            if(($user_reg_time >= $s_time) && ($user_reg_time <= $e_time))
            {
                $new_member_game_people_counter++;
                $new_member_game_bet +=         (float)$row['bet'];
//                $new_member_game_bonus +=       (float)$row['bonus'];
                $new_member_game_profit +=      (float)$row['profit'];
            }
        }

        $tree = array_merge($tree,array(
            'all_member_game_people_counter' =>     $all_member_game_people_counter,
            'all_member_game_bet' =>                $all_member_game_bet,
            'all_member_game_bonus' =>              $all_member_game_bonus,
            'all_member_game_profit' =>             $all_member_game_profit,
            'new_member_game_people_counter' =>     $new_member_game_people_counter,
            'new_member_game_bet' =>                $new_member_game_bet,
            'new_member_game_bonus' =>              $new_member_game_bonus,
            'new_member_game_profit' =>             $new_member_game_profit,
        ));

        return new res();
    }
    public function stats_all_member_game_data_group_user_level(&$tree,int $chief_id,int $s_ymd,int $e_ymd,int $s_time,int $e_time)
    {
        foreach ($tree as $index => $row)
        {
            $tree[$index] = array_merge($tree[$index],array(
                'all_member_game_people_counter' => 0,
                'all_member_game_bet' => 0,
                'all_member_game_bonus' => 0,
                'all_member_game_profit' => 0,
                'new_member_game_people_counter' => 0,
                'new_member_game_bet' => 0,
                'new_member_game_bonus' => 0,
                'new_member_game_profit' => 0,
            ));
        }

        $sql = sprintf("
            select
            coalesce(sum(player_game_record_day_stats.bet),0)       as `bet`,
            coalesce(sum(player_game_record_day_stats.profit),0)    as `profit`,
            br_user.reg_time                                        as `reg_time`,
            pn_unt.user_level                                       as `user_level`
            from
            nexus_module.user_nexus_tree as pn_unt
            left join member_module.user as br_user
            on br_user.id = pn_unt.user_id
            left join game_module_poker_chess.player_game_record_day_stats as player_game_record_day_stats
            on player_game_record_day_stats.player_id = pn_unt.user_id
            where 
            pn_unt.chief_id = %u and player_game_record_day_stats.Ymd between %u and %u
            group by player_game_record_day_stats.player_id
        ",$chief_id,$s_ymd,$e_ymd);
        $qry = bonjour::$mysql->ins->query($sql);
        if(bonjour::$mysql->ins->errno)
        {
            return new res(1,'执行发生错误!',bonjour::$mysql->ins->error);
        }

        foreach ($qry as $row)
        {
            $user_level = $row['user_level'];
            $user_reg_time = $row['reg_time'];

            $tree[$user_level]['all_member_game_people_counter']++;
            $tree[$user_level]['all_member_game_bet'] += (float)$row['bet'];
//            $tree[$user_level]['all_member_game_bonus'] += (float)$row['bonus'];
            $tree[$user_level]['all_member_game_profit'] += (float)$row['profit'];

            if(($user_reg_time >= $s_time) && ($user_reg_time <= $e_time))
            {
                $tree[$user_level]['new_member_game_people_counter']++;
                $tree[$user_level]['new_member_game_bet'] += (float)$row['bet'];
//                $tree[$user_level]['new_member_game_bonus'] += (float)$row['bonus'];
                $tree[$user_level]['new_member_game_profit'] += (float)$row['profit'];
            }
        }

        return new res();
    }
}