<?php


namespace bonjour\obj;


use app\module\bonjour\model\file_manager;
use app\module\config\model\config_id;
use app\module\member\mc;
use app\module\member\model\user;
use app\module\nexus\model\root_nexus_tree;
use app\module\nexus\model\user_nexus_tree;
use app\module\promotion\model\commission_setting;
use app\module\promotion\model\game_classification;
use app\module\promotion\model\game_day_stats;
use app\module\promotion\model\user_day_report;
use app\module\promotion\model\user_day_report_details;
use bonjour\core\bonjour;
use bonjour\format\res\res;
use bonjour\lib\lib_decide;


class obj_commission
{
    public $player_stats_data = array();
    public $game_type_group_list = array();
    public $commission_setting = array();

    public function get_today_business_by_player(int $player_id,int $Ymd)
    {
        // ------------------------------------------------------------------------------------------------------------------------------
        // 获取所有的分类，类型
        $res = bonjour::$container->get(file_manager::class)->select_by_path_new('`id`','/返佣管理',true);
        if($res->code) return $res;
        /* @var \bonjour\format\db\bonjour\format_file_manager $dir_data */
        $dir_data = $res->qry->fetch_object();

        $res = bonjour::$container->get(file_manager::class)->select_by_parent_id('`name`',$dir_data->id);
        if($res->code) return $res;
        foreach ($res->qry as $row) $this->game_type_group_list[] = $row['name'];
        if(count($this->game_type_group_list) == 0) return new res(1,'获取返佣分类失败!');
        // ------------------------------------------------------------------------------------------------------------------------------

        // 所有分类的佣金系数
        foreach ($this->game_type_group_list as $game_type_group)
        {
            $this->commission_setting[$game_type_group] = array();

            $path = sprintf('/返佣管理/%s/返佣比例/',$game_type_group);
            $res = bonjour::$container->get(file_manager::class)->select_by_path_new('`id`',$path,true);
            if($res->code) return $res;
            $parent_id = $res->qry->fetch_assoc()['id'];
            $res = bonjour::$container->get(commission_setting::class)->select_by_parent_id('*',$parent_id);
            if($res->code) return $res;
            foreach ($res->qry as $row) $this->commission_setting[$game_type_group][] = $row;

            // 排序
            array_multisort(array_column($this->commission_setting[$game_type_group],'less_than'),SORT_ASC,$this->commission_setting[$game_type_group]);
        }
        // ------------------------------------------------------------------------------------------------------------------------------
//        $res = bonjour::$container->get(promotion_module_game_day_stats::class)->select_by_player_id_and_ymd('*',$player_id,$Ymd);
//        if($res->code) return $res;
//        foreach ($res->qry as $row)
//        {
//            $player_id = $row['player_id'];
//            $game_type_group = $row['game_type_group'];
//            if(isset($this->player_stats_data[$player_id]) == false) $this->player_stats_data[$player_id] = array();
//            $this->player_stats_data[$player_id][$game_type_group] = $row['bet'];
//        }
        $res = bonjour::$container->get(game_day_stats::class)->select_by_ymd('*',$Ymd);
        if($res->code) return $res;
        foreach ($res->qry as $row)
        {
            $p_uid = $row['player_id'];
            $game_type_group = $row['game_type_group'];
            if(isset($this->player_stats_data[$p_uid]) == false) $this->player_stats_data[$p_uid] = array();
            $this->player_stats_data[$p_uid][$game_type_group] = $row['bet'];
        }


        // 获取所有直属玩家的ID
        $user_id_list = array();
        $res = bonjour::$container->get(root_nexus_tree::class)->select_by_parent_id('user_id',$player_id);
        if($res->code) return $res;
        foreach ($res->qry as $row) $user_id_list[] = $row['user_id'];

        // 统计所有直属玩家的业绩
        // 包括直属玩家自营业绩
        // 包括直属玩家的团队业绩
        // 包括直属玩家的团队佣金系数
        $members_business_data = array();
        foreach ($user_id_list as $user_id)
        {
            $res = $this->get_business_amount($user_id);
            if($res->code) return $res;
            $members_business_data[] = $res->data;
        }

        // 初始化玩家的统计
        $players_day_report[$player_id]['stats'] = array(
            'Ymd' =>                        $Ymd,
            'user_id' =>                    $player_id,
            'business_amount_of_mine' =>    0,
            'business_amount_of_team' =>    0,
            'commission_amount' =>          0
        );

        // 遍历所有游戏类型
        foreach ($this->game_type_group_list as $game_type_group)
        {
            $temp_commission_amount =  0;
            $temp_business_amount_of_mine = 0;
            $temp_business_amount_of_team = 0;

            // 玩家的自营业绩
            $temp_business_amount_of_mine = $this->get_business_amount_by_player_id_and_game_type_group($player_id,$game_type_group);

            // 计算佣金总系数
            // 总的业绩总额：（所有直属下级会员的自营业绩+所有直属下级会员的团队业绩）
            foreach ($members_business_data as $data)
            {
                $temp_business_amount_of_team += ($data[$game_type_group]['business_amount_of_mine'] + $data[$game_type_group]['business_amount_of_team']);
            }
            $res = $this->get_commission_percent($game_type_group,$temp_business_amount_of_team);
            if($res->code) return $res;
            $temp_general_commission_percent = $res->data / 100;

            // 遍历所有的直属下级数据，计算佣金
            foreach ($members_business_data as $data)
            {
                // 代理贡献上级：总系数*代理自营业绩
                $temp = $data[$game_type_group]['business_amount_of_mine'] * $temp_general_commission_percent;
                $temp_commission_amount += $temp;

                // 代理的团队贡献上级：（总佣金系数-代理佣金系数）* 代理团队业绩
                $temp = $data[$game_type_group]['business_amount_of_team'] * ($temp_general_commission_percent - $data[$game_type_group]['commission_percent']);
                $temp_commission_amount += $temp;
            }

            // 返佣统计
            $players_day_report[$player_id]['stats']['business_amount_of_mine'] += $temp_business_amount_of_mine;
            $players_day_report[$player_id]['stats']['business_amount_of_team'] += $temp_business_amount_of_team;
            $players_day_report[$player_id]['stats']['commission_amount'] += $temp_commission_amount;
        }
        return new res(0,'',$players_day_report);
    }

    // 发放发水
    public function cal(int $Ymd)
    {
        // 所有玩家的ID列表
        $player_id_list = array();
        // 所有玩家的账单数据
        $players_day_report = array();
        // ------------------------------------------------------------------------------------------------------------------------------
        // 获取所有的分类，类型
        $res = bonjour::$container->get(file_manager::class)->select_by_path_new('`id`','/返佣管理',true);
        if($res->code) return $res;
        /* @var \bonjour\format\db\bonjour\format_file_manager $dir_data */
        $dir_data = $res->qry->fetch_object();

        $res = bonjour::$container->get(file_manager::class)->select_by_parent_id('`name`',$dir_data->id);
        if($res->code) return $res;
        foreach ($res->qry as $row) $this->game_type_group_list[] = $row['name'];
        if(count($this->game_type_group_list) == 0) return new res(1,'获取返佣分类失败!');
        // ------------------------------------------------------------------------------------------------------------------------------
        // 所有分类的佣金系数
        foreach ($this->game_type_group_list as $game_type_group)
        {
            $this->commission_setting[$game_type_group] = array();

            $path = sprintf('/返佣管理/%s/返佣比例/',$game_type_group);
            $res = bonjour::$container->get(file_manager::class)->select_by_path_new('`id`',$path,true);
            if($res->code) return $res;
            $parent_id = $res->qry->fetch_assoc()['id'];
            $res = bonjour::$container->get(commission_setting::class)->select_by_parent_id('*',$parent_id);
            if($res->code) return $res;
            foreach ($res->qry as $row) $this->commission_setting[$game_type_group][] = $row;

            // 排序
            array_multisort(array_column($this->commission_setting[$game_type_group],'less_than'),SORT_ASC,$this->commission_setting[$game_type_group]);
        }
        // ------------------------------------------------------------------------------------------------------------------------------
        // 获取当天所有统计数据
        // 并按玩家ID分组保存
        $res = $this->cache_game_stats_of_all_players($Ymd);
        if($res->code) return $res;
        // ------------------------------------------------------------------------------------------------------------------------------
        // 获取所有玩家的ID
        $res = bonjour::$container->get(user::class)->select_all('`id`');
        if($res->code) return $res;
        foreach ($res->qry as $row) $player_id_list[] = $row['id'];
        // ------------------------------------------------------------------------------------------------------------------------------
        // 遍历所有玩家ID
        foreach ($player_id_list as $player_id)
        {
            // 获取所有直属玩家的ID
            $user_id_list = array();
            $res = bonjour::$container->get(root_nexus_tree::class)->select_by_parent_id('user_id',$player_id);
            if($res->code) return $res;
            foreach ($res->qry as $row) $user_id_list[] = $row['user_id'];

            // 统计所有直属玩家的业绩
            // 包括直属玩家自营业绩
            // 包括直属玩家的团队业绩
            // 包括直属玩家的团队佣金系数
            $members_business_data = array();
            foreach ($user_id_list as $user_id)
            {
                $res = $this-> get_business_amount($user_id);
                if($res->code) return $res;
                $members_business_data[] = $res->data;
            }

            // 初始化玩家的统计
            $players_day_report[$player_id]['stats'] = array(
                'Ymd' =>                        $Ymd,
                'user_id' =>                    $player_id,
                'business_amount_of_mine' =>    0,
                'business_amount_of_team' =>    0,
                'commission_amount' =>          0
            );
            $players_day_report[$player_id]['details'] = array();

            // 遍历所有游戏类型
            foreach ($this->game_type_group_list as $game_type_group)
            {
                $temp_commission_amount =  0;
                $temp_business_amount_of_mine = 0;
                $temp_business_amount_of_team = 0;

                // 玩家的自营业绩
                $temp_business_amount_of_mine = $this->get_business_amount_by_player_id_and_game_type_group($player_id,$game_type_group);

                // 计算佣金总系数
                // 总的业绩总额：（所有直属下级会员的自营业绩+所有直属下级会员的团队业绩）
                foreach ($members_business_data as $data)
                {
                    $temp_business_amount_of_team += ($data[$game_type_group]['business_amount_of_mine'] + $data[$game_type_group]['business_amount_of_team']);
                }
                $res = $this->get_commission_percent($game_type_group,$temp_business_amount_of_team);
                if($res->code) return $res;
                $temp_general_commission_percent = $res->data / 100;

                // 遍历所有的直属下级数据，计算佣金
                foreach ($members_business_data as $data)
                {
                    // 代理贡献上级：总系数*代理自营业绩
                    $temp = $data[$game_type_group]['business_amount_of_mine'] * $temp_general_commission_percent;
                    $temp_commission_amount += $temp;

                    // 代理的团队贡献上级：（总佣金系数-代理佣金系数）* 代理团队业绩
                    $temp = $data[$game_type_group]['business_amount_of_team'] * ($temp_general_commission_percent - $data[$game_type_group]['commission_percent']);
                    $temp_commission_amount += $temp;
                }

                // 返佣统计
                $players_day_report[$player_id]['stats']['business_amount_of_mine'] += $temp_business_amount_of_mine;
                $players_day_report[$player_id]['stats']['business_amount_of_team'] += $temp_business_amount_of_team;
                $players_day_report[$player_id]['stats']['commission_amount'] += $temp_commission_amount;
                // 返佣明细
                $players_day_report[$player_id]['details'][$game_type_group] = array(
                    'user_id' =>                    $player_id,
                    'Ymd' =>                        $Ymd,
                    'game_type_group' =>            $game_type_group,
                    'business_amount_of_mine' =>    $temp_business_amount_of_mine,
                    'business_amount_of_team' =>    $temp_business_amount_of_team,
                    'commission_amount' =>          $temp_commission_amount,
                );
            }
        }

        // 过滤多余的账单
        foreach ($players_day_report as $player_id => $row)
        {
            $stats = $row['stats'];
            if($stats['business_amount_of_mine'] == 0 && $stats['business_amount_of_team'] == 0)
            {
                unset($players_day_report[$player_id]);
                continue;
            }

            $details = $row['details'];
            foreach ($details as $index => $row2)
            {
                if($row2['business_amount_of_mine'] == 0 && $row2['business_amount_of_team'] == 0)
                {
                    unset($players_day_report[$player_id]['details'][$index]);
                    continue;
                }

                // 如果返佣金额，小于0.01，不计算返佣
                $row2['commission_amount'] = round($row2['commission_amount'],2);
                if($row2['commission_amount'] < 0.01) $row2['commission_amount'] = 0;
            }
        }

        if(count($players_day_report) == 0) return new res(1,'没有可结算的佣金');

        // 保存所有账单
        try
        {
            bonjour::$mysql->begin_transaction();

            // 遍历所有账单
            foreach ($players_day_report as $row)
            {
                $stats = $row['stats'];

                $res = bonjour::$container->get(user_day_report::class)->insert(
                    $stats['Ymd'],
                    $stats['user_id'],
                    $stats['business_amount_of_team'],
                    $stats['business_amount_of_mine'],
                    $stats['commission_amount']
                );
                if($res->code) throw new \Exception('',1);

                // 遍历所有账单的明细
                foreach ($row['details'] as $detail)
                {
                    $res = bonjour::$container->get(user_day_report_details::class)->insert(
                        $stats['user_id'],
                        $stats['Ymd'],
                        $detail['game_type_group'],
                        $detail['business_amount_of_team'],
                        $detail['business_amount_of_mine'],
                        $detail['commission_amount']
                    );
                    if($res->code) throw new \Exception('',1);
                }
            }

            bonjour::$mysql->commit();
        }catch (\Exception $e)
        {
            bonjour::$mysql->rollback();
            if($e->getCode() == 1) return $res;
            $res = new res(1,'执行发生异常',$e->getMessage());
            return $res;
        }

        return new res(0,'');
    }

    /**
     * 缓存所有玩家的日报表
     *
     * @param int               $Ymd
     *
     * @return \bonjour\format\res
     * @throws
     * */
    public function cache_game_stats_of_all_players(int $Ymd)
    {
        $res = bonjour::$container->get(game_day_stats::class)->select_by_ymd('*',$Ymd);
        if($res->code) return $res;
        foreach ($res->qry as $row)
        {
            $player_id = $row['player_id'];
            $game_type_group = $row['game_type_group'];
            if(isset($this->player_stats_data[$player_id]) == false) $this->player_stats_data[$player_id] = array();
            $this->player_stats_data[$player_id][$game_type_group] = $row['bet'];
        }
        return $res;
    }

    /**
     * 查询返水模块的游戏分类
     * 如果游戏没有进行分类，将会报错!
     *
     * 如果没有游戏分类，status=0
     * 如果存在游戏分类，status=1
     *
     * @param string            $platform_code
     * @param string            $game_record_code
     *
     * @return \bonjour\format\res;
     * @throws
     * */
    public function select_data_of_game_classification(string $platform_code,string $game_record_code)
    {
        $res = bonjour::$container->get(game_classification::class)->select_by_platform_code_and_game_record_code('*',$platform_code,$game_record_code);
        if($res->code) return $res;
        if($res->qry->num_rows != 1) return new res(0,'',0);
        $classification_data = $res->qry->fetch_assoc();

        // 假设查询出来的路径 = 根目录/返佣管理/棋牌/游戏列表
        // count - 1 = 游戏列表
        // count - 2 = 棋牌
        $res = bonjour::$container->get(file_manager::class)->select_data_path_array_by_id($classification_data['parent_id']);
        if($res->code) return $res;
        $path = $res->data;
        $count = count($path);
        $game_type_group = $path[$count-2];

        return new res(0,'',array(
            'game_type_group' =>    $game_type_group
        ));
    }

    public function get_commission_percent(string $game_type_group,float $business_amount)
    {
        if(isset($this->commission_setting[$game_type_group]) == false) return new res(1,sprintf('没有此分类的佣金系数 %s',$game_type_group));
        if(count($this->commission_setting[$game_type_group]) == 0) return new res(1,sprintf('没有此分类的佣金系数 %s',$game_type_group));
        $index = bonjour::$container->get(lib_decide::class)->less_than('less_than',$business_amount,$this->commission_setting[$game_type_group]);
        return new res(0,'',$this->commission_setting[$game_type_group][$index]['percent']);
    }

    public function get_business_amount_by_player_id_and_game_type_group(int $player_id,string $game_type_group)
    {
        if(isset($this->player_stats_data[$player_id]) == false) return 0;
        if(isset($this->player_stats_data[$player_id][$game_type_group]) == false) return 0;
        return $this->player_stats_data[$player_id][$game_type_group];
    }

    /**
     * 根据player_id获取玩家的营业情况
     * 包包所有的游戏分类
     * 包含玩家的自营业绩，团队业绩，团队业绩的佣金系数 (佣金系数，不包含此玩家的自营业绩，只计算玩家的团队业绩)
     *
     * @param int               $player_id
     * 玩家ID
     *
     * @return \bonjour\format\res
     * @throws
     * */
    public function get_business_amount(int $player_id)
    {
        $ret_data = array();

        // 获取chief_id下的所有会员ID
        $user_id_list = array();
        $res = bonjour::$container->get(user_nexus_tree::class)->select_by_chief_id('user_id',$player_id);
        if($res->code) return $res;
        foreach ($res->qry as $row) $user_id_list[] = $row['user_id'];

        foreach ($this->game_type_group_list as $game_type_group)
        {
            $business_amount_of_mine = $this->get_business_amount_by_player_id_and_game_type_group($player_id,$game_type_group);
            $business_amount_of_team = 0;
            foreach ($user_id_list as $user_id) $business_amount_of_team += $this->get_business_amount_by_player_id_and_game_type_group($user_id,$game_type_group);
            $res = $this->get_commission_percent($game_type_group,$business_amount_of_team);
            if($res->code) return $res;
            $commission_percent = $res->data / 100;

            $ret_data[$game_type_group] = array(
                'business_amount_of_mine' => $business_amount_of_mine,
                'business_amount_of_team' => $business_amount_of_team,
                'commission_percent' => $commission_percent
            );
        }

        return new res(0,'',$ret_data);
    }

    public function get_commission(int $user_id,float $amount)
    {
        $amount = round($amount,2);
        if($amount < 0.01)
        {
            $res = new res(1,'领取金额不能少于0.01');
            return $res;
        }

        // 账变明细ID
        $res = bonjour::$container->get(config_id::class)->select_by_group_and_key('`id`','moneyDetailsTypeID','promotion_commission',true);
        if($res->code) return $res;
        $user_money_details_type_id = $res->qry->fetch_assoc()['id'];

        // 减量用户推广钱包
        $res = bonjour::$container->get(\app\module\promotion\model\user::class)->decr($user_id,$amount);
        if($res->code) return $res;

        // 增量用户钱包
        $res = bonjour::$container->get(mc::class)->incr_user_money($user_id,$amount,$user_money_details_type_id,[]);
        if($res->code) return $res;

        return $res;
    }

    public function incr(int $user_id,float $amount)
    {
        $res = bonjour::$container->get(\app\module\promotion\model\user::class)->incr($user_id,$amount);
        if($res->code) return $res;

        return $res;
    }
}