<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/6
 * Time: 13:52
 */

namespace bonjour\lib;


use bonjour\core\bonjour;
use bonjour\format\res\res;


class lib_ip
{
    public function ip_to_address($ip)
    {
        try
        {
            $ipip = new \ipip\datx\City(__DIR__ . '/ipip/17monipdb.datx');
            $IPDistrict = $ipip->find($ip);
            return new res(0,'',$IPDistrict);
        }catch (\Exception $e)
        {
            return new res(1,$e->getMessage());
        }
    }

    /**
     * 生成搜索用的IP参数，返回的数据是数组格式
     * count($ips) == 1 单独搜索一个IP
     * count($ips) == 2 搜索范围 between
     * count($ips) == 255 搜索 in ()
     *
     * @param string        $ip
     * this format such as 192.168.*.*
     *
     * @return res
     * @throws
     * */
    public function gen_search(string $ip)
    {
        $ips =      array();
        $ip =       trim($ip,' ');
        $begin =    0x00000000;
        $end =      0xffffffff;
        $wildcardCharacter = 0;
        $numbers = explode('.',$ip);
        if(count($numbers) != 4) goto ip_format_not_match;

        for($i=0;$i<4;$i++)
        {
            if($numbers[$i] == '*')
            {
                $wildcardCharacter++;
                continue;
            }
            if(!is_numeric($numbers[$i])) goto ip_format_not_match;
            if(($numbers[$i] < 0) || ($numbers[$i] > 255)) goto ip_format_not_match;
            $this->fill($begin,$numbers[$i],$i);
            $this->fill($end,$numbers[$i],$i);
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
                    $this->fill($begin,$i,$section);
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

        return new res(0,'',$ips);
        ip_format_not_match:
        return new res(1,'IP格式不符合!');
    }
    private function fill(&$data,int $fillData,int $section)
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