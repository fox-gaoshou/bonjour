<?php


namespace bonjour\format\res;


class res extends res_basic
{
    public $type;
    public $code;
    public $message;
    public $data;
    public $log;

    public function __construct(int $code=0, string $message='', $data = '', $log = '')
    {
        parent::__construct('normal', $code, $message, $data, $log);
    }
}