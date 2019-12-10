<?php


namespace bonjour\format\res;


/**
 *
 * @property \mysqli_result     $qry
 * @property \mysqli_stmt       $stmt
 *
 * @property int                $affected_rows
 * @property int                $insert_id
 *
 * */
class res_mysql extends res_basic
{
    public $qry;
    public $stmt;
    public $affected_rows;
    public $insert_id;

    public function __construct(int $code=0, string $message='', $data='', $log='')
    {
        parent::__construct('mysql', $code, $message, $data, $log);
    }
}