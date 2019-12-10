<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/4/30
 * Time: 11:29
 */

namespace bonjour\lib;


use bonjour\format\res\res;
use Respect\Validation\Validator as v;

class lib_validate_search_param
{
    public function uid_username($uid,$username)
    {
        $res = new res();

        if(!empty($uid) && !empty($username))
        {
            $res->code =       1;
            $res->error =       "user_id and username 不能同时搜索";
            return $res;
        }
        if(!empty($uid))
        {
            if(v::intVal()->validate($uid) == false)
            {
                $res->code =   1;
                $res->error =   "uid 只能输入纯数字类型";
                return $res;
            }
        }
        if(!empty($username))
        {
            if(v::regex('/[0-9a-zA-Z]{2,32}/')->validate($username) == false)
            {
                $res->code =   1;
                $res->error =   "username 只能由 数字|英文字母 2-32位组成";
                return $res;
            }
        }
        return $res;
    }

    public function page(&$page)
    {
        $res = new res();

        if(empty($page) == true) $page = 1;
        if($page <= 0) $page = 1;
        if(v::intVal()->validate($page) == false) $page = 1;
        $page -= 1;

        return $res;
    }

    /**
     * 生成搜索用的IP参数，返回的数据是数组格式
     * count($ips) == 1 单独搜索一个IP
     * count($ips) == 2 搜索范围 between
     * count($ips) == 255 搜索 in ()
     *
     * @param $ip
     * this format such as 192.168.*.*
     * @param &$ips
     * 返回给外层的 IP 数组
     *
     *
     * */
    public function ip(string $ip,&$ips)
    {
        $res = (object)array('errno'=>0);

        $ips = array();
        if(empty($ip) == true) return $res;

        $ip =       trim($ip,' ');
        $begin =    0x00000000;
        $end =      0xffffffff;
        $wildcardCharacter = 0;
        $numbers = explode('.',$ip);
        if(count($numbers) != 4) goto ipFormatNotMatch;

        for($i=0;$i<4;$i++)
        {
            if($numbers[$i] == '*')
            {
                $wildcardCharacter++;
                continue;
            }
            if(!is_numeric($numbers[$i])) goto ipFormatNotMatch;
            if(($numbers[$i] < 0) || ($numbers[$i] > 255)) goto ipFormatNotMatch;
            $this->ipFill($begin,$numbers[$i],$i);
            $this->ipFill($end,$numbers[$i],$i);
        }

        switch ($wildcardCharacter)
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
                    $this->ipFill($begin,$i,$section);
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

        return $res;

        ipFormatNotMatch:
        $res->code =           1;
        $res->error =           'IP格式不符合!';
        return $res;
    }
    private function ipFill(&$data,int $fillData,int $section)
    {
        switch ($section)
        {
            case 0:
                $data = $data & 0x00ffffff;
                $fillData = $fillData << 24;
                $data = $data | $fillData;
                break;
            case 1:
                $data = $data & 0xff00ffff;
                $fillData = $fillData << 16;
                $data = $data | $fillData;
                break;
            case 2:
                $data = $data & 0xffff00ff;
                $fillData = $fillData << 8;
                $data = $data | $fillData;
                break;
            case 3:
                $data = $data & 0xffffff00;
                $data = $data | $fillData;
                break;
            default:
                throw new \Exception(sprintf("This section %s is an error section\n",$section),1);
        }
    }
}