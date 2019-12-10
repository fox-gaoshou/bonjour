<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/5/6
 * Time: 10:52
 */

namespace bonjour\core;


use bonjour\format\mysql\prepare;
use bonjour\format\res\res;
use bonjour\format\res\res_mysql;

define('status_of_transaction_nothing',             0);
define('status_of_transaction_begin_success',       1);
define('status_of_transaction_begin_failed',        2);
define('status_of_transaction_commit_success',      3);
define('status_of_transaction_commit_failed',       4);
define('status_of_transaction_rollback_success',    5);
define('status_of_transaction_rollback_failed',     6);


class mysql
{
    /* @var \mysqli     $ins */
    public              $ins;
    /* @var \mysqli[]   $inss */
    private             $inss;
    // 当前连接ID
    private             $connection_id;
    public              $connections;

    public              $enable_warning = 1;
    public              $reference = 0;
    public function __construct()
    {
        $this->ins =            null;
        $this->inss =           array();
        $this->connection_id =  0;
        $this->connections =    array();
    }
    public function __destruct()
    {
        $this->close_all();
    }
    // ------------------------------------------------------------------------------------------------------------------------------
    public function get_conf(int $id)
    {
        $conf = bonjour::$conf->get_config('database.ini');
        if(isset($conf[$id]) == false) throw new \Exception('the id conf of database is not setting!');
        return $conf[$id];
    }
    // ------------------------------------------------------------------------------------------------------------------------------
    /**
     * 单例模式，连接数据库，返回连接的对象
     *
     * @param $id
     * 根据配置文件，连接到指定的数据库
     *
     * @return \mysqli
     *
     * @throws
     * 如果数据库连接失败，会直接抛出异常
     * */
    public function connect(int $id=0)
    {
        $this->connection_id = $id;
    }
    public function close($id=0)
    {
        if($this->inss[$id] !== null)
        {
            $this->inss[$id]->close();
            unset($this->inss[$id]);
        }
        if($this->connection_id == $id)
        {
            $this->ins = null;
        }
    }
    public function close_all()
    {
        foreach ($this->inss as $id => $ins) $this->close($id);
    }

    // 初始化连接的状态
    private function init_connection_status(int $id)
    {
        $this->connections[$id] = array(
            'reference' =>                  0,          // 事务的开启，预处理，都增加引用量
            'status_of_transaction' =>      0,          // 事务状态
        );
    }

    // 关闭当前连接
    private function close_current_connection()
    {
        // current connection id
        $id = $this->connection_id;

        $this->ins->close();
        unset($this->inss[$id]);
        unset($this->ins);

        $this->ins =        null;
        $this->inss[$id] =  null;

        $this->init_connection_status($id);
    }

    private function real_connect()
    {
        // current connection id
        $id = $this->connection_id;

        // init connection
        if(isset($this->connections[$id]) == false)
        {
            $this->init_connection_status($id);
        }

        // check the connection
        if($this->connections[$id]['reference'] != 0) throw new \Exception('重新连接失败，连接的引用量不为0');

        // get config
        $conf = $this->get_conf($id);

        // connect
        $this->inss[$id] = new \mysqli($conf['host'],$conf['user'],$conf['pass'],$conf['name']);
        if($this->inss[$id]->connect_errno != 0) throw new \Exception('连接DB失败!');
        $this->ins = &$this->inss[$id];
    }

    private function incr_reference()
    {
        // current connection id
        $id = $this->connection_id;

        $this->connections[$id]['reference'] += 1;
    }
    private function decr_reference()
    {
        // current connection id
        $id = $this->connection_id;

        $this->connections[$id]['reference'] -= 1;
    }

    public function get_reference()
    {
        // current connection id
        $id = $this->connection_id;

        return $this->connections[$id]['reference'];
    }

    // 设置当前连接 事务状态
    private function set_status_of_transaction(int $status)
    {
        $id = $this->connection_id;
        $this->connections[$id]['status_of_transaction'] = $status;
    }
    // 获取当前连接 事务状态
    private function get_status_of_transaction()
    {
        $id = $this->connection_id;
        if(isset($this->connections[$id]) == false) return 0;
        return $this->connections[$id]['status_of_transaction'];
    }

    /**
     * 事务操作
     * 开始事务失败，抛出错误，捕捉错误，rollback会关闭当前连接
     * 开始事务成功，正常运行
     *
     * 提交事务成功，正常运行
     * 提交事务失败，抛出错误，捕捉错误，rollback会关闭当前连接
     *
     * 进行会在下次调用 mysql 的时候，自动连接
     *
     * 所以如果事务出现错误，都会立即关闭当前连接
     * 事务后如果还存在代码，不要依赖先前的连接，无论先前的事务是正常，还是不正常，都不依赖先前的连接，必须要以全新的连接来对待编写代码
     * */
    public function begin_transaction()
    {
        // 检查是否已经连接
        if($this->ins === null) $this->real_connect();

        // 执行开启事务
        if($this->ins->begin_transaction() == true)
        {
            $this->reference++;
            $this->set_status_of_transaction(status_of_transaction_begin_success);
        }else{
            $this->set_status_of_transaction(status_of_transaction_begin_failed);
            throw new \Exception(sprintf('MYSQL执行发生错误，请重新尝试! %u',status_of_transaction_begin_failed));
        }
    }
    public function commit()
    {
        if($this->ins->commit() == true)
        {
            $this->set_status_of_transaction(status_of_transaction_commit_success);
        }else{
            $this->set_status_of_transaction(status_of_transaction_commit_failed);
            throw new \Exception(sprintf('MYSQL执行发生错误，请重新尝试! %u',status_of_transaction_commit_failed));
        }
    }
    public function rollback()
    {
        if($this->ins->rollback() == true)
        {
            $this->set_status_of_transaction(status_of_transaction_rollback_success);
        }else{
            $this->set_status_of_transaction(status_of_transaction_rollback_failed);
            $this->close_current_connection();
        }
    }
    // ------------------------------------------------------------------------------------------------------------------------------
    private     $sql_type =         null;
    public      $sql =              "";
    public      $bindParams =       null;
    public      $bind_param =       null;
    private     $count =            -1;
    private     $line =             null;

    private     $lines =            null;

    private function push_line()
    {
        if(is_null($this->count)) $this->count = -1;
        if(is_null($this->lines)) $this->lines = array();
        $this->count++;
        $this->lines[$this->count] = array();
        $this->line = &$this->lines[$this->count];
    }
    private function pop_line()
    {
        if($this->count)
        {
            $this->count--;
            $this->line =           &$this->lines[$this->count];
        }else{
            $this->sql_type =       null;
            $this->count =          null;
            $this->line =           null;
            $this->lines =          null;
            $this->bind_param =     null;
        }
    }

    private function push_param(&$value,$type)
    {
        if(is_null($this->bind_param))  $this->bind_param = array("");
        $this->bind_param[0] .=         $type;
        $this->bind_param[] =           &$value;
    }

    // ------------------------------------------------------------------------------------------------------------------------------

    private function prepare_low(&$stmt,string $sql,array $bind_param=null)
    {
        $stmt = @$this->ins->prepare($sql);

        if(($this->ins->errno == 2006) || ($this->ins->errno == 2013))
        {
            $this->real_connect();
            $stmt = @$this->ins->prepare($sql);
        }
        if($this->ins->errno == 0)
        {
            if(!empty($bind_param)) $stmt->bind_param(...$bind_param);
            $this->incr_reference();
            return new res_mysql();
        }else{
            return new res_mysql(1,'预处理错误!',[],[
                'errno' =>          $this->ins->errno,
                'error' =>          $this->ins->error,
                'sql' =>            $sql,
                'bind_params' =>    $bind_param
            ]);
        }
    }

    /**
     * @param \mysqli_stmt|null $stmt
     * @param string $sql
     * @param array|null $bind_param
     * @return res_mysql
     * @throws \Exception
     */
    public function prepare(&$stmt=null,string $sql='',array $bind_param=null)
    {
        if($this->ins === null) $this->real_connect();
        if(!empty($sql)) return $this->prepare_low($stmt,$sql,$bind_param);

        $pp = new prepare();
        $pp->sql_type = $this->sql_type;
        $pp->sql = implode(' ',$this->line);
        $pp->bind_param = $this->bind_param;

        $this->bind_param = null;
        $this->pop_line();

        $res = $this->prepare_low($pp->stmt,$pp->sql,$pp->bind_param);
        if($res->code) return $res;
        return new res_mysql(0,'',$pp);
    }

    /**
     * 预处理 关闭
     * 不管是原生的stmt 还是封装的 prepare 都需要使用此方法进行关闭
     *
     * @param object $obj
     * @throws \Exception
     */
    public function prepare_close(object $obj)
    {
        if(gettype($obj) != 'object') throw new \Exception("预处理关闭错误，目标不是对象类型!");
        if(isset($obj->stmt))
        {
            $obj->stmt->close();
        }else{
            $obj->close();
        }
        $this->decr_reference();
    }

    /**
     * @param prepare $pp
     * @return res_mysql
     * @throws \Exception
     */
    public function execute(&$pp)
    {
        $pp->stmt->execute();
        if($pp->stmt->errno) return new res_mysql(1,'执行发生错误!',null,[
            'errno' =>  $pp->stmt->errno,
            'error' =>  $pp->stmt->error,
            'pp' =>     $pp
        ]);

        $res = new res_mysql();

        switch ($pp->sql_type)
        {
            case "insert":
                $res->insert_id = $pp->stmt->insert_id;
                break;
            case "delete":
                $res->affected_rows = $pp->stmt->affected_rows;
                break;
            case "update":
                $res->affected_rows = $pp->stmt->affected_rows;
                break;
            case 'select':
                $res->qry = $pp->stmt->get_result();
                break;
            case 'count':
                $res->data = $pp->stmt->get_result()->fetch_assoc()["count"];
                break;
            default:
                throw new \Exception(sprintf("unknown sentence header %s",$pp->sql_type));
        }
        $pp->stmt->reset();

        return $res;
    }

    private function query_low(string $sql)
    {
        $qry = $this->ins->query($sql);

        if(($this->ins->errno == 2006) || ($this->ins->errno == 2013))
        {
            $this->real_connect();
            $qry = $this->ins->query($sql);
        }

        if($this->ins->errno == 0)
        {
            $res =                  new res_mysql();
            $res->qry =             $qry;
            $res->affected_rows =   $this->ins->affected_rows;
            $res->insert_id =       $this->ins->insert_id;
            return $res;
        }else{
            return new res_mysql(1,'执行发生错误!',null,[
                'sql' =>            $sql,
                'errno' =>          $this->ins->errno,
                'error' =>          $this->ins->error
            ]);
        }
    }

    /**
     * @param string $sql
     * @return res_mysql
     * @throws \Exception
     */
    public function query(string $sql='')
    {
        if($this->ins === null) $this->real_connect();
        if(!empty($sql)) return $this->query_low($sql);

        // 记录并清空当前占用的环境变量
        $sql_type =         $this->sql_type;
        $this->sql_type =   null;
        $sql =              implode(' ',$this->line);
        $bind_param =       $this->bind_param;
        $this->pop_line();

        /* @var \mysqli_stmt $stmt */
        $stmt =             null;
        $res = $this->prepare_low($stmt,$sql,$bind_param);
        if($res->code) return $res;
        $stmt->execute();
        $res = new res_mysql();
        switch ($stmt->errno)
        {
            case 0:
                $res->log =         array(
                    'sql' =>        $sql,
                    'bind_param' => $bind_param
                );
                break;
            case 1062:
                $res->code =        $stmt->errno;
                $res->message =     '数据的唯一性，发生冲突!';
                $res->log =         array(
                    'sql' =>        $sql,
                    'bind_param' => $bind_param,
                );
                $this->prepare_close($stmt);
                return $res;
            default:
                $res->code =        $stmt->errno;
                $res->message =     '执行发生错误!';
                $res->log =         array(
                    'sql' =>        $sql,
                    'bind_param' => $bind_param,
                    "stmt_errno" => $stmt->errno,
                    "stmt_error" => $stmt->error,
                );
                $this->prepare_close($stmt);
                return $res;
        }

        switch ($sql_type)
        {
            case "insert":
                $res->insert_id = $stmt->insert_id;
                break;
            case "delete":
                $res->affected_rows = $stmt->affected_rows;
                break;
            case "update":
                $res->affected_rows = $stmt->affected_rows;
                break;
            case "select":
                $res->insert_id = $stmt->insert_id;
                $res->qry = $stmt->get_result();
                break;
            case 'count':
                $res->data = $stmt->get_result()->fetch_assoc()["count"];
                break;
            default:
                throw new \Exception(sprintf("unknown sentence type %s",$sql_type));
        }

        $this->prepare_close($stmt);

        return $res;
    }

    public function insert($table_name,$any)
    {
        $this->push_line();

        $this->line[] = "insert";
        $this->line[] = "into {$table_name}";
        if($this->sql_type === null) $this->sql_type = 'insert';

        switch (gettype($any))
        {
            case "string":
                $this->line[] = $any;
                break;
            case "array":
                $this->line[] = $this->analysis_for_insert($any);
                break;
        }
        return $this;
    }

    public function on_duplicate_key_update($params)
    {
        $this->line[] = 'on duplicate key update';
        switch (gettype($params))
        {
            case "string":
                $this->line[] = $params;
                break;
            case "array":
                $this->line[] = $this->analysis_for_update($params);
                break;
        }
        return $this;
    }

    public function delete()
    {
        $this->push_line();

        $this->line[] = "delete";
        if($this->sql_type === null) $this->sql_type = 'delete';
        return $this;
    }

    public function select($any='*')
    {
        switch (gettype($any))
        {
            case 'NULL':
                $this->push_line();
                $this->line[] = "select";
                break;
            case "string":
                $this->push_line();
                if($this->sql_type === null) $this->sql_type = 'select';
                $this->line[] = "select";
                $this->line[] = $any;
                break;
            case "array":
                $this->push_line();
                if($this->sql_type === null) $this->sql_type = 'select';
                $temp_string = implode('`,`',$any);
                $temp_string = '`' . $temp_string . '`';
                $this->line[] = 'select';
                $this->line[] = $temp_string;
                break;
        }
        return $this;
    }
    public function select_count()
    {
        $this->push_line();

        $this->line[] = "select";
        $this->line[] = "coalesce(count(*),0) as `count`";
        if($this->sql_type === null) $this->sql_type = 'count';
        return $this;
    }

    public function from($from)
    {
        $this->line[] = "from";
        $this->line[] = $from;
        return $this;
    }
    public function from_paren($from)
    {
        if(is_string($from)) $this->line[] = "from ({$from})";
        return $this;
    }

    public function update($table_name)
    {
        $this->push_line();
        $this->line[] = "update";
        $this->line[] = $table_name;
        if($this->sql_type === null) $this->sql_type = 'update';
        return $this;
    }
    public function set($any)
    {
        switch (gettype($any))
        {
            case "NULL":
                break;
            case "string":
                $this->line[] = "set";
                $this->line[] = $any;
                break;
            case "array":
                $this->line[] = "set";
                $this->line[] = $this->analysis_for_update($any);
                break;
        }
        return $this;
    }

    public function as(string $str)
    {
        if(!empty($str))
        {
            $this->line[] = 'as';
            $this->line[] = $str;
        }
        return $this;
    }
    public function on($any)
    {
        switch (gettype($any))
        {
            case 'array':
                $this->line[] = 'on';
                $this->line[] = $this->analysis_on($any);
                break;
            case 'string':
                $this->line[] = 'on';
                $this->line[] = $any;
                break;
        }
        return $this;
    }
    // ------------------------------------------------------------------------------------------------------------------------------
    public function where($any=null)
    {
        switch (gettype($any))
        {
            case "NULL":
                break;
            case "string":
                $this->line[] = "where";
                $this->line[] = $any;
                break;
            case "array":
                if(count($any))
                {
                    $this->line[] = "where";
                    $this->line[] = $this->express($any);
                }
                break;
        }
        return $this;
    }

    public function for_update()
    {
        $this->line[] = 'for update';
        return $this;
    }

    public function group_by(string $key)
    {
        $this->line[] = "group by";
        $this->line[] = $key;
        return $this;
    }

    public function order_by($any)
    {
        switch (gettype($any))
        {
            case "string":
                $this->line[] = "order by";
                $this->line[] = $any;
                break;
            case "array":
                $str = "";
                foreach ($any as $row)
                {
                    if(count($row) != 2) throw new \Exception("br_mysql Error : Not support the order by format\n");
                    if(!in_array($row[1],array('asc','desc'))) throw new \Exception("br_mysql Error : Not support the order by sort");
                    $str = $str . ",? " . $row[1];
                    $this->push_param($row[0],'s');
                }
                $this->line[] = "order by";
                $this->line[] = ltrim($str,',');
                break;
        }

        return $this;
    }

    public function limit(int $num)
    {
        if(is_numeric($num))
        {
            $this->line[] = "limit";
            $this->line[] = $num;
        }
        return $this;
    }

    public function page(int $page_num,int $page_size=20)
    {
        if($page_num === null)                  return $this;
        if(is_numeric($page_num) == false)      $page_num = 0;
        if(is_numeric($page_size) == false)     $page_size = 20;
        $this->line[] = "limit";
        $this->line[] = sprintf("%s,%s",($page_num*$page_size),$page_size);
        return $this;
    }
    // ------------------------------------------------------------------------------------------------------------------------------
    public function left_join($table)
    {
        $this->line[] = 'left join';
        $this->line[] = $table;
        return $this;
    }
    // ------------------------------------------------------------------------------------------------------------------------------

    // 专用于解析 条件拼接的
    // 返回的是 字符串
    // 目前经过测试 可以拼接
    // [key , logical operator , value , value type]
    // [key , like , value , value type]
    // [key , between , value1 , value 2, values type]
    // [key , in , value(array) , values type]
    private function express($params,$logicalOperator="and")
    {
        $temp = array();
        foreach ($params as $row)
        {
            switch (gettype($row))
            {
                case 'string':
                    $temp[] = $row;
                    break;
                case 'array':
                    $fieldTemp = explode('.',$row[0]);
                    $fieldTempCount = count($fieldTemp);
                    if($fieldTempCount == 1)
                    {
                        $row[0] = sprintf('`%s`',$row[0]);
                    }else{
                        $row[0] = sprintf('%s.`%s`',$fieldTemp[0],$fieldTemp[1]);
                    }

                    switch ($row[1])
                    {
                        case "in":
                            if(is_string($row[2]))
                            {
                                $values = explode(',',$row[2]);
                                if(count($values) == 0) throw new \Exception("in 数据格式错误");
                                if(count($values) == 1)
                                {
                                    $this->push_param($values[0],$row[3]);
                                    $temp[] = sprintf("%s in ( ? )",$row[0]);
                                }else{
                                    $chars = "";
                                    foreach ($values as $value)
                                    {
                                        $chars .= ",?";
                                        $this->push_param($value,$row[3]);
                                    }
                                    $temp[] = sprintf("%s in ( %s )",$row[0],ltrim($chars,','));
                                }
                            } else if(is_array($row[2]))
                            {
                                $chars = "";
                                foreach ($row[2] as $key => $value)
                                {
                                    $chars .= ",?";
                                    $this->push_param($row[2][$key],$row[3]);
                                }
                                $temp[] = sprintf("%s in ( %s )",$row[0],ltrim($chars,','));
                            } else {
                                throw new \Exception("in 未知的数据类型\n");
                            }
                            break;
                        case "not in":
                            if(is_string($row[2]))
                            {
                                $values = explode(',',$row[2]);
                                if(count($values) == 0) throw new \Exception("in 数据格式错误");
                                if(count($values) == 1)
                                {
                                    $this->push_param($values[0],$row[3]);
                                    $temp[] = sprintf("%s not in ( ? )",$row[0]);
                                }else{
                                    $chars = "";
                                    foreach ($values as $value)
                                    {
                                        $chars .= ",?";
                                        $this->push_param($value,$row[3]);
                                    }
                                    $temp[] = sprintf("%s not in ( %s )",$row[0],ltrim($chars,','));
                                }
                            } else if(is_array($row[2]))
                            {
                                $chars = "";
                                foreach ($row[2] as $key => $value)
                                {
                                    $chars .= ",?";
                                    $this->push_param($row[2][$key],$row[3]);
                                }
                                $temp[] = sprintf("%s not in ( %s )",$row[0],ltrim($chars,','));
                            } else {
                                throw new \Exception("not in 未知的数据类型\n");
                            }
                            break;
                        case "between":
                            if(isset($row[4]) == false) throw new \Exception('between 没有定义数据类型');
                            $temp[] = sprintf("%s between ? and ?",$row[0]);
                            $this->push_param($row[2],$row[4]);
                            $this->push_param($row[3],$row[4]);
                            break;
                        case 'like':
                            $temp[] = sprintf('%s like ?',$row[0]);
                            $this->push_param($row[2],$row[3]);
                            break;
                        case "=":
                            if(count($row) == 3)
                            {
                                $temp[] = sprintf("%s = %s",$row[0],$row[2]);
                                break;
                            }
                        default:
                            $temp[] = sprintf("%s %s ?",$row[0],$row[1]);
                            if(($row[3] == 'i') && (is_numeric($row[2]) == false)) throw new \Exception("类型不匹配",br_errno_sql_query_error);
                            $this->push_param($row[2],$row[3]);
                            break;
                    }
                    break;
            }
        }

        return implode(" {$logicalOperator} ",$temp);
    }

    // 进行拼接sql的时候，key加上 ` 反单引号，而且支持字段前带表明
    private function key($key)
    {
        $temp = explode('.',$key);
        $count = count($temp);
        if($count == 1)
        {
            return sprintf('`%s`',$key);
        }else if($count == 2)
        {
            return sprintf('%s.`%s`',$temp[0],$temp[1]);
        }else{
            throw new \Exception('不支持的key格式');
        }
    }

    private function analysis_on(array $params)
    {
        $cache = array();
        foreach ($params as $row)
        {
            switch (count($row))
            {
                case 3:
                    // format ['table.xx','=','table.xx']
                    $cache[] = sprintf('%s=%s',$this->key($row[0]),$this->key($row[2]));
                    break;
                default:
                    throw new \Exception('on 不支持的操作!');
            }
        }
        return implode(',',$cache);
    }

    // 用于解析更新的参数组
    private function analysis_for_update(array $params)
    {
        $cache = array();
        foreach ($params as $row)
        {
            switch (count($row))
            {
                case 1:
                    break;
                case 3:
                    // 格式
                    // ['frozenMoney','=','money']
                    switch ($row[1])
                    {
                        case '=':
                            $cache[] = sprintf('%s=%s',$this->key($row[0]),$this->key($row[2]));
                            break;
                        default:
                            throw new \Exception('不支持的更新操作!');
                    }
                    break;
                case 4:
                    $key = $this->key($row[0]);
                    switch ($row[1])
                    {
                        case '+=':
                        case 'inc':
                        case 'incr':
                            $cache[] = sprintf('%s=%s+?',$key,$key);
                            break;
                        case '-=':
                        case 'dec':
                        case 'decr':
                            $cache[] = sprintf('%s=%s-?',$key,$key);
                            break;
                        case '=':
                            $cache[] = sprintf('%s=?',$key);
                            break;
                        default:
                            throw new \Exception("UPDATE 不支持的运算符! %s",$row[1]);
                            break;
                    }
                    $this->push_param($row[2],$row[3]);
                    break;
            }
        }
        return implode(',',$cache);
    }

    //['key','value','type']
    private function analysis_for_insert(array $params)
    {
        $keys =     array();
        $chars =    array();
        foreach ($params as $row)
        {
            if(count($row) != 3) throw new \Exception(sprintf("%s 不支持的数据格式",$this->line[0]));
            $fieldTemp = explode('.',$row[0]);
            $fieldTempCount = count($fieldTemp);
            if($fieldTempCount == 1)
            {
                $keys[] = sprintf('`%s`',$row[0]);
            }else{
                $keys[] = sprintf('%s.`%s`',$fieldTemp[0],$fieldTemp[1]);
            }
            $chars[] = '?';
            $this->push_param($row[1],$row[2]);
        }
        return sprintf("( %s ) values ( %s )",implode(',',$keys),implode(',',$chars));
    }
    // ------------------------------------------------------------------------------------------------------------------------------
    public function analysis(array $arr,string $type="",string $logicalOperator="and")
    {
        if(is_array($arr) == false) throw new \Exception("不支持的数据");

        if(empty($type)) goto typeIsEmpty;
        if($type == "where") goto typeIsWhere;
        if($type == "express") goto typeIsWhere;


        typeIsWhere:
        // data format
        // [key , logical operator , value , value type]
        // [key , between , value1 , value 2 , values type]
        return $this->express($arr,$logicalOperator);

        typeIsEmpty:
        switch ($this->line[0])
        {
            case "insert":
                // data format [key,value,value type]
                $keys =     array();
                $chars =    array();
                foreach ($arr as $row)
                {
                    if(count($row) != 3)            throw new \Exception(sprintf("%s 不支持的数据格式",$this->line[0]));
                    $keys[] =                       $row[0];
                    $chars[] =                      '?';
                    $this->push_param($row[1],$row[2]);
                }
                return sprintf("( %s ) values ( %s )",implode(',',$keys),implode(',',$chars));
                break;
            case "delete":
                break;
            case "update":
                // data format [key,logical operator,value,value type]
                $temp = array();
                foreach ($arr as $row)
                {
                    if(count($row) != 4) throw new \Exception(sprintf("%s 不支持的数据格式",$this->line[0]));
                    switch ($row[1])
                    {
                        case "+=":
                        case "inc":
                        case "incr":
                            $temp[] = sprintf("%s=%s+?",$row[0],$row[0]);
                            break;
                        case "-=":
                        case "dec":
                        case "decr":
                            $temp[] = sprintf("%s=%s-?",$row[0],$row[0]);
                            break;
                        case "=":
                            $temp[] = sprintf("%s=?",$row[0]);
                            break;
                        default:
                            throw new \Exception("UPDATE 不支持的运算符!");
                    }
                    $this->push_param($row[2],$row[3]);
                }
                return implode(',',$temp);
                break;
            case "select":
                break;
        }
    }
    // ------------------------------------------------------------------------------------------------------------------------------
}