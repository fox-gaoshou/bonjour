<?php


namespace bonjour\model;


use bonjour\core\bonjour;
use bonjour\core\model;
use bonjour\format\res\res;
use bonjour\format\res\res_mysql;
use bonjour\traits\model\model_reference;
use bonjour\traits\model\model_select_by_id;
use bonjour\traits\model\model_sort;


bonjour::$traits->include_model('id');
bonjour::$traits->include_model('sort');
bonjour::$traits->include_model('reference');

class m_file_manager extends model
{
    use model_select_by_id;
    use model_sort;
    use model_reference;

    public $database_table;
    public function set_database_table(string $database_table)
    {
        return $this->database_table = $database_table;
    }

    public function insert(int $parent_id,int $level,int $sort,string $assoc_table,int $assoc_id,int $type,string $name,string $desc,string $icon,string $web_table_template)
    {
        $insert = array(
            ['parent_id',$parent_id,'i'],
            ['level',$level,'i'],
            ['sort',$sort,'i'],
            ['assoc_table',$assoc_table,'s'],
            ['assoc_id',$assoc_id,'i'],
            ['type',$type,'i'],
            ['name',$name,'s'],
            ['desc',$desc,'s'],
            ['icon',$icon,'s'],
            ['web_table_template',$web_table_template,'s']
        );
        return $this->mysql->insert($this->database_table,$insert)->query();
    }

    /**
     * 添加文件，关联的数据，需要外层操作
     *
     * @param int                   $parent_id
     * 父级ID
     * @param string                $assoc_table
     * 关联数据的 库名.表名
     * @param int                   $assoc_id
     * 关联数据的ID
     * @param string                $name
     * 文件名称
     * @param string                $desc
     * 文件描述
     * @param string                $icon
     * 文件图标
     *
     * @return res|res_mysql
     * @throws
     * */
    public function insert_file(int $parent_id,string $assoc_table,int $assoc_id,string $name,string $desc,string $icon)
    {
        // 查询父级的数据
        $res = $this->select_by_id('*',$parent_id,true);
        if($res->code) return $res;
        $parent_data = $res->qry->fetch_assoc();

        // 检查上级是否 属于一个目录
        // 如果不是就返回错误
        $res = $this->check_type_by_id($parent_id,bonjour_file_manager_type_dir);
        if($res->code) return $res;

        // 增加上层的引用量
        $res = $this->incr_reference_by_id($parent_data['id']);
        if($res->code) return $res;

        // 递归更新上层的 更新时间
        $res = $this->recursive_update_parent_last_update_time($parent_data['id']);
        if($res->code) return $res;

        // 获取排序的值
        $res = $this->select_data_of_max_sort([['parent_id','=',$parent_data['id'],'i']]);
        if($res->code) return $res;
        $sort = $res->data;

        // 插入数据
        $res = $this->insert(
            $parent_id,
            $parent_data['level'] + 1,
            $sort,
            $assoc_table,
            $assoc_id,
            bonjour_file_manager_type_file,
            $name,
            $desc,
            $icon,
            ''
        );
        if($res->code) return $res;

        return $res;
    }

    /**
     * 检查目标ID，是否等于type
     *
     * @param int           $id
     * 需要检查的ID
     * @param int           $type
     * 需要检查匹配的数据类型
     *
     * @return res
     * */
    public function check_type_by_id(int $id,int $type)
    {
        $res = $this->mysql->select('`type`')->from($this->database_table)->where([['id','=',$id,'i']])->query();
        if($res->code) return $res;
        if($res->qry->num_rows != 1) return new res(1,sprintf('目标数据不存在 ID=%u',$id));
        if($res->qry->fetch_assoc()['type'] != $type) return new res(1,sprintf('目标类型不符合 ID=%u',$id));
        return new res();
    }

    /**
     * 递归更新父级的最后更新时间
     *
     * @param int $id
     * @return res|\bonjour\format\res\res_mysql
     * @throws \Exception
     */
    public function recursive_update_parent_last_update_time(int $id)
    {
        if($id == 0) return new res();

        do{
            $res = $this->select_by_id('`parent_id`',$id,true);
            if($res->code) return $res;
            $parent_id = $res->qry->fetch_assoc()['parent_id'];

            $res = $this->update_last_update_time_by_id($id);
            if($res->code) return $res;
            $id = $parent_id;
        }while($id != 0);

        return new res();
    }

    // 更新一个数据
    public function update_by_id(int $id,string $name,string $desc,string $icon,string $web_table_template,bool $necessary=false)
    {
        $update = array(
            ['name','=',$name,'s'],
            ['desc','=',$desc,'s'],
            ['icon','=',$icon,'s'],
            ['web_table_template','=',$web_table_template,'s']
        );
        return $this->update_by_id_low_level($id,$update,$necessary);
    }

    /**
     * 根据ID，更新最后的更新时间
     *
     * @param int $id
     * @return \bonjour\format\res\res_mysql
     * @throws \Exception
     */
    public function update_last_update_time_by_id(int $id)
    {
        return $this->mysql->update($this->database_table)->set("last_update_time=NOW()")->where([['id','=',$id,'i']])->query();
    }

    /**
     * 更新父级ID
     *
     * @param int $id
     * @param int $parent_id
     * @param bool $necessary
     * @return res|\bonjour\format\res\res_mysql
     * @throws \Exception
     */
    public function update_parent_id_by_id(int $id,int $parent_id,bool $necessary=false)
    {
        $res = $this->mysql->update($this->database_table)->set([['parent_id','=',$parent_id,'i']])->where([['id','=',$id,'i']])->query();
        if($res->code) return $res;
        if(($necessary == true) && ($res->affected_rows != 1)) return new res(1,'更新父级ID失败，可能内容没有发生变化，或者数据不存在!');
        return $res;
    }

    // 获取类型的中文标识
    public function select_data_type_cn($type)
    {
        switch ($type)
        {
            case bonjour_file_manager_type_dir:
                return new res(0,'','目录');
            case bonjour_file_manager_type_file:
                return new res(0,'','文件');
                break;
            case bonjour_file_manager_type_dir_shortcut:
                return new res(0,'','目录链接');
            case bonjour_file_manager_type_file_shortcut:
                return new res(0,'','文件链接');
            default:
                return new res(0,'',$type);
        }
    }

    // 获取ID的完整路径，返回数据格式
    public function select_data_of_path_by_id(int $id)
    {
        $path = array();
        operation_begin:
        $res = $this->mysql->select('`name` as `title`,`id` as `value`,`parent_id`')->from($this->database_table)->where([['id','=',$id,'i']])->query();
        if($res->code) return $res;
        if($res->qry->num_rows != 1) return new res(1,'获取数据失败 id='.$id);
        $temp = $res->qry->fetch_assoc();
        $path[] = $temp;
        if($temp['parent_id'] != 0)
        {
            $id = $temp['parent_id'];
            goto operation_begin;
        }
        return new res(0,'',array_reverse($path));
    }

    // 根据父级ID，查询所有的item以及排序
    public function select_by_parent_id_and_sort(string $ss,int $parent_id)
    {
        return $this->mysql->select($ss)->from($this->database_table)->where([['parent_id','=',$parent_id,'i']])->order_by('`sort` asc')->query();
    }
}