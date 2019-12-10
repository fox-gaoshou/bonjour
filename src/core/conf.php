<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/6
 * Time: 11:18
 */

namespace bonjour\core;


class conf
{
    private $conf;
    public function __construct()
    {
        $this->conf = array();
    }

    private $ini_path;
    private $ini_conf;
    public function set_config_path(string $path)
    {
        $this->ini_path = $path;
    }
    public function get_config(string $file_name)
    {
        if($this->ini_path == null) throw new \Exception('未设置ini配置路径');
        if(isset($this->ini_conf[$file_name]) == false) $this->ini_conf[$file_name] = parse_ini_file(sprintf("%s/%s",$this->ini_path,$file_name),true);
        return $this->ini_conf[$file_name];
    }

    public function load(string $file_name)
    {
        $file = bonjour::$evn->dir_config . '/' . $file_name . '.php';
        if(isset($this->conf[$file_name])) return $this->conf[$file_name];
        if(!file_exists($file)) throw new \Exception(sprintf('%s 配置文件不存在',$file_name));
        return $this->conf[$file_name] = include $file;
    }

    /**
     * 一般配置文件
     *
     * @param string                $key
     * 如果key=null,返回所有key的配置
     *
     * @return array | string
     *
     * @throws "如果key不存在，抛出错误"
     * */
    public function common($key=null)
    {
        if($key === null) return bonjour::$evn->common;
        if(isset(bonjour::$evn->common[$key]) == false) throw new \Exception(sprintf('Has no the config : %s',$key));
        return bonjour::$evn->common[$key];
    }

    /**
     * 获取workerman配置
     *
     * @param string | null         $service_type
     * service_type = register | gateway | business | null
     * 如果是null，所有类型
     *
     * @param string                $key
     * 配置key，如果 service_type = null 配置key不生效
     *
     * @return array | string
     *
     * @throws "如果service_type不存在，抛出错误，如果key不存在，抛出错误"
     * */
    public function workerman(string $service_type=null,$key=null)
    {
        $conf = $this->load('workerman');
        if(!is_array($conf)) throw new \Exception('workerman 配置文件，不是一个数组类型!');
        if($service_type === null) return $conf;

        if(isset($conf[$service_type]) == false) throw new \Exception(sprintf('没有定义 %s 配置',$service_type));
        if($key === null) return $conf[$service_type];

        if(isset($conf[$service_type][$key]) == false) throw new \Exception(sprintf('没有定义 %s %s',$service_type,$key));
        return $conf[$service_type][$key];
    }

    /**
     * 获取机器配置
     * 可以用于编制机器号码，和一些 mac 等等
     *
     * @param string $key
     *
     * @return array | string
     *
     * @throws "如果不存在的key配置，会直接抛出错误!"
     * */
    public function machine(string $key=null)
    {
        $conf = $this->common('machine');
        if($key === null) return $conf;
        if(isset($conf[$key]) == false) throw new \Exception(sprintf('Has no the machine config : %s',$key));
        return $conf[$key];
    }

    /**
     *
     * 获取数据库配置
     *
     * @param int|null $id
     * 如果id=null返回所有的数据库配置
     * 如果id=int 返回相应索引的数据库配置
     *
     * @return array | string
     *
     * @throws "如果不存在的索引配置，会直接抛出错误!"
     *
     * */
    public function database(int $id=null)
    {
        $conf = $this->load('database');
        if($id === null) return $conf;
        if(isset($conf[$id]) == false) throw new \Exception(sprintf('Has no the config of database id=%u',$id));
        return $conf[$id];
    }

    /**
     *
     * 获取redis配置
     *
     * @param int|null $id
     * 如果id=null返回所有的redis配置
     * 如果id=int 返回相应索引的redis配置
     *
     * @return array | string
     *
     * @throws "如果不存在的索引配置，会直接抛出错误!"
     *
     * */
    public function redis(int $id=null)
    {
        $conf = $this->load('redis');
        if($id === null) return $conf;
        if(isset($conf[$id]) == false) throw new \Exception(sprintf('Has no the config of redis id=%u',$id));
        return $conf[$id];
    }

    /**
     * 获取三方游戏平台的配置
     *
     * @param string                $platform_code
     * 平台编码
     * @param string                $key
     * 配置KEY
     *
     * @return array | string
     *
     * @throws "如果配置不存在，会直接抛出异常"
     * */
    public function third_game(string $platform_code,string $key)
    {
        $conf = $this->load('third_game');
        if(!is_array($conf)) throw new \Exception('third_game 配置文件，不是一个数组类型!');
        if(isset($conf[$platform_code]) == false) throw new \Exception(sprintf('没有定义 %s 三方游戏平台的配置',$platform_code));
        if(isset($conf[$platform_code][$key]) == false) throw new \Exception(sprintf('没有定义 %s %s',$platform_code,$key));
        return $conf[$platform_code][$key];
    }

    public function oss(int $config_id=0)
    {
        $conf = $this->load('oss');
        if(!is_array($conf)) throw new \Exception('oss 配置文件，不是一个数组类型');
        if(isset($conf[$config_id]) == false) throw new \Exception(sprintf('没有定义 %u 配置',$config_id));
        return $conf[$config_id];
    }

    public function ppp(string $id)
    {
        echo sprintf("i am ppp id=%s\n",$id);
    }
}