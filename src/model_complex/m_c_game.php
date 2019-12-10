<?php


namespace bonjour\model_complex;


use bonjour\core\bonjour;
use bonjour\model\game_module\game_module_platform_trans_task;
use bonjour\model\game_module\game_module_platform_trans_user;

class m_c_game
{
    /**
     * 生成上分任务
     *
     * @param int           $user_id
     * 用户UID
     * @param string        $username
     * 用户账号
     * @param string        $platform_code
     * 平台编码
     * @param float         $amount
     * 上分额度
     * @param string        $guid
     * 任务ID
     *
     * @return \bonjour\format\res
     * @throws
     * */
    public function to(int $user_id,string $username,string $platform_code,float $amount,string $guid)
    {
        $type = game_module_platform_trans_user::$type_to;

        // 用户任务抢占
        $res = bonjour::$container->get(game_module_platform_trans_user::class)->start($username, $platform_code, $type, $guid);
        if ($res->code) return $res;

        // 生成任务
        $res = bonjour::$container->get(game_module_platform_trans_task::class)->insert($guid, $platform_code, $type, $user_id, $username, $amount);
        if ($res->code) return $res;

        return $res;
    }
}