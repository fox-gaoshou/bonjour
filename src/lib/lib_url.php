<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2018/12/28
 * Time: 11:22
 */

namespace bonjour\lib;


class lib_url
{
    public function joinParams(&$data)
    {
        $str = '';
        foreach ($data as $key => $val) $str .= sprintf("%s=%s&",$key,$val);
        return substr($str,0,-1);
    }
}