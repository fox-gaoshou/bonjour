<?php


namespace bonjour\lib;


class lib_process
{
    /**
     * 检查进程是否存在
     *
     * @param string            $process_name
     * 进程名称
     *
     * @return boolean
     * */
    public function has_process(string $process_name)
    {
        $cmd = sprintf('ps axu|grep "%s"|grep -v "grep"|wc -l',$process_name);
        if((int)shell_exec($cmd) >= 1) return true;
        return false;
    }
}