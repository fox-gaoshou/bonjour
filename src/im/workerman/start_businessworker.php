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
use \Workerman\Worker;
use \Workerman\WebServer;
use \GatewayWorker\Gateway;
use \GatewayWorker\BusinessWorker;
use \Workerman\Autoloader;

// 自动加载类
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../autoload.php';

define('HEARTBEAT_TIME', 30); // 定义一个心跳间隔30秒
define('CHECK_HEARTBEAT_TIME', 1); // 检查连接的间隔时间


$conf = \bonjour\core\bonjour::$conf->workerman();

switch ($conf['business']['net_model'])
{
    case 0:
        $register_address = sprintf("127.0.0.1:%s",$conf['register']['port']);
        break;
    case 1:
        $register_address = sprintf("%s:%s",$conf['register']['local_net_address'],$conf['register']['port']);
        break;
    case 2:
        $register_address = sprintf("%s:%s",$conf['register']['public_net_address'],$conf['register']['port']);
        break;
    default:
        exit ('不支持的net_model' . PHP_EOL);
}

$worker = new BusinessWorker();
$worker->name =             $conf['business']['process_name'];
$worker->count =            $conf['business']['process_max'];
$worker->registerAddress =  $register_address;
$worker->eventHandler =     $conf['business']['event_handler'];

//// bussinessWorker 进程
//$worker = new BusinessWorker();
//// worker名称
//$worker->name = 'YourAppBusinessWorker';
//// bussinessWorker进程数量
//$worker->count = 4;
//// 服务注册地址
//$worker->registerAddress = '127.0.0.1:1238';
///*
// * 设置处理业务的类为MyEvent。
// * 如果类带有命名空间，则需要把命名空间加上，
// * 类似$worker->eventHandler='\my\namespace\MyEvent';
// */
//$worker->eventHandler = 'Events';

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

