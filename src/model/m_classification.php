<?php


namespace bonjour\model;


use bonjour\core\model;

class m_classification extends model
{
    public function set_database_table(string $database_table)
    {
        $this->database_table = $database_table;
        return $this;
    }

    public function insert(int $parent_id,int $sort,string $assoc_table,int $assoc_id,int $type,string $name,string $desc,string $icon,string $web_table_template)
    {
        $insert = array(
            ['parent_id',$parent_id,'i'],
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
}