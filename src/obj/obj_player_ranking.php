<?php


namespace bonjour\obj;


use bonjour\core\bonjour;
use bonjour\format\res\res;
use bonjour\model\m_ranking_user;


class obj_player_ranking
{
    public function gen()
    {
        $lock_name = "player:ranking:regen";
        if(bonjour::$redis->lock($lock_name,1,-1) == false) return new res(1,'排行榜正在计算中...');

        // 获取所有的虚拟用户
        $res = bonjour::$container->get(m_ranking_user::class)->select_all('`id`,`min_balance`,`max_balance`');
        if($res->code) return $res;

        try
        {
            bonjour::$mysql->begin_transaction();

            // 更新虚拟用户的随机金额
            foreach ($res->qry as $row)
            {
                $balance = mt_rand((int)$row['min_balance'],(int)$row['max_balance']) + lcg_value();
                $res = bonjour::$container->get(m_ranking_user::class)->update_balance_by_user_id($row['id'],$balance);
                if($res->code) throw new \Exception('',1);
            }

            bonjour::$mysql->commit();
        }catch (\Exception $e)
        {
            bonjour::$mysql->rollback();
            if($e->getCode() == 1) return $res;
            return new res(1,'执行发生异常',$e->getMessage());
        }



        bonjour::$redis->unlock($lock_name);
    }
}