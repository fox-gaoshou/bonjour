<?php


namespace bonjour\format\res;


class res_basic
{
    public $type;
    public $code;
    public $message;
    public $data;
    public $log;

    public function __construct(string $type,int $code,string $message,$data='',$log='')
    {
        $this->type =       $type;
        $this->code =       $code;
        $this->message =    $message;
        $this->data =       $data;
        $this->log =        $log;
    }
}