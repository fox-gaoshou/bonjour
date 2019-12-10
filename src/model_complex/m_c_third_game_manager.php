<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/7/10
 * Time: 15:20
 */

namespace bonjour\model_complex;


use bonjour\core\bonjour;
use bonjour\format\res\res;
use bonjour\model\bonjour\br_file_manager;
use bonjour\model\bonjour\br_lobby_game_unit;
use bonjour\model\third_game_manager\tgm_platform_game_unit;
use Respect\Validation\Validator;

class m_c_third_game_manager
{
    public function file_manager_update_by_id(int $id,string $title,string $desc,string $icon,string $web_table_template)
    {
        $res = bonjour::$container->get(br_file_manager::class)->select_by_id('parent_id',$id,true);
        if($res->code) return $res;
        $parent_id = $res->qry->fetch_assoc()['parent_id'];

        $res = bonjour::$container->get(br_file_manager::class)->update_by_id($id,$title,$desc,$icon,$web_table_template,true);
        if($res->code) return $res;

        $res = bonjour::$container->get(br_file_manager::class)->recursive_update_parent_last_update_time($parent_id);
        if($res->code) return $res;

        return new res();
    }

    public function file_manager_update_sort(string $sort)
    {
        $id_list = explode(',',$sort);
        if(is_array($id_list) == false)
        {
            return new res(1,'排序的数据有错误!');
        }
        if(count($id_list) == 0) return new res(1,'需要排序的数据不能为空');
        foreach ($id_list as $id)
        {
            if(Validator::numeric()->positive()->validate($id) == false) return new res(1,'排序的数据有错误!');
        }

        // 获取父级ID
        $res = bonjour::$container->get(br_file_manager::class)->select_by_id('parent_id',$id_list[0],true);
        if($res->code) return $res;
        $parent_id = $res->qry->fetch_assoc()['parent_id'];

        // 递归更新父级的更新时间
        $res = bonjour::$container->get(br_file_manager::class)->recursive_update_parent_last_update_time($parent_id);
        if($res->code) return $res;

        foreach ($id_list as $index => $id)
        {
            $res = bonjour::$container->get(br_file_manager::class)->update_sort_by_id($id,$index+1);
            if($res->code) return $res;
        }

        return new res();
    }

    public function file_manager_insert(int $parent_id,string $assoc_table,int $assoc_id,int $type,string $name,string $desc,string $icon)
    {
        // 检查父级是否目录
        $res = bonjour::$container->get(br_file_manager::class)->select_by_id('`id`,`type`,`level`',$parent_id,true);
        if($res->code) return $res;
        $parent_data = $res->qry->fetch_assoc();
        if($parent_data['type'] != br_file_manager::$type_dir)
        {
            $res = new res(1,'父级不是一个目录，不能添加数据');
            return $res;
        }

        // 获取游戏单元数据
        $res = bonjour::$container->get(tgm_platform_game_unit::class)->select_by_id('*',$assoc_id,true);
        if($res->code) return $res;
        $game_unit_data = $res->qry->fetch_assoc();

        // 增加上层的引用量
        $res = bonjour::$container->get(br_file_manager::class)->incr_reference_by_id($parent_data['id']);
        if($res->code) return $res;

        // 递归更新上层的 更新时间
        $res = bonjour::$container->get(br_file_manager::class)->recursive_update_parent_last_update_time($parent_data['id']);
        if($res->code) return $res;

        // 获取排序的值
        $res = bonjour::$container->get(br_file_manager::class)->select_data_max_sort([['parent_id','=',$parent_data['id'],'i']]);
        if($res->code) return $res;
        $sort = $res->data;

        $res = bonjour::$container->get(br_file_manager::class)->insert(
            $parent_id,
            $parent_data['level'] + 1,
            $sort,
            $assoc_table,
            $assoc_id,
            $type,
            $name,
            $desc,
            $icon
        );
        if($res->code) return $res;

        // 增加额外数据
        $insert_id = $res->insert_id;
        $res = bonjour::$container->get(br_lobby_game_unit::class)->insert($insert_id,1,$game_unit_data['platform_code'],$game_unit_data['game_code']);
        if($res->code) return $res;

        return $res;
    }

    public function file_manager_delete_by_id(int $id)
    {
        // 获取父级ID
        $res = bonjour::$container->get(br_file_manager::class)->select_by_id('`parent_id`',$id,true);
        if($res->code) return $res;
        $parent_id = $res->qry->fetch_assoc()['parent_id'];

        // 删除目标数据
        $res = bonjour::$container->get(br_file_manager::class)->delete_by_id_and_check_reference($id);
        if($res->code) return $res;

        // 减少上层的引用量
        $res = bonjour::$container->get(br_file_manager::class)->decr_reference_by_id($parent_id);
        if($res->code) return $res;

        // 递归更新父级的更新时间
        $res = bonjour::$container->get(br_file_manager::class)->recursive_update_parent_last_update_time($parent_id);
        if($res->code) return $res;

        return new res();
    }

    public function select_data_game_bar(int $id)
    {
        $list = array();
        $res = bonjour::$container->get(br_file_manager::class)->select_by_parent_id_and_sort('`id`,`name`,`icon`',$id);
        if($res->code) return $res;
        foreach ($res->qry as $row) $list[] = $row;
        return new res(0,'',null,$list);
    }
    public function select_data_game_list(int $id)
    {
        $list = array();
        $res = bonjour::$container->get(br_file_manager::class)->select_by_parent_id('`type`,`assoc_table`,`assoc_id`,`name`,`icon`',$id);
        if($res->code) return $res;
        foreach ($res->qry as $row)
        {
            $temp = array(
                'type' =>           $row['type'],
                'group_id' =>       0,
                'assoc_id' =>       $row['assoc_id'],
                'name' =>           $row['name'],
                'icon' =>           $row['icon'],
                'status' =>         0,
                'platform_code' =>  '',
                'game_code' =>      ''
            );

            // 如果是一个目录快捷方式，把目录的真实上层ID，作为group_id
            if($row['type'] == br_file_manager::$type_dir_link)
            {
                $res = bonjour::$container->get(br_file_manager::class)->select_by_id('`parent_id` as `group_id`',$row['assoc_id']);
                if($res->code) return $res;
                $temp = array_merge($temp,$res->qry->fetch_assoc());
            }

            // 如果关联的游戏单元
            if(($row['type'] == br_file_manager::$type_file_link) && ($row['assoc_table'] == tgm_platform_game_unit::$dt))
            {
                $res = bonjour::$container->get(tgm_platform_game_unit::class)->select_by_id('`platform_code`,`game_code`',$row['assoc_id'],true);
                if($res->code) return $res;
                $temp = array_merge($temp,$res->qry->fetch_assoc());
            }

            $list[] = $temp;
        }
        return new res(0,'',null,$list);
    }

    public function select_data_by_file_link_id(int $id)
    {
        $data = array();

        // 获取源文件的关系数据
        $res = bonjour::$container->get(br_file_manager::class)->select_by_id('`assoc_table`,`assoc_id`',$id,true);
        if($res->code) return $res;
        $file_data = $res->qry->fetch_assoc();

        switch ($file_data['assoc_table'])
        {
            case br_lobby_game_unit::$dt:
                // 获取附加显示的数据
                $res = $this->select_data_by_file_id($file_data['assoc_id']);
                if($res->code) return $res;
                $data = $res->data;

                // 获取源文件的路径
                $res = bonjour::$container->get(br_file_manager::class)->select_data_path_string_by_id($file_data['assoc_id']);
                if($res->code) return $res;
                $data[] = sprintf("%s : %s",'源文件路径',$res->data);
            default:
                break;
        }

        return new res(0,'',null,$data);
    }
    public function select_data_by_file_id(int $id)
    {
        $data = array();

        $res = bonjour::$container->get(br_lobby_game_unit::class)->select_by_id('*',$id,true);
        if($res->code) return $res;
        $temp = $res->qry->fetch_assoc();
        $data[] = sprintf("%s : %s",'游戏ID',$temp['game_id']);
        $data[] = sprintf("%s : %s",'平台编码',$temp['platform_code']);
        $data[] = sprintf("%s : %s",'游戏编码',$temp['game_code']);

        return new res(0,'',null,$data);
    }
}