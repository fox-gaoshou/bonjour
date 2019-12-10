<?php


namespace bonjour\model_complex;


use bonjour\core\bonjour;
use bonjour\format\res\res;
use bonjour\model\third_game_manager\tgm_player_rakeback_bill_details;
use bonjour\model\third_game_manager\tgm_player_rakeback_game_record_stats;

class m_c_third_game_player_rakeback
{
    /**
     * 获取未进行结算的游戏记录统计
     * 返回数组格式，数组的使用 `game_type_group`_`game_type` 作为键值
     * 返水的数据，已经过滤 bet=0 的数据
     *
     * @param int           $player_id
     * @param int           $Ymd
     *
     * @return \bonjour\format\res
     * @throws
     * */
    public function select_data_of_uncheck_record_stats(int $player_id,int $Ymd)
    {
        $stats_list = array();

        $res = bonjour::$container->get(tgm_player_rakeback_game_record_stats::class)->select_by_player_id_and_ymd('*',$player_id,$Ymd);
        if($res->code) return $res;
        foreach ($res->qry as $row)
        {
            $row_key = sprintf('%s_%s',$row['game_type_group'],$row['game_type']);
            $stats_list[$row_key] = $row;
        }

        $res = bonjour::$container->get(tgm_player_rakeback_bill_details::class)->select_by_player_id_and_ymd('*',$player_id,$Ymd);
        if($res->code) return $res;
        foreach ($res->qry as $row)
        {
            $row_key = sprintf('%s_%s',$row['game_type_group'],$row['game_type']);
            $stats_list[$row_key]['bet'] -= $row['bet'];
            if($stats_list[$row_key]['bet'] <= 0) unset($stats_list[$row_key]);
        }

        return new res(0,'',null,$stats_list);
    }
}
