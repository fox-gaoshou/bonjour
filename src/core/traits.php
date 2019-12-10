<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/6
 * Time: 15:01
 */

namespace bonjour\core;


class traits
{
    private $defined;
    public function __construct()
    {
        $this->defined = array();
    }
    public function include_model($file_name)
    {
        if(isset($this->defined['model']) == false) $this->defined['model'] = array();
        if(isset($this->defined['model'][$file_name])) return;
        $this->defined['model'][$file_name] = true;
        include bonjour::$evn->dir_traits . '/model/' . $file_name . '.php';
    }
    public function include_controller(string $file_name)
    {
        if(isset($this->defined['controller']) == false) $this->defined['controller'] = array();
        if(isset($this->defined['controller'][$file_name])) return;
        $this->defined['controller'][$file_name] = true;
        include bonjour::$evn->dir_traits . '/controller/' . $file_name . '.php';
    }
}