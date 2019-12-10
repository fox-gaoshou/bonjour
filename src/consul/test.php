<?php

$url = "http:127.0.0.1:8301/v1/agent/service/register";
$header = [];
$body = '{
"ID": "user", //服务id
"Name": "username", //服务名
"Tags": [

"primary",
"v1"
],
"Address": "127.0.0.1", //服务的ip
"Port": 8000, //服务的端口
"EnableTagOverride": false,
"Check": { //健康检查部分

"DeregisterCriticalServiceAfter": "90m",
"HTTP": "127.0.0.1/rpc/health.php", //指定健康检查的URL，调用后只要返回20X，consul都认为是健康的
"Interval": "10s"   //健康检查间隔时间，每隔10s，调用一次上面的URL
}
}';

$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_CUSTOMREQUEST,"PUT");
curl_setopt($ch,CURLOPT_HEADER,false);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
$response = curl_exec($ch);
$errno = curl_errno($ch);

var_dump($errno);
var_dump($response);