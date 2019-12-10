<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2018/12/8
 * Time: 14:03
 */

namespace bonjour\lib;


use bonjour\format\res\res;

class lib_network
{
    public function formatGet(array $header,string $url)
    {
        return array('request_header'=>$header,'request_url'=>$url);
    }
    public function formatPost()
    {

    }

    public function httpGetInit(&$ch,&$request,$timeout)
    {
        if(is_array($request) == false) throw new \Exception("the request must be array");
        if(isset($request["request_url"]) == false) throw new \Exception("the request loss the request_url");
        if(isset($request["request_header"]) == false) throw new \Exception("the request loss the request_header");
        if(is_array($request["request_header"]) == false) throw new \Exception("the request header must be an array");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request["request_url"]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request["request_header"]);
//        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        // 文本形式返回内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

        // 不验证SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    public function httpPostInit(&$ch,&$request,$timeout)
    {
        if(isset($request["request_url"]) == false) throw new \Exception("the request loss the request_url");
        if(isset($request["request_header"]) == false) throw new \Exception("the request loss the request_header");
        if(is_array($request["request_header"]) == false) throw new \Exception("the request header must be an array");
        if(isset($request["request_data"]) == false) throw new \Exception("the request loss the request_data");

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$request["request_url"]);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$request["request_header"]);
        curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
        curl_setopt($ch,CURLOPT_POST ,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$request["request_data"]);

        // 文本形式返回内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

        // 不验证SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //在HTTP请求中包含一个"User-Agent: "头的字符串。
//        curl_setopt($ch,CURLOPT_USERAGENT,'SSTS Browser/1.0');
//        curl_setopt($ch,CURLOPT_ENCODING,'gzip');
//        curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0.1)'); // 模拟用户使用的浏览器
    }

    public function httpRequest(&$ch,&$request)
    {
        /* @var \bonjour\core\res $res */
        $res = (object)array('errno'=>0);

        $request["request_time"] = date("Y-m-d H:i:s");
        br_mem_log_append($request);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if(($errno != 0) || ($httpCode != 200))
        {
            $res->code =           br_errno_http_request_error;
            $res->msg =             sprintf("请求发生异常，错误代码:%s",$httpCode);
            br_mem_log_append(array(
                "desc" =>           "请求发成异常",
                "http_code" =>      $httpCode,
                "curl_errno" =>     $errno,
                "curl_error" =>     $error,
                "response" =>       $response
            ));
            return $res;
        }

        $res->data = &$response;
        br_mem_log_append($response);
        return $res;
    }



    public function httpGetRequest(&$ch,&$request)
    {
        $res = new res();

        $request["request_time"] = date("Y-m-d H:i:s");
        br_mem_log_append($request);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if(($errno != 0) || ($httpCode != 200))
        {
            $res->code =           br_errno_http_request_error;
            $res->msg =             sprintf("请求发生异常，错误代码:%s",$httpCode);
            br_mem_log_append(array(
                "desc" =>           "请求发成异常",
                "http_code" =>      $httpCode,
                "curl_errno" =>     $errno,
                "curl_error" =>     $error,
                "response" =>       $response
            ));
            return $res;
        }
//        var_dump($response); die;
        $res->data = &$response;
//        br_mem_log_append($response);
        return $res;
    }
//    public function httpGetRequest1($url,$timeout = 30,array $header = array())
//    {
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $url);
//        // 添加头信息
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
//        // CURLINFO_HEADER_OUT选项可以拿到请求头信息
//        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
//        // 设置操作超时时间
//        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
//        // 设置以文本流形式输出
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//        // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
//        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
//        // 不验证SSL
////        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
////        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//        $response = curl_exec($ch);
//        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
//        $errno = curl_errno($ch);
//
//        curl_close($ch);
//        return $response;
//    }

    public function httpGet(string $url,array $header=array(),int $timeout=30)
    {
        /* @var \bonjour\core\res $res */
        $res = (object)array('errno'=>0);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        // 设置文本流形式返回数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

        // 不验证SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if(($errno != 0) || ($httpCode != 200))
        {
            $res->code =   br_errno_http_request_error;
            $res->msg =     sprintf("url:%s httpCode:%s error:%s\n",$url,$httpCode,$error);
            return $res;
        }

        $res->data = &$response;
        return $res;
    }

    public function httpPostRequest($url,$data,$timeout=30,array $header = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // 添加头信息
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // 设置操作超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        // 设置post
        curl_setopt($ch, CURLOPT_POST, true);
        // post数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // 设置以文本流形式输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}