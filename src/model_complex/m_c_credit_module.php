<?php


namespace bonjour\model_complex;


use bonjour\core\bonjour;
use bonjour\format\res\res;
use bonjour\model\credit_module\credit_module_user;
use bonjour\model\credit_module\credit_module_user_details;


class m_c_credit_module
{
    public function incr(int $user_id,int $type,float $amount)
    {
        $res = bonjour::$container->get(credit_module_user::class)->incr($user_id,$amount);
        if($res->code) return $res;

        $res = bonjour::$container->get(credit_module_user::class)->select_by_id('balance',$user_id,true);
        if($res->code) return $res;
        $balance = $res->qry->fetch_assoc()['balance'];

        $res = bonjour::$container->get(credit_module_user_details::class)->insert($user_id,$type,$amount,$balance);
        if($res->code) return $res;

        return new res();
    }
    public function decr(int $user_id,int $type,float $amount)
    {
        $res = bonjour::$container->get(credit_module_user::class)->decr($user_id,$amount);
        if($res->code) return $res;

        $res = bonjour::$container->get(credit_module_user::class)->select_by_id('balance',$user_id,true);
        if($res->code) return $res;
        $balance = $res->qry->fetch_assoc()['balance'];

        $res = bonjour::$container->get(credit_module_user_details::class)->insert($user_id,$type,$amount,$balance);
        if($res->code) return $res;

        return new res();
    }
}