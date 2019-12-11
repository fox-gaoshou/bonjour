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
    private $ini_path;
    private $ini_conf;

    public function __construct()
    {
        $this->ini_conf = array();
        $this->ini_path = bonjour::$evn->dir_config;
    }
    public function get_config(string $file_name)
    {
        if($this->ini_path == null) throw new \Exception('未设置ini配置路径');
        if(isset($this->ini_conf[$file_name]) == false) $this->ini_conf[$file_name] = parse_ini_file(sprintf("%s/%s",$this->ini_path,$file_name),true);
        return $this->ini_conf[$file_name];
    }
}