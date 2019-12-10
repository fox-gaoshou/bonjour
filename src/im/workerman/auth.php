<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/7/2
 * Time: 14:15
 */


use GatewayWorker\Lib\Gateway;


class auth
{
    static $errno_session_be_occupy = 1;
    static public function login()
    {

    }


    // 验证失败的时候，提示会话已经过期
    static public function feedback_session_expired()
    {

    }

    // 把就的终端连接关闭，保持一个uid对应一个终端连接，并提示旧的终端会话被占用
    static public function bind_client_and_unbind_old_client($user_id,$new_client_id)
    {
        $client_id_list = Gateway::getClientIdByUid($user_id);
        foreach ($client_id_list as $client_id)
        {
            Gateway::closeClient($client_id,self::feedback_pack('auth','error_handler',self::$errno_session_be_occupy,'用户会话被其他设备占用!'));
        }
        Gateway::bindUid($new_client_id,$user_id);
    }

    static public function feedback_pack(string $obj,string $cmd,int $errno,string $error='',$data='')
    {
        return json_encode(array(
            'obj' =>        $obj,
            'cmd' =>        $cmd,
            'errno' =>      $errno,
            'error' =>      $error,
            'data' =>       $data
        ));
    }
}