<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/7/2
 * Time: 14:31
 */

class common_error
{
    static public function cache_response_failed()
    {
        return feedback::pack('global','error_handler',1,'');
    }
}