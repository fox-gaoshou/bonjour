<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/6
 * Time: 11:51
 */

namespace bonjour\extend;


use bonjour\core\bonjour;

class order
{
    private $machine_number = 0;
    public function __construct()
    {
        $this->machine_number = bonjour::$evn->machine_number;
    }
    public function gen()
    {
        return bonjour_order_no($this->machine_number);
    }
}