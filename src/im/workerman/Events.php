<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;
use \bonjour\core\bonjour;

require_once ('errno.php');

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    public static $message;
    public static function onWorkerStart($businessWorker)
    {
        echo sprintf("WorkerStart\n");

        bonjour::$redis->connect();

        \Workerman\Lib\Timer::add(10,function(){
            bonjour::$redis->ins->ping();
        });

//        \Workerman\Lib\Timer::add(CHECK_HEARTBEAT_TIME,function()use($businessWorker){
//            $time_now = time();
//            foreach (Gateway::getAllClientSessions() as $sessionKey => &$sessionVal)
//            {
//                // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
//                if(empty($sessionVal['last_message_time']))
//                {
//                    $sessionVal['last_message_time'] = $time_now;
//                }
//
//                var_dump($sessionVal);
//            }
////            var_dump(Gateway::getAllClientSessions());
////            foreach (Gateway::getAllClientSessions() as $session)
////            {
////                // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
////                var_dump($session);
////            }
////            foreach ($businessWorker->connections as $connection)
////            {
////                // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
////                if(empty($connection->lastMessageTime))
////                {
////                    $connection->lastMessageTime = $time_now;
////                    continue;
////                }
////
////                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
////                if(($time_now - $connection->lastMessageTime) > HEARTBEAT_TIME)
////                {
////                    $connection->close();
////                }
////            }
//        });
    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        $_SESSION['auth'] = array();
        $_SESSION['auth']['is_auth'] = 0;
        $_SESSION['auth']['session_id'] = null;
        $_SESSION['auth']['timer_id'] = \Workerman\Lib\Timer::add(10,function($client_id) {
            Gateway::closeClient($client_id);
        },array($client_id),false);

        // 向当前client_id发送数据 
//        Gateway::sendToClient($client_id, "Hello $client_id\r\n");
        // 向所有人发送
        // Gateway::sendToAll("$client_id login\r\n");
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        // 向所有人发送 
//        Gateway::sendToAll("$client_id said $message\r\n");

        // 校验数据格式
        self::$message = json_decode($message,true);
        if(isset(self::$message['obj']) == false) return;
        if(isset(self::$message['cmd']) == false) return;
        // ------------------------------------------------------------------------------------------------------------------------------
        // 校验是否有验证
        if(($_SESSION['auth']['is_auth'] == 0) && (self::$message['obj'] != 'auth') && (self::$message['cmd'] != 'login')) return;
        // ------------------------------------------------------------------------------------------------------------------------------

        switch (self::$message['obj'])
        {
            case 'auth':
                switch (self::$message['cmd'])
                {
                    case 'login':
                        if(isset(self::$message['token']) == false) goto operation_finish;
                        if(isset(self::$message['session_id']) == false) goto operation_finish;

                        $token = self::$message['token'];
                        $session_id = self::$message['session_id'];

                        $res = bonjour::$ext->auth->token2id($token);
                        if($res->code)
                        {
                            Gateway::sendToCurrentClient(json_encode(session_module_token_is_invalid));
                            goto operation_finish;
                        }
                        $user_id = $res->data;

                        $user_info = bonjour::$ext->user_cache->hMGet($user_id,['username','sessionID']);
                        if($user_info === false) goto cache_response_failed;
                        if($user_info['sessionID'] != $session_id)
                        {
                            Gateway::sendToCurrentClient(json_encode(session_module_session_id_is_invalid));
                            goto operation_finish;
                        }

                        // 清除定时器
                        \Workerman\Lib\Timer::del($_SESSION['auth']['timer_id']);
                        unset($_SESSION['auth']['timer_id']);

                        $_SESSION['personal'] = array();
                        $_SESSION['personal']['user_id'] =  $user_id;
                        $_SESSION['personal']['username'] = $user_info['username'];

                        $client_id_list = Gateway::getClientIdByUid($user_id);
                        foreach ($client_id_list as $cid)
                        {
                            Gateway::closeClient($cid,json_encode(session_module_session_be_occupy));
                        }
                        Gateway::bindUid($client_id,$user_id);

                        self::feedback();
                        break;
                    default:
                        break;
                }
                break;
        }

        operation_finish:
//        bonjour::$msql->close();
//        bonjour::$redis->close();
        return;

        // 缓存响应失败
        cache_response_failed:
        Gateway::sendToCurrentClient(json_encode(array(
            'obj' =>        self::$message['obj'],
            'cmd' =>        self::$message['cmd'],
            'errno' =>      1,
            'error' =>      '服务器缓存响应失败!',
            'data' =>       ''
        )));
        return;
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        // 向所有人发送
        // GateWay::sendToAll("$client_id logout\r\n");
    }

    private static function feedback($errno=0,$error='',$data='')
    {
        Gateway::sendToCurrentClient(json_encode(array(
            'obj' =>        self::$message['obj'],
            'cmd' =>        self::$message['cmd'],
            'errno' =>      $errno,
            'error' =>      $error,
            'data' =>       $data
        )));
    }
}
