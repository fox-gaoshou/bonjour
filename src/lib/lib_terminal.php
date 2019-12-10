<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/7/4
 * Time: 14:34
 */

namespace bonjour\lib;


class lib_terminal
{
    public function terminal_type_params_list()
    {
        return array(
            ['title'=>'网页','value'=>br_terminal_type_web],
            ['title'=>'苹果','value'=>br_terminal_type_ios],
            ['title'=>'安卓','value'=>br_terminal_type_android],
        );
    }
}