<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/29
 * Time: 13:51
 */

namespace bonjour\lib;

class oss
{
    public $host;
    public $accessKeyId;
    public $accessKeySecret;
    public function __construct($host,$accessKeyId,$accessKeySecret)
    {
        $this->host = $host;
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
    }
    private function gmt_iso8601($time) {
        $dtStr = date("c", $time);
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }

    public function genResponse()
    {
        // ------------------------------------------------------------------------------------------------------------------------------
        // 配置参数
        // expire 设置该policy超时时间�?0s. 即这个policy过了这个有效时间，将不能访问
        $now = time();
        $expire = 30;
        $end = $now + $expire;
        $expiration = $this->gmt_iso8601($end);

        // 最大文件大�?用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition;

        // 表示用户上传的数�?必须是以$dir开�? 不然上传会失�?这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0=>'starts-with', 1=>'$key', 2=>$this->dir);
        $conditions[] = $start;

        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
        // ------------------------------------------------------------------------------------------------------------------------------
        // 生成签名
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret, true));

        // 生成客户端需要的参数，然后返回给客户端，可以直接上传
        $response = array();
        $response['accessid'] = $this->accessKeyId;
        $response['host'] = $this::host;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['dir'] = $this->dir;
        return json_encode($response);
    }

    public function genResponseWithCallback($callbackUrl,$dir)
    {
        // ------------------------------------------------------------------------------------------------------------------------------
        // 设置回调连接
        $callback_param = array('callbackUrl'=>$callbackUrl,
            'callbackBody'=>'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType'=>"application/x-www-form-urlencoded");
        $callback_string = json_encode($callback_param);
        $base64_callback_body = base64_encode($callback_string);
        // ------------------------------------------------------------------------------------------------------------------------------
        // 配置参数
        // expire 设置该policy超时时间�?0s. 即这个policy过了这个有效时间，将不能访问
        $now = time();
        $expire = 30;
        $end = $now + $expire;
        $expiration = $this->gmt_iso8601($end);

        // 最大文件大小.用户可以自己设置
        $condition = array(0=>'content-length-range', 1=>0, 2=>1048576000);
        $conditions[] = $condition;

        // 表示用户上传的数据，必须是以$dir开始，不然上传会失败，这一步不是必须项，只是为了安全起见，防止用户通过policy上传到别人的目录。
        $start = array(0=>'starts-with', 1=>'$key', 2=>$dir);
        $conditions[] = $start;
        // ------------------------------------------------------------------------------------------------------------------------------`
        $arr = array('expiration'=>$expiration,'conditions'=>$conditions);
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret, true));

        $response = array();
        $response['accessid'] = $this->accessKeyId;
        $response['host'] = $this->host;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $base64_callback_body;
        $response['dir'] = $dir;  // 这个参数是设置用户上传文件时指定的前缀。

        return json_encode($response);
    }
}