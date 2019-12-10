<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/6
 * Time: 11:48
 */

namespace bonjour\core;


/**
 * @property \bonjour\extend\auth           $auth
 * @property \bonjour\extend\rdc            $rdc
 * @property \bonjour\extend\rdsgc          $rdsgc
 * @property \bonjour\extend\order          $order
 * @property \bonjour\extend\api_cache      $api_cache
 * @property \bonjour\extend\user_cache     $user_cache
 * @property \bonjour\extend\admin_cache    $admin_cache
 * @property \bonjour\extend\network        $network
 * @property \bonjour\extend\redis_task     $redis_task
 * @property \bonjour\extend\session        $session
 * @property \bonjour\extend\file_manager   $file_manager
 * */
class ext
{
    public function __get($name)
    {
        $c = '\\bonjour\\extend\\' . $name;
        return $this->{$name} = new $c;
    }
}