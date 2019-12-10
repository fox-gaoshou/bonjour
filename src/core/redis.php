<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/6
 * Time: 10:43
 */

namespace bonjour\core;


class redis
{
    /* @var \Redis      $ins */
    public              $ins;
    /* @var \Redis[]    $inss */
    private             $inss;
    private             $connection_id;
    // ------------------------------------------------------------------------------------------------------------------------------
    public function __construct()
    {
        $this->ins =        null;
        $this->inss =       array();
    }
    public function __destruct()
    {
        $this->close_all();
    }
    // ------------------------------------------------------------------------------------------------------------------------------
    public function get_conf(int $id)
    {
        $conf = bonjour::$conf->get_config('redis.ini');
        if(isset($conf[$id]) == false) throw new \Exception('the id conf of database is not setting!');
        return $conf[$id];
    }
    // ------------------------------------------------------------------------------------------------------------------------------
    /**
     * 单例模式，连接redis，返回连接的对象
     *
     * @param $id
     * 根据配置文件，连接到指定的redis
     *
     * @return \Redis
     *
     * @throws
     * 如果redis连接失败，会直接抛出异常
     * */
    public function connect($id=0)
    {
        $conf = $this->get_conf($id);
        if(isset($this->inss[$id]) == false)
        {
            $this->inss[$id] = new \Redis();
            $this->inss[$id]->connect($conf['ip'],$conf['port']);
            if(!empty($conf['password']))
            {
                $this->inss[$id]->auth($conf['password']);
            }
        }
        $this->connection_id = $id;
        return $this->ins = &$this->inss[$id];
    }
    public function pconnect($id=0)
    {
        $conf = $this->get_conf($id);
        if(isset($this->inss[$id]) == false)
        {
            $this->inss[$id] = new \Redis();
            $this->inss[$id]->pconnect($conf['ip'],$conf['port']);
            if(!empty($conf['password']))
            {
                $this->inss[$id]->auth($conf['password']);
            }
        }
        return $this->ins = &$this->inss[$id];
    }
    public function close($id=0)
    {
        if(!empty($this->inss[$id]))
        {
            $this->inss[$id]->close();
            unset($this->inss[$id]);
        }
    }
    public function close_all()
    {
        foreach ($this->inss as $id => $conn) $this->close($id);
    }
    // ------------------------------------------------------------------------------------------------------------------------------
    public function lock(string $lock_name,string $lock_val,int $timeout)
    {
        if($timeout < 0)
        {
            return $this->ins->set('lock:'.$lock_name,$lock_val,array('nx'));
        }else{
            return $this->ins->set('lock:'.$lock_name,$lock_val,array('nx','ex'=>$timeout));
        }
    }
    public function unlock(string $lock_name)
    {
        return $this->ins->del('lock:'.$lock_name);
    }
    public function hasLock($lockName)
    {
        return $this->ins->exists('lock:'.$lockName);
    }
    public function safeUnlock()
    {
    }

    /**
     * @param string            $process_name
     * @param array             $channels
     * @param array|string      $callback
     * @throws
     * */
    public function subscribe(string $process_name,array $channels,$callback)
    {
        if(php_sapi_name() != 'cli') throw new \Exception('不支持的运行模式',1);

        $ps_name = sprintf("php:%s",$process_name);
        $cmd = sprintf('ps axu|grep "%s"|grep -v "grep"|wc -l',$ps_name);
        if((int)shell_exec($cmd) >= 1) throw new \Exception(sprintf("进程已经存在!\n"),1);
        cli_set_process_title($ps_name);

        $b_res = $this->ins->subscribe($channels,$callback);
        if($b_res == false) throw new \Exception('订阅失败!',1);
    }
}