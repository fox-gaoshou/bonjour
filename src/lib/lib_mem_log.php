<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/4/23
 * Time: 16:21
 */

namespace bonjour\lib;


class lib_mem_log
{
    private $memLog;
    private $memLogToggle = 0;
    public function begin()
    {
        if($this->memLogToggle == 1) var_dump("Multiple Begin\n");
        $this->memLogToggle = 1;
        $this->memLog = "";
    }
    public function append(string $logType,$content)
    {
        if($this->memLogToggle == 0) return;
        $mtimestamp = sprintf("%.3f", microtime(true));
        $timestamp = floor($mtimestamp);
        $milliseconds = round(($mtimestamp - $timestamp) * 1000);
        $datetime = date("Y-m-d H:i:s", $timestamp) . '.' . $milliseconds;

        if(is_string($content) == false) $content = json_encode($content,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $str = $datetime . ' ' . $logType . "\r\n" . $content . "\r\n";
        $this->memLog = $this->memLog . $str;
    }
    public function get()
    {
        return $this->memLog;
    }
    public function get_clean()
    {
        if($this->memLogToggle == 0) return null;
        $temp = $this->memLog;
        $this->memLog = null;
        return $temp;
    }
    public function clean()
    {
        $this->memLog = null;
    }
}