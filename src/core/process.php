<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/4/19
 * Time: 15:24
 */

namespace bonjour\core;


/**
 * 进程抽象类
 *
 * config       进程启动前 回调
 * startup      进程启动 回调
 * shutdown     进程结束 回调
 * main         进程程序入口
 *
 * @property string         $process_name
 * 进程的名称
 * 进程启动时，会先检查系统的进程列表，防止重复启动进程
 * @property int            $max_process
 * 进程数量
 *
 * */
abstract class process
{
    public $master_pid;
    public $pid;

    // 进程的名字前缀
    public $process_name;
    // 进程数量
    public $max_process;
    // 所有进程的信息
    public $works;

    abstract public function config();
    abstract public function startup(\swoole_process &$worker);
    abstract public function shutdown(\swoole_process &$worker);
    abstract public function main(\swoole_process &$worker);

    public function __construct()
    {
        try
        {
            $this->master_pid = getmypid();
            if(empty($this->master_pid)) throw new \Exception("Error: can not get the master pid!\n");
            $this->pid = 0;
            $this->process_name = 'default-name';
            $this->max_process = 1;
            $this->works = array();
            $this->run();
            $this->processWait();
        } catch (\Exception $e) {
            die('ALL ERROR: '.$e->getMessage());
        }
    }

    private function run()
    {
        // 调用实例的配置
        $this->config();

        // 主进程的名字
        $process_name = sprintf('php-%s:master',$this->process_name);

        // 检查进行是否已经启动
        $cmd = sprintf('ps axu|grep "%s"|grep -v "grep"|wc -l',$process_name);
        if((int)shell_exec($cmd) >= 1) exit(sprintf("进程已经存在!\n"));

        // 设置主进程名字
        swoole_set_process_name($process_name);

        // 创建进程
        for ($i=0;$i<$this->max_process;$i++) $this->create_process($i);
    }

    private function create_process($index = null)
    {
        if($index === null) throw new \Exception("进程索引值不能为NULL" . PHP_EOL);
        $process = new \swoole_process(function(\swoole_process $worker) use ($index) {
            ini_set('default_socket_timeout',-1);
            $this->startup($worker);
            swoole_set_process_name(sprintf('php-%s:%u',$this->process_name,$index));
            while(1)
            {
                if(!\swoole_process::kill($this->master_pid,0))
                {
                    $this->shutdown($worker);
                    $worker->exit();
                }
                $this->main($worker);
            }
        });
        $pid = $process->start();
        $this->works[$index] = $pid;
        return $this->pid = $pid;
    }

    private function reboot_process($ret)
    {
        $pid = $ret['pid'];
        $index = array_search($pid, $this->works);
        if($index !== false)
        {
            $index = intval($index);
            $newPid = $this->create_process($index);
            echo "重新启动进程：{$index} = {$newPid}" . PHP_EOL;
            return;
        }
        throw new \Exception('reboot process error: no pid' . PHP_EOL);
    }

    private function processWait()
    {
        while(1)
        {
            if(count($this->works))
            {
                $ret = \swoole_process::wait();
                if ($ret) $this->reboot_process($ret);
            }else{
                break;
            }
        }
    }
}