<?php


namespace bonjour\lib;


class lib_swoole
{
    public function allow_cross(&$response)
    {
        $response->header('Access-Control-Allow-Origin','*');
        $response->header('Access-Control-Allow-Methods','POST, GET, OPTIONS, PUT, DELETE');
//        $response->header('Access-Control-Allow-Headers','x-requested-with,content-type,sessionID,token');
//        $response->header('Access-Control-Allow-Origin','OPTIONS');
//        header("Access-Control-Allow-Origin: *");
//        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
//        header('Access-Control-Allow-Headers:x-requested-with,content-type,sessionID,token');
//        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'OPTIONS') {
//            echo "OK";
//            exit;
//        }
//        header('Content-Type:text/html;charset=utf-8');
    }
}