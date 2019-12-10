<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/6
 * Time: 11:24
 */

namespace bonjour\core;


/**
 * Class evn
 * @package bonjour\core
 *
 * @property string     $root
 * @property int        $start_time
 * @property string     $dir_config
 * @property string     $dir_log
 * @property string     $dir_lib
 * @property string     $dir_traits
 * @property string     $dir_data
 *
 * @property array      $core
 * @property array      $app
 * @property array      $common
 */
class evn
{
    public $root;
    public $start_time;
    public $config_dir;
    public $model_trait_dir;
    public $log_dir;
    public $lib_dir;
    public $traits_dir;

    public $app;
    public $core;
    public $common;

    public function __construct(string $root)
    {
        $this->root =               $root;
        $this->start_time =         time();

        $this->dir_config =         $this->root . '/config';
        $this->dir_lib =            $this->root . '/lib';
        $this->dir_traits =         $this->root . '/traits';
        $this->dir_log =            $this->root . '/../runtime/bonjour/log';
        $this->dir_data =           $this->root . '/../runtime/bonjour/data';

        $this->core =               include $this->dir_config . '/core.php';
        $this->app =                include $this->dir_config . '/app.php';
        $this->common =             include $this->dir_config . '/common.php';
    }
}