<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/25
 * Time: 13:45
 */

namespace bonjour\format\network;


/**
 * @property string     $url
 * @property array      $headers
 * @property array      $body
 * @property string     $request_time
 * @property int        $request_timeout
 * */
class request
{
    public function __construct()
    {
        $this->headers = array();
    }
    public function add_header(string $key,string $val)
    {
        array_push($this->headers,sprintf("%s: %s",$key,$val));
    }
}