<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/18
 * Time: 22:07
 */

namespace bonjour\extend;


use bonjour\format\res\res_http;


class network
{
    /**
     * @param $ch
     * @param $request
     * @return res_http
     */
    public function http_get_init(&$ch,&$request)
    {
        if(is_object($request) == false) return new res_http(1,'request必须是一个对象');
        if(isset($request->url) == false) return new res_http(1,'request没有定义请求链接');
        if(isset($request->headers) == false) return new res_http(1,'request没有定义请求头');
        if(is_array($request->headers) == false) return new res_http(1,'request请求头必须是数组类型');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request->url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request->headers);
        // curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        // curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        // 文本形式返回内容
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);

        // 不验证SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        return new res_http();
    }

    /**
     * @param $ch
     * @param $request
     * @return res_http
     */
    public function http_post_init(&$ch,&$request)
    {
        if(is_object($request) == false) return new res_http(1,'request必须是一个对象');
        if(isset($request->url) == false) return new res_http(1,'request没有定义请求链接');
        if(isset($request->body) == false) return new res_http(1,'request没有定义body');
        if(isset($request->headers) == false) return new res_http(1,'request没有定义请求头');
        if(is_array($request->headers) == false) return new res_http(1,'request请求头必须是数组类型');

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$request->url);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$request->headers);
//        curl_setopt($ch,CURLOPT_TIMEOUT,$timeout);
        curl_setopt($ch,CURLOPT_POST ,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$request->body);

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

        return new res_http();
    }

    /**
     * @param $ch
     * @param $request
     * @param int $timeout
     * @return res_http
     */
    public function http_request(&$ch,&$request,$timeout=10)
    {
        $request->request_time = date('Y-m-d H:i:s');
        $request->request_timeout = $timeout;

        bonjour_memlog_append($request);

        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if(($errno != 0) || ($http_code != 200))
        {
            bonjour_memlog_append(array(
                "desc" =>           "请求发成异常",
                "http_code" =>      $http_code,
                "curl_errno" =>     $errno,
                "curl_error" =>     $error,
                "response" =>       $response
            ));
            return new res_http(1,'请求发成异常',sprintf("请求发生异常，错误代码:%s",$http_code));
        }

        bonjour_memlog_append(array("response" => $response));
        return new res_http(0,'',$response);
    }
}