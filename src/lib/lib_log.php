<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2018/12/19
 * Time: 21:20
 */

namespace bonjour\lib;


use bonjour\core\bonjour;

class lib_log
{
//    public function log($path,$type,$appendData='')
//    {
//        $str = sprintf("%s:%.3f  %s\n %s \n",
//            date('Ymd H:i:s'),microtime(),$type,var_export($appendData,true)
//        );
//        $temp = explode('/',$fileName);
//        $theFileName = array_pop($temp);
//        $Ymd = date('Ymd');
//        $dir = bonjour_root.'/log'.implode('/',$temp).'/'.$Ymd;
//        if(!is_dir($dir)) mkdir($dir,0755,true);
//        $res = file_put_contents($dir.'/'.$theFileName,$str,FILE_APPEND);
//    }


    public function autoYmd($path,$type,$appendData='')
    {
        $mtimestamp =   sprintf("%.3f", microtime(true));
        $timestamp =    floor($mtimestamp);
        $milliseconds = round(($mtimestamp - $timestamp) * 1000);
        $datetime = date("Y-m-d H:i:s", $timestamp) . '.' . $milliseconds;
        $str = sprintf("%s %s\n %s\n", $datetime,$type,var_export($appendData,true));
        $dir = sprintf("%s/log%s",bonjour_root,$path);
        if(!is_dir($dir)) mkdir($dir,0755,true);
        $Ymd = date('Ymd',$timestamp);
        file_put_contents($dir.$Ymd.'.log',$str,FILE_APPEND);
    }

    public function dubiousRequest($desc,array $appendData)
    {
        // 生成时间
        $mtimestamp =   sprintf("%.3f", microtime(true));
        $timestamp =    floor($mtimestamp);
        $milliseconds = round(($mtimestamp - $timestamp) * 1000);
        $datetime = date("Y-m-d H:i:s", $timestamp) . '.' . $milliseconds;

        // 第一个字符串是 时间 | 描述可疑原因 | 请求数据 | post数据
        $content = sprintf("%s | %s\r\nrequest=>%s\r\npost=>%s\r\n", $datetime,$desc,var_export($_SERVER,true),var_export($_POST,true));
        if(!empty($appendData)) $content .= var_export($appendData,true);

        $dir = sprintf("%s/log/dubiousRequest/",bonjour_root);
        if(!is_dir($dir)) mkdir($dir,0755,true);
        $Ymd = date("Ymd");
        file_put_contents($dir.$Ymd.".log",$content,FILE_APPEND);
    }

    public function dubious_request(string $desc,array $append_data)
    {
        // 生成时间
        $mtimestamp =   sprintf("%.3f", microtime(true));
        $timestamp =    floor($mtimestamp);
        $milliseconds = round(($mtimestamp - $timestamp) * 1000);
        $datetime = date("Y-m-d H:i:s", $timestamp) . '.' . $milliseconds;

        // 第一个字符串是 时间 | 描述可疑原因 | 请求数据 | post数据
        $content = sprintf("%s | %s\r\nrequest=>%s\r\npost=>%s\r\n", $datetime,$desc,var_export($_SERVER,true),var_export($_POST,true));
        if(!empty($append_data)) $content .= var_export($append_data,true);

        // 生成路径目录
        $dir = sprintf('%s/dubious_request/',bonjour::$evn->dir_log);
        if(!is_dir($dir)) mkdir($dir,0755,true);

        // 保存日志
        $Ymd = date('Ymd');
        file_put_contents($dir.$Ymd.".log",$content,FILE_APPEND);
    }
}