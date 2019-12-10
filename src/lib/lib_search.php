<?php


namespace bonjour\lib;


use bonjour\format\res\res;
use bonjour\format\res\res_exception;
use Respect\Validation\Validator as v;

class lib_search
{
    public function user_id_username(int $user_id,string $username)
    {
        if(!empty($user_id) && !empty($username))
        {
            (new res_exception(1,"用户ID和用户名，不能同时进行搜索!"))->throw();
        }
        if(!empty($user_id))
        {
            if(v::intVal()->validate($user_id) == false)
            {
                (new res_exception(1,"用户ID 只能输入纯数字类型!"))->throw();
            }
        }
        if(!empty($username))
        {
            if(v::regex('/[0-9a-zA-Z]{1,32}/')->validate($username) == false)
            {
                (new res_exception(1,"用户名 只能由 数字|英文字母 1-32位组成"))->throw();
            }
        }
    }
    public function page(&$page)
    {
        if(empty($page) == true) $page = 1;
        if($page <= 0) $page = 1;
        if(v::intVal()->validate($page) == false) $page = 1;
        $page -= 1;
    }

    /**
     * 生成搜索用的IP参数，返回的数据是数组格式
     * count($ips) == 1 单独搜索一个IP
     * count($ips) == 2 搜索范围 between
     * count($ips) == 255 搜索 in ()
     *
     * @param string $ip
     * this format such as 192.168.*.*
     * @param &$ips
     * 返回给外层的 IP 数组
     * @return res
     * @throws \Exception
     */
    public function ip(string $ip,&$ips)
    {
        $ips = array();
        if(empty($ip) == true) return new res();

        $ip =       trim($ip,' ');
        $begin =    0x00000000;
        $end =      0xffffffff;
        $wildcard_character = 0;
        $numbers = explode('.',$ip);
        if(count($numbers) != 4) return new res(1,"IP地址，不是4组数据");

        for($i=0;$i<4;$i++)
        {
            if($numbers[$i] == '*')
            {
                $wildcard_character++;
                continue;
            }
            if(!is_numeric($numbers[$i])) return new res(1,"IP数组，不是通配符* 或者是数字");
            if(($numbers[$i] < 0) || ($numbers[$i] > 255)) return new res(1,"IP数组，数字超出范围0~255");
            $this->ip_fill($begin,$numbers[$i],$i);
            $this->ip_fill($end,$numbers[$i],$i);
        }

        switch ($wildcard_character)
        {
            case 0:
                $ips = array(ip2long($ip));
                break;
            case 1:
                $section = 0;
                for($i=0;$i<4;$i++)
                {
                    if($numbers[$i] == '*')
                    {
                        $section = $i;
                        break;
                    }
                }
                $ips = array();
                for($i=0;$i<=255;$i++)
                {
                    $this->ip_fill($begin,$i,$section);
                    $ips[] = $begin;
                }
                break;
            case 2:
            case 3:
            case 4:
                $ips =  array($begin,$end);
                break;
            default:
                throw new \Exception("程序异常错误!",1);
        }

        return new res();
    }
    private function ip_fill(&$data,int $fill_data,int $section)
    {
        switch ($section)
        {
            case 0:
                $data = $data & 0x00ffffff;
                $fill_data = $fill_data << 24;
                $data = $data | $fill_data;
                break;
            case 1:
                $data = $data & 0xff00ffff;
                $fill_data = $fill_data << 16;
                $data = $data | $fill_data;
                break;
            case 2:
                $data = $data & 0xffff00ff;
                $fill_data = $fill_data << 8;
                $data = $data | $fill_data;
                break;
            case 3:
                $data = $data & 0xffffff00;
                $data = $data | $fill_data;
                break;
            default:
                throw new \Exception(sprintf("This section %s is an error section\n",$section),1);
        }
    }
}