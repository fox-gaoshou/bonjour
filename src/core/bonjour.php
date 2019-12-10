<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/13
 * Time: 19:28
 */

namespace bonjour\core;


class bonjour
{
    /* @var \DI\Container       $container */
    static public               $container;
    /* @var evn                 $evn */
    static public               $evn;
    /* @var conf                $conf */
    static public               $conf;

    /* @var traits              $traits */
    static public               $traits;

    /* @var mysql               $mysql */
    static public               $mysql;
    /* @var redis               $redis */
    static public               $redis;

    /* @var ext                 $ext */
    static public               $ext;

    static public function init(string $root)
    {
        foreach (glob($root . '/define/*.php') as $define_file) require_once $define_file;

        self::$container =      new \DI\Container();
        self::$evn =            new evn($root);
        self::$conf =           new conf();
        self::$traits =         new traits();

        self::$mysql =          new mysql();
        self::$redis =          new redis();

        self::$ext =            new ext();
    }
}