<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/18
 * Time: 19:13
 */

spl_autoload_register(function ($class) {
    $FilePath = __DIR__;
    $vendor = explode('\\',$class);
    for($i=1;$i<count($vendor);$i++) $FilePath = $FilePath . '/' . $vendor[$i];
    $FilePath .= '.php';
    if (file_exists($FilePath)) include $FilePath;
});

\bonjour\core\bonjour::init(__DIR__);