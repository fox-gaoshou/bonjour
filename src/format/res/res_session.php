<?php


namespace bonjour\format\res;


class res_session extends res_basic
{
    public function __construct(int $code = 0, string $message = '', array $data = [], $log = '')
    {
        parent::__construct('session', $code, $message, $data, $log);
    }
}