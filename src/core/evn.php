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
 * @property int        $machine_number
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

    public $machine_number = 0;

    public function __construct(string $root)
    {
        $this->root =               $root;
        $this->start_time =         time();
        $this->machine_number =     BON_MACHINE_NUMBER;

        $this->dir_lib =            __DIR__ . "/../lib";
        $this->dir_traits =         __DIR__ . "/../traits";

        $this->dir_config =         BON_DIR_CONFIG;
        $this->dir_log =            BON_DIR_LOG;
        $this->dir_data =           BON_DIR_DATA;
    }
}