<?php


namespace bonjour\format\res;


class res_http extends res_basic
{
    public function __construct(int $code=0, string $message='', $data = '', $log = '')
    {
        parent::__construct('http_request', $code, $message, $data, $log);
    }
}