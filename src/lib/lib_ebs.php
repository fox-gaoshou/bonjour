<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/20
 * Time: 10:09
 */

namespace bonjour\lib;


class lib_ebs {

    private $authCodeKey = 'DoReMeFaSo';
    
    function authCode($input, $key) {

        # Input must be of even length.
        if (strlen($input) % 2) {
            //$input .= '0';
        }

        # Keys longer than the input will be truncated.
        if (strlen($key) > strlen($input)) {
            $key = substr($key, 0, strlen($input));
        }

        # Keys shorter than the input will be padded.
        if (strlen($key) < strlen($input)) {
            $key = str_pad($key, strlen($input), '0', STR_PAD_RIGHT);
        }

        # Now the key and input are the same length.
        # Zero is used for any trailing padding required.

        # Simple XOR'ing, each input byte with each key byte.
        $result = '';
        for ($i = 0; $i < strlen($input); $i++) {
            $result .= $input{$i} ^ $key{$i};
        }
        return $result;
    }

    /**
     * 加密
     */
    function encrypt($str) {

        $hashKey = $this->base64url_encode($this->authCode($str, $this->authCodeKey));
        //$hashKey = $this->base64url_encode($sessionId);
        return $hashKey;
    }

    /**
     * 解密
     */
    function decrypt($hashKey) {
        $sessionId = $this->authCode($this->base64url_decode($hashKey), $this->authCodeKey);
        //$sessionId = $this->base64url_decode($hashKey);
        return $sessionId;
    }

    // url传输需要替换部分字符
    function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    // url传输需要替换部分字符
    function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    function setKey($key)
    {
        $this->authCodeKey = $key;
    }
}