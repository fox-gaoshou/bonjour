<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/4
 * Time: 21:22
 */

namespace bonjour\controllers;


use bonjour\core\bonjour;


class controller
{
    public $session;
    public $terminal_type;
    private function enable($val)
    {
        $val = strtoupper($val);
        if(in_array($val,['1','TRUE','ENABLE'])) return true;
        return false;
    }
    public function __construct()
    {
        $conf = bonjour::$conf->get_config('controller.ini');
        if(is_array($conf) == false) return;

        // 设置请求头
        if(isset($conf['headers']))
        {
            foreach ($conf['headers'] as $key => $val) header(sprintf("%s : %s",$key,$val));
        }

        // 回复指定的请求
        if(isset($conf['response_method']))
        {
            $method = strtoupper($_SERVER['REQUEST_METHOD']);
            if(isset($conf['response_method'][$method])) exit($conf['response_method'][$method]);
        }

        // 检查设备类型
        if(isset($conf['terminal-type']))
        {
            $terminal_type = $_SERVER['HTTP_TERMINAL_TYPE'] ?? exit;
            if(isset($conf['terminal-type'][$terminal_type]) == false) exit;
            if($this->enable($conf['terminal-type'][$terminal_type]) == false) exit;
            $this->terminal_type = $terminal_type;
        }

        // 初始化会话
        $this->session = bonjour::$ext->session;
    }
    public function __destruct()
    {
        bonjour::$mysql->close_all();
        bonjour::$redis->close_all();
    }
}