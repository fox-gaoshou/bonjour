<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/7/2
 * Time: 14:32
 */

class feedback
{
//    static public function pack(string $obj,string $cmd,int $errno,string $error='',$data='')
//    {
//        return json_encode(array(
//            'obj' =>        $obj,
//            'cmd' =>        $cmd,
//            'errno' =>      $errno,
//            'error' =>      $error,
//            'data' =>       $data
//        ));
//    }

    static public function pack(string $obj,string $cmd,int $errno,string $error='',$data='')
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