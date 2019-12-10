<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/2/18
 * Time: 15:17
 */

namespace bonjour\lib;


class lib_rand
{
    /**
     * 随机生成，指定长度的密码。由数字、小写字母、大写字母组成
     *
     * @param $length
     * 密码长度
     *
     * @return string
     * */
    function password($length = 12)
    {
        $password = '';
        $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $charLen = strlen($chars);
        for($i=0;$i<$length;$i++)
        {
            $loop = mt_rand(0, ($charLen-1));
            $password .= $chars[$loop];
        }
        return $password;
    }
}