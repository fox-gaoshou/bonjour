<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18
 * Time: 21:23
 */

namespace bonjour\lib;

class feedback
{
    static public function echoAjax($status,$msg,$data='')
    {
        echo json_encode(array(
            'sta' => $status,
            'msg' => $msg,
            'dat' => $data
        ));
    }
}