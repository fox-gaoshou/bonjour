<?php


namespace bonjour\format\mysql;


/**
 *
 * @property \mysqli_stmt       $stmt
 */
class prepare
{
    public $sql_type;
    public $sql;
    public $bind_param;
    public $stmt;
}