<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/21
 * Time: 14:53
 */

namespace bonjour\lib;

class referrer
{
    // 对UID进行加密
    public function pack($referrerID)
    {
        $ebs = new ebs();
        $ebs->setKey('LetMeDoIt');
        $str = sprintf("referrer:%s",$referrerID);
        return $ebs->encrypt($str);
    }
    // 对加密的内容进行解密
    public function unPack($str)
    {
        if(empty($str)) return 0;

        $ebs = new ebs();
        $ebs->setKey('LetMeDoIt');
        $data = $ebs->decrypt($str);
        if(empty($data)) return 0;
        list($prefix,$referrerID) = explode(':',$data);
        if($prefix != 'referrer') return 0;
        if(is_numeric($referrerID) == false) return 0;

        return $referrerID;
    }
}