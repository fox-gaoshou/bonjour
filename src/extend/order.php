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
    private $machineNumber = 0;
    public function __construct()
    {
        $this->machineNumber = 0;
        $this->machineNumber = bonjour::$conf->machine('number');
    }
    public function d15()
    {
        return brOrderNo($this->machineNumber);
    }
}