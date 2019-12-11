<?php


namespace bonjour\lib;


class lib_file_log
{
    public function out(string $path,string $file_name,string $type,string $message)
    {
        // 生成日志时间
        $mtimestamp =   sprintf("%.3f", microtime(true));
        $timestamp =    floor($mtimestamp);
        $milliseconds = round(($mtimestamp - $timestamp) * 1000);
        $datetime =     date("Y-m-d H:i:s", $timestamp) . '.' . $milliseconds;

        // 连接内容
        $content = sprintf("%s %s\n%s\n",$datetime,$type,$message);

        // 创建日志的目录
        if(!is_dir($path)) mkdir($path,0755,true);

        // 保存内容
        file_put_contents(sprintf("%s/%s.log",$path,$file_name),$content,FILE_APPEND);
    }
    public function auto_ymd(string $path,string $type,string $message)
    {
        // 生成日志时间
        $mtimestamp =   sprintf("%.3f", microtime(true));
        $timestamp =    floor($mtimestamp);
        $milliseconds = round(($mtimestamp - $timestamp) * 1000);
        $datetime =     date("Y-m-d H:i:s", $timestamp) . '.' . $milliseconds;

        // 生成文件名
        $Ymd = date('Ymd',$timestamp);
        $file_name = $path . $Ymd . '.log';

        // 创建日志的目录
        $content = sprintf("%s %s \n %s\n",$datetime,$type,$message);
        if(!is_dir($path)) mkdir($path,0755,true);

        // 保存内容
        file_put_contents($file_name,$content,FILE_APPEND);
    }
    public function auto_ymd_v1(string $path,string $type,string $message)
    {
        // 生成日志时间
        $mtimestamp =   sprintf("%.3f", microtime(true));
        $timestamp =    floor($mtimestamp);
        $milliseconds = round(($mtimestamp - $timestamp) * 1000);
        $datetime =     date("Y-m-d H:i:s", $timestamp) . '.' . $milliseconds;

        // 生成文件名
        $Ymd = date('Ymd',$timestamp);
        $file_name = sprintf('%s/%s.log',$path,$Ymd);

        // 创建日志的目录
        $content = sprintf("%s %s \n %s\n",$datetime,$type,$message);
        if(!is_dir($path)) mkdir($path,0755,true);

        // 保存内容
        file_put_contents($file_name,$content,FILE_APPEND);
    }

    public function auto_ymd_dir(string $path,string $file_name,string $content)
    {
        // 生成日志时间
//        $mtimestamp =   sprintf("%.3f", microtime(true));
//        $timestamp =    floor($mtimestamp);
//        $milliseconds = round(($mtimestamp - $timestamp) * 1000);
//        $datetime =     date("Y-m-d H:i:s", $timestamp) . '.' . $milliseconds;

        $dir = sprintf("%s/%s",$path,date('Ymd'));
        if(!is_dir($dir)) mkdir($dir,0755,true);

        $full_path = sprintf("%s/%s.log",$dir,$file_name);
        file_put_contents($full_path,$content,FILE_APPEND);
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