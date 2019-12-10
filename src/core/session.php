<?php


namespace bonjour\core;


class session
{
    // 会话加密的密钥
    private $encrypt_key = 'DoReMeFaSo';
    // 令牌的前缀
    private $token_prefix = 'DoReMi';

    // 加密的底层实现
    private function encrypt_low($input,$key)
    {
        # Input must be of even length.
        if (strlen($input) % 2)
        {
            //$input .= '0';
        }

        # Keys longer than the input will be truncated.
        if (strlen($key) > strlen($input))
        {
            $key = substr($key, 0, strlen($input));
        }

        # Keys shorter than the input will be padded.
        if (strlen($key) < strlen($input))
        {
            $key = str_pad($key, strlen($input), '0', STR_PAD_RIGHT);
        }

        # Now the key and input are the same length.
        # Zero is used for any trailing padding required.

        # Simple XOR'ing, each input byte with each key byte.
        $result = '';
        for ($i = 0; $i < strlen($input); $i++)
        {
            $result .= $input{$i} ^ $key{$i};
        }
        return $result;
    }

    // url传输需要替换部分字符
    private function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // url传输需要替换部分字符
    private function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    // 加密
    private function encrypt($str)
    {
        $hashKey = $this->base64url_encode($this->encrypt_low($str,$this->encrypt_key));
        return $hashKey;
    }

    // 解密
    private function decrypt($hashKey)
    {
        $contents = $this->encrypt_low($this->base64url_decode($hashKey),$this->encrypt_key);
        return $contents;
    }

    // 使用id生成token
    protected function id2token($id)
    {
        $str = sprintf("%s:%u:%u:%u",$this->token_prefix,time(), rand(1, 99999), $id);
        return $this->encrypt($str);
    }

    // 使用token还原id
    protected function token2id($token)
    {
        if(empty($token)) throw new \Exception('The token can not empty');

        $token =    $this->decrypt($token);
        $list =     explode(':',$token);
        if(count($list) != 4) throw new \Exception('The token format is wrong! 0x01');
        list($prefix, $time, $rand, $id) = $list;
        if(empty($prefix) || empty($time) || empty($rand) || empty($id)) throw new \Exception('The token format is wrong! 0x02');
        if($prefix != $this->token_prefix) throw new \Exception('The token format is wrong! 0x03');
        if((is_numeric($time) == false) || (is_numeric($rand) == false) || (is_numeric($id) == false)) throw new \Exception('The token format is wrong! 0x04');
        if($rand > 99999) throw new \Exception('The token format is wrong! 0x05');
        return $id;
    }
}