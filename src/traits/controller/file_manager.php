<?php


namespace bonjour\traits\controller;


use bonjour\core\bonjour;
use bonjour\format\res\res;
use bonjour\model\m_file_manager;
use Respect\Validation\Validator;


define('bonjour_file_manager_type_dir',0);
define('bonjour_file_manager_type_dir_shortcut',2);
define('bonjour_file_manager_type_file',3);
define('bonjour_file_manager_type_file_shortcut',4);


///**
// * @property \bonjour\lib\lib_session $session
// * */
//trait file_manager_delete
//{
//    public function delete()
//    {
//        $res =                                  new res();
//        $ret_data =                             array();
//        //-------------------------------------------------------------------------------------------------------------------------------
//        $parent_id =                            (int)($_POST['parent_id'] ?? exit);
//        $id =                                   (int)($_POST['id'] ?? exit);
//        //-------------------------------------------------------------------------------------------------------------------------------
//        try
//        {
//            bonjour::$mysql->begin_transaction();
//
//            $res = bonjour::$container->get(m_c_file_manager::class)->delete_by_parent_id_and_id($parent_id,$id);
//            if($res->code) throw new \Exception('',1);
//
//            bonjour::$mysql->commit();
//        }catch (\Exception $e)
//        {
//            bonjour::$mysql->rollback();
//            if($e->getCode() == 1) goto operation_failed;
//            $res = new res(1,'执行发生异常',$e->getMessage());
//            goto operation_failed;
//        }
//        //-------------------------------------------------------------------------------------------------------------------------------
//        operation_finish:
//        $this->session->echoAjax(0,'删除成功',$ret_data);
//        exit;
//        //-------------------------------------------------------------------------------------------------------------------------------
//        operation_failed:
//        $this->session->echoAjaxLog($res);
//        exit;
//    }
//}



/**
 * @property string $database_table
 * */
trait file_manager
{
    // 排序
    public function sort()
    {
        $ret_data =                             array();
        //-------------------------------------------------------------------------------------------------------------------------------
        $sort =                                 $_POST['sort'] ?? exit;
        //-------------------------------------------------------------------------------------------------------------------------------
        if(isset($this->database_table) == false)
        {
            $res = new res(1,'没有定义database_table');
            goto operation_failed;
        }
        bonjour::$container->get(m_file_manager::class)->set_database_table($this->database_table);
        //-------------------------------------------------------------------------------------------------------------------------------
        $id_list = explode(',',$sort);
        if(is_array($id_list) == false)
        {
            $res = new res(1,'排序的数据有错误!');
            goto operation_failed;
        }
        if(count($id_list) == 0)
        {
            $res = new res(1,'需要排序的数据不能为空');
            goto operation_failed;
        }
        //-------------------------------------------------------------------------------------------------------------------------------

        // 获取父级ID
        $res = bonjour::$container->get(m_file_manager::class)->select_by_id('parent_id',$id_list[0],true);
        if($res->code) goto operation_failed;
        $parent_id = $res->qry->fetch_assoc()['parent_id'];

        try
        {
            bonjour::$mysql->begin_transaction();

            // 递归更新父级的更新时间
            $res = bonjour::$container->get(m_file_manager::class)->recursive_update_parent_last_update_time($parent_id);
            if($res->code) throw new \Exception('res');

            // 更新sort
            foreach ($id_list as $index => $id)
            {
                $res = bonjour::$container->get(m_file_manager::class)->update_sort_by_id($id,$index+1);
                if($res->code) throw new \Exception('res');
            }

            bonjour::$mysql->commit();
        }catch (\Exception $e)
        {
            bonjour::$mysql->rollback();
            if($e->getMessage() == 'res')
            {
                goto operation_failed;
            }else{
                $res = new res(1,'执行发生异常','',$e);
                goto operation_failed;
            }
        }
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_finish:
        bonjour::$ext->session->echo(new res(0,'保存排序成功',$ret_data));
        exit;
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_failed:
        bonjour::$ext->session->echo($res);
        exit;
    }

    // 添加目录
    public function insert_dir()
    {
        $ret_data =                             array();
        //-------------------------------------------------------------------------------------------------------------------------------
        $parent_id =                            (int)($_POST['parent_id'] ?? exit);
        $name =                                 $_POST['name'] ?? exit;
        $desc =                                 $_POST['desc'] ?? exit;
        $icon =                                 $_POST['icon'] ?? exit;
        $web_table_template =                   $_POST['web_table_template'] ?? '';
        //-------------------------------------------------------------------------------------------------------------------------------
        if(isset($this->database_table) == false)
        {
            $res = new res(1,'没有定义database_table');
            goto operation_failed;
        }
        bonjour::$container->get(m_file_manager::class)->set_database_table($this->database_table);
        //-------------------------------------------------------------------------------------------------------------------------------
        $res = bonjour::$container->get(m_file_manager::class)->select_by_id('*',$parent_id,true);
        if($res->code) goto operation_failed;
        $parent_data = $res->qry->fetch_assoc();
        if($parent_data['type'] != bonjour_file_manager_type_dir)
        {
            $res = new res(1,'父级不是一个目录，不能添加数据');
            goto operation_failed;
        }

        // 获取排序的值
        $res = bonjour::$container->get(m_file_manager::class)->select_data_of_max_sort([['parent_id','=',$parent_data['id'],'i']]);
        if($res->code) goto operation_failed;
        $sort = $res->data;

        try
        {
            bonjour::$mysql->begin_transaction();

            // 增加上层的引用量
            $res = bonjour::$container->get(m_file_manager::class)->incr_reference_by_id($parent_data['id']);
            if($res->code) throw new \Exception('res');

            // 递归更新上层的 更新时间
            $res = bonjour::$container->get(m_file_manager::class)->recursive_update_parent_last_update_time($parent_data['id']);
            if($res->code) throw new \Exception('res');

            // 插入数据
            $res = bonjour::$container->get(m_file_manager::class)->insert(
                $parent_id,
                $parent_data['level'] + 1,
                $sort,
                '',
                0,
                bonjour_file_manager_type_dir,
                $name,
                $desc,
                $icon,
                $web_table_template
            );
            if($res->code) throw new \Exception('res');

            bonjour::$mysql->commit();
        }catch (\Exception $e)
        {
            bonjour::$mysql->rollback();
            if($e->getMessage() == 'res')
            {
                goto operation_failed;
            }else{
                $res = new res(1,'执行发生异常','',$e);
                goto operation_failed;
            }
        }
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_finish:
        bonjour::$ext->session->echo(new res(0,'添加成功',$ret_data));
        exit;
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_failed:
        bonjour::$ext->session->echo($res);
        exit;
    }

    // 更新目录
    public function update_dir()
    {
        $ret_data =                             array();
        //-------------------------------------------------------------------------------------------------------------------------------
        $id =                                   (int)($_POST['id'] ?? exit);
        $parent_id =                            $_POST['parent_id'] ?? null;
        $name =                                 $_POST['name'] ?? exit;
        $desc =                                 $_POST['desc'] ?? exit;
        $icon =                                 $_POST['icon'] ?? exit;
        $web_table_template =                   $_POST['web_table_template'] ?? exit;
        //-------------------------------------------------------------------------------------------------------------------------------
        if(isset($this->database_table) == false)
        {
            $res = new res(1,'没有定义database_table');
            goto operation_failed;
        }
        bonjour::$container->get(m_file_manager::class)->set_database_table($this->database_table);
        //-------------------------------------------------------------------------------------------------------------------------------
        $res = bonjour::$container->get(m_file_manager::class)->select_by_id('*',$id,true);
        if($res->code) goto operation_failed;
        $item_data = $res->qry->fetch_assoc();

        if($item_data['type'] != bonjour_file_manager_type_dir)
        {
            $res = new res(1,'目标不是一个目录类型，更新失败!');
            goto operation_failed;
        }

        if(Validator::intVal()->min(1)->validate($parent_id))
        {
            $res = bonjour::$container->get(m_file_manager::class)->select_by_id('`type`',$parent_id,true);
            if($res->code) goto operation_failed;
            $temp = $res->qry->fetch_assoc();
            if($temp['type'] != bonjour_file_manager_type_dir)
            {
                $res = new res(1,'父级ID，不是一个目录类型');
                goto operation_failed;
            }
        }

        try
        {
            bonjour::$mysql->begin_transaction();

            // 获取父级ID
            $res = bonjour::$container->get(m_file_manager::class)->select_by_id('`parent_id`',$id,true);
            if($res->code) throw new \Exception('res');
            $parent_id = $res->qry->fetch_assoc()['parent_id'];

            // 更新目标数据
            $res = bonjour::$container->get(m_file_manager::class)->update_by_id($id,$name,$desc,$icon,$web_table_template);
            if($res->code) throw new \Exception('res');

            $res = bonjour::$container->get(m_file_manager::class)->recursive_update_parent_last_update_time($parent_id);
            if($res->code) throw new \Exception('res');

            // 如果需要更新上级ID
            if(Validator::intVal()->min(1)->validate($parent_id))
            {
                // 减少原父级引用量
                $res = bonjour::$container->get(m_file_manager::class)->decr_reference_by_id($item_data['parent_id']);
                if($res->code) throw new \Exception('res');

                // 增加新父级引用量
                $res = bonjour::$container->get(m_file_manager::class)->incr_reference_by_id($parent_id);
                if($res->code) throw new \Exception('res');

                // 更新上级ID
                $res = bonjour::$container->get(m_file_manager::class)->update_parent_id_by_id($id,$parent_id);
                if($res->code) throw new \Exception('res');

                // 递归更新上级的更新时间
                $res = bonjour::$container->get(m_file_manager::class)->recursive_update_parent_last_update_time($parent_id);
                if($res->code) return $res;
            }

            bonjour::$mysql->commit();
        }catch (\Exception $e)
        {
            bonjour::$mysql->rollback();
            if($e->getCode() == 1) goto operation_failed;
            $res = new res(1,'执行发生异常',$e->getMessage());
            goto operation_failed;
        }
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_finish:
        bonjour::$ext->session->echo(new res(0,'更新成功!',$ret_data));
        exit;
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_failed:
        bonjour::$ext->session->echo($res);
        exit;
    }

    public function delete_dir()
    {
        $ret_data =                             array();
        //-------------------------------------------------------------------------------------------------------------------------------
        $id =                                   (int)($_POST['id'] ?? exit);
        //-------------------------------------------------------------------------------------------------------------------------------
        if(isset($this->database_table) == false)
        {
            $res = new res(1,'没有定义database_table');
            goto operation_failed;
        }
        bonjour::$container->get(m_file_manager::class)->set_database_table($this->database_table);
        //-------------------------------------------------------------------------------------------------------------------------------
        $res = bonjour::$container->get(m_file_manager::class)->select_by_id('*',$id,true);
        if($res->code) goto operation_failed;
        $item_data = $res->qry->fetch_assoc();
        if($item_data['type'] != bonjour_file_manager_type_dir)
        {
            $res = new res(1,'目标不是一个目录，删除失败!');
            goto operation_failed;
        }

        try
        {
            bonjour::$mysql->begin_transaction();

            // 删除目标数据
            $res = bonjour::$container->get(m_file_manager::class)->delete_by_id_and_check_reference($item_data['id']);
            if($res->code) throw new \Exception('res');

            // 减少上层的引用量
            $res = bonjour::$container->get(m_file_manager::class)->decr_reference_by_id($item_data['parent_id']);
            if($res->code) throw new \Exception('res');

            // 递归更新父级的更新时间
            $res = bonjour::$container->get(m_file_manager::class)->recursive_update_parent_last_update_time($item_data['parent_id']);
            if($res->code) throw new \Exception('res');

            bonjour::$mysql->commit();
        }catch (\Exception $e)
        {
            bonjour::$mysql->rollback();
            if($e->getMessage() == 'res')
            {
                goto operation_failed;
            }else{
                $res = new res(1,'执行发生异常','',$e->getMessage());
                goto operation_failed;
            }
        }
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_finish:
        bonjour::$ext->session->echo(new res(0,'删除成功!',$ret_data));
        exit;
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_failed:
        bonjour::$ext->session->echo($res);
        exit;
    }
}

trait file_manager_v1
{
    public static $type_dir = 0;
    public static $type_dir_shortcut = 2;
    public static $type_file = 3;
    public static $type_file_shortcut = 1;

    public $database_table = '';
    public function set_database(string $database_table)
    {
        $this->database_table = $database_table;
    }

    // 获取类型的中文标识
    public function select_data_of_type_cn(int $type)
    {
        switch ($type)
        {
            case self::$type_dir:
                return new res(0,'','目录');
            case self::$type_dir_shortcut:
                return new res(0,'',null,'目录链接');
            case self::$type_file:
                return new res(0,'',null,'文件');
            case self::$type_file_shortcut:
                return new res(0,'',null,'文件链接');
            default:
                return new res(0,'',null,$type);
        }
    }

    // 获取ID的完整路径，返回数据格式
    public function select_data_of_path(string $database_table,int $id)
    {
        $path = array();
        operation_begin:
        $res = bonjour::$mysql->select('`name` as `title`,`id` as `value`,`parent_id`')->from($database_table)->where([['id','=',$id,'i']])->query();
        if($res->code) return $res;
        if($res->qry->num_rows != 1) return new res(1,'获取数据失败 id='.$id);
        $temp = $res->qry->fetch_assoc();
        $path[] = $temp;
        if($temp['parent_id'] != 0)
        {
            $id = $temp['parent_id'];
            goto operation_begin;
        }
        return new res(0,'',null,array_reverse($path));
    }

    public function sort()
    {
        $res =                                  new res();
        $ret_data =                             array();
        //-------------------------------------------------------------------------------------------------------------------------------
        $sort =                                 $_POST['sort'] ?? exit;
        //-------------------------------------------------------------------------------------------------------------------------------
        $id_list =                              explode(',',$sort);
        if(is_array($id_list) == false)
        {
            $res = new res(1,'排序的数据有错误!');
            goto operation_failed;
        }
        if(count($id_list) == 0)
        {
            $res = new res(1,'需要排序的数据不能为空');
            goto operation_failed;
        }

        // 获取父级ID
        $res = bonjour::$container->get(br_file_manager::class)->select_by_id('parent_id',$id_list[0],true);
        if($res->code) goto operation_failed;
        $parent_id = $res->qry->fetch_assoc()['parent_id'];

        try
        {
            bonjour::$mysql->begin_transaction();

            // 递归更新父级的更新时间
            $res = bonjour::$container->get(br_file_manager::class)->recursive_update_parent_last_update_time($parent_id);
            if($res->code) goto operation_failed;

            // 更新sort
            foreach ($id_list as $index => $id)
            {
                $res = bonjour::$container->get(br_file_manager::class)->update_sort_by_id($id,$index+1);
                if($res->code) goto operation_failed;
            }

            bonjour::$mysql->commit();
        }catch (\Exception $e)
        {
            bonjour::$mysql->rollback();
            if($e->getCode() == 1) goto operation_failed;
            $res = new res(1,'执行发生异常',$e->getMessage());
            goto operation_failed;
        }
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_finish:
        $this->session->echoAjax(0,'保存排序成功',$ret_data);
        exit;
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_failed:
        $this->session->echoAjax($res);
        exit;
    }
    public function insert_dir()
    {
        $res =                                  new res();
        $ret_data =                             array();
        //-------------------------------------------------------------------------------------------------------------------------------
        $parent_id =                            (int)($_POST['parent_id'] ?? exit);
        $name =                                 $_POST['name'] ?? exit;
        $desc =                                 $_POST['desc'] ?? exit;
        $icon =                                 $_POST['icon'] ?? exit;
        $web_table_template =                   $_POST['web_table_template'] ?? '';
        //-------------------------------------------------------------------------------------------------------------------------------
        $res = bonjour::$container->get(br_file_manager::class)->select_by_id('*',$parent_id,true);
        if($res->code) goto operation_failed;
        $parent_data = $res->qry->fetch_assoc();
        if($parent_data['type'] != br_file_manager::$type_dir)
        {
            $res = new res(1,'父级不是一个目录，不能添加数据');
            goto operation_failed;
        }

        // 获取排序的值
        $res = bonjour::$container->get(br_file_manager::class)->select_data_max_sort([['parent_id','=',$parent_data['id'],'i']]);
        if($res->code) goto operation_failed;
        $sort = $res->data;

        try
        {
            bonjour::$mysql->begin_transaction();

            // 增加上层的引用量
            $res = bonjour::$container->get(br_file_manager::class)->incr_reference_by_id($parent_data['id']);
            if($res->code) throw new \Exception('',1);

            // 递归更新上层的 更新时间
            $res = bonjour::$container->get(br_file_manager::class)->recursive_update_parent_last_update_time($parent_data['id']);
            if($res->code) throw new \Exception('',1);

            // 插入数据
            $res = bonjour::$container->get(br_file_manager::class)->insert(
                $parent_id,
                $parent_data['level'] + 1,
                $sort,
                '',
                0,
                br_file_manager::$type_dir,
                $name,
                $desc,
                $icon,
                $web_table_template
            );
            if($res->code) throw new \Exception('',1);

            bonjour::$mysql->commit();
        }catch (\Exception $e)
        {
            bonjour::$mysql->rollback();
            if($e->getCode() == 1) goto operation_failed;
            $res = new res(1,'执行发生异常',$e->getMessage());
            goto operation_failed;
        }
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_finish:
        $this->session->echoAjax(0,'添加成功',$ret_data);
        exit;
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_failed:
        $this->session->echoAjaxLog($res);
        exit;
    }
    public function update_dir()
    {
        $res =                                  new res();
        $ret_data =                             array();
        //-------------------------------------------------------------------------------------------------------------------------------
        $id =                                   (int)($_POST['id'] ?? exit);
        $parent_id =                            $_POST['parent_id'] ?? null;
        $name =                                 $_POST['name'] ?? exit;
        $desc =                                 $_POST['desc'] ?? exit;
        $icon =                                 $_POST['icon'] ?? exit;
        $web_table_template =                   $_POST['web_table_template'] ?? exit;
        //-------------------------------------------------------------------------------------------------------------------------------
        $res = bonjour::$container->get(br_file_manager::class)->select_by_id('*',$id,true);
        if($res->code) goto operation_failed;
        $item_data = $res->qry->fetch_assoc();

        if($item_data['type'] != br_file_manager::$type_dir)
        {
            $res = new res(1,'目标不是一个目录类型，更新失败!');
            goto operation_failed;
        }

        if(Validator::intVal()->min(1)->validate($parent_id))
        {
            $res = bonjour::$container->get(bonjour_file_manager::class)->select_by_id('type',$parent_id,true);
            if($res->code) goto operation_failed;
            $temp = $res->qry->fetch_assoc();
            if($temp['type'] != br_file_manager::$type_dir)
            {
                $res = new res(1,'父级ID，不是一个目录类型');
                goto operation_failed;
            }
        }

        try
        {
            bonjour::$mysql->begin_transaction();

            $res = bonjour::$container->get(m_c_file_manager::class)->update_by_id($id,$name,$desc,$icon,$web_table_template);
            if($res->code) throw new \Exception('',1);

            if(Validator::intVal()->min(1)->validate($parent_id))
            {
                // 减少原父级引用量
                $res = bonjour::$container->get(br_file_manager::class)->decr_reference_by_id($item_data['parent_id']);
                if($res->code) throw new \Exception('',1);

                // 增加新父级引用量
                $res = bonjour::$container->get(br_file_manager::class)->incr_reference_by_id($parent_id);
                if($res->code) throw new \Exception('',1);

                $res = bonjour::$container->get(bonjour_file_manager::class)->update_parent_id_by_id($id,$parent_id);
                if($res->code) throw new \Exception('',1);

                // 递归更新上级的更新时间
                $res = bonjour::$container->get(br_file_manager::class)->recursive_update_parent_last_update_time($parent_id);
                if($res->code) return $res;
            }

            bonjour::$mysql->commit();
        }catch (\Exception $e)
        {
            bonjour::$mysql->rollback();
            if($e->getCode() == 1) goto operation_failed;
            $res = new res(1,'执行发生异常',$e->getMessage());
            goto operation_failed;
        }

        //-------------------------------------------------------------------------------------------------------------------------------
        operation_finish:
        $this->session->echoAjax(0,'更新成功!',$ret_data);
        exit;
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_failed:
        $this->session->echoAjaxLog($res);
        exit;
    }

    public function delete_dir()
    {
        /* @var \bonjour\format\db\bonjour\file_manager         $item_data */
        $res =                                  new res();
        $ret_data =                             array();
        //-------------------------------------------------------------------------------------------------------------------------------
        $id =                                   (int)($_POST['id'] ?? exit);
        //-------------------------------------------------------------------------------------------------------------------------------
        $res = bonjour::$container->get(br_file_manager::class)->select_by_id('*',$id,true);
        if($res->code) goto operation_failed;
        $item_data = $res->qry->fetch_object();

        if($item_data->type != br_file_manager::$type_dir)
        {
            $res = new res(1,'目标不是一个目录，删除失败!');
            goto operation_failed;
        }

        try
        {
            bonjour::$mysql->begin_transaction();

            $res = bonjour::$container->get(m_c_file_manager::class)->delete_dir($item_data);
            if($res->code) throw new \Exception('',1);

            bonjour::$mysql->commit();
        }catch (\Exception $e)
        {
            bonjour::$mysql->rollback();
            if($e->getCode() == 1) goto operation_failed;
            $res = new res(1,'执行发生异常',$e->getMessage());
            goto operation_failed;
        }
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_finish:
        $this->session->echoAjax(0,'删除成功!',$ret_data);
        exit;
        //-------------------------------------------------------------------------------------------------------------------------------
        operation_failed:
        $this->session->echoAjax($res);
        exit;
    }
}