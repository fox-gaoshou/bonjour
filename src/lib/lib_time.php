<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2018/12/29
 * Time: 16:51
 */

namespace bonjour\lib;


use bonjour\format\res\res;

class lib_time
{
    function now()
    {
        $mTimestamp = sprintf("%.3f",microtime(true));
        $timestamp = floor($mTimestamp);
        $milliseconds = round(($mTimestamp - $timestamp) * 1000);
        return array(
            "timestamp" => $timestamp,
            "milliseconds" => $milliseconds
        );
    }
    function distanceMinus($beginTime,$endTime)
    {
        $temp = $endTime - $beginTime;
        return floor($temp/60);
    }
    function distanceHours($beginTime,$endTime)
    {
        $temp = $endTime - $beginTime;
        return floor($temp/(60*60));
    }
    function distanceDays($beginTime,$endTime)
    {
        $temp = $endTime - $beginTime;
        return floor($temp/(60*60*24));
    }
    function distanceWeek($beginTime,$endTime)
    {
        $temp = $endTime - $beginTime;
        return floor($temp/(60*60*24*7));
    }
    function distanceYear($beginTime,$endTime)
    {
        $temp = $endTime - $beginTime;
        return floor($temp/(60*60*24*365));
    }

    public function distance_slicing(int $s_time,int $e_time)
    {
        $temp = $e_time - $s_time;
        return floor($temp/(60*10));
    }

    /**
     * 两个时间的间隔小时数
     *
     * @param int               $s_time
     * @param int               $e_time
     * @return int
     * */
    function distance_hour(int $s_time,int $e_time)
    {
        $temp = $e_time - $s_time;
        return floor($temp/(60*60));
    }

    /**
     * 两个时间的间隔天数
     *
     * @param int               $s_time
     * @param int               $e_time
     * @return int
     * */
    function distance_day(int $s_time,int $e_time)
    {
        $temp = $e_time - $s_time;
        return floor($temp/(60*60*24));
    }

    /**
     * 两个时间的间隔周数
     * @param int               $s_time
     * @param int               $e_time
     * @return int
     * */
    function distance_week(int $s_time,int $e_time)
    {
        $temp = $e_time - $s_time;
        return floor($temp/(60*60*24*7));
    }

    function between_last_days(int $days)
    {
        $Ymd = date('Ymd',strtotime(sprintf("-%u day",$days)));
        $s_time = strtotime($Ymd.'000000');
        $Ymd = date('Ymd');
        $e_time = strtotime($Ymd.'235959');

        $temp = array();
        $temp['s_time'] = $s_time;
        $temp['e_time'] = $e_time;

        return $temp;
    }
    function between_today()
    {
        $Ymd = date('Ymd');
        $temp = array();
        $temp['s_time'] = strtotime($Ymd.'000000');
        $temp['e_time'] = strtotime($Ymd.'235959');
        return $temp;
    }
    function between_yesterday()
    {
        $Ymd = date('Ymd',strtotime("-1 day"));
        $temp = array();
        $temp['s_time'] = strtotime($Ymd.'000000');
        $temp['e_time'] = strtotime($Ymd.'235959');
        return $temp;
    }

    function betweenToday()
    {
        $Ymd = date('Ymd');
        $temp = array();
        $temp['begin'] = strtotime($Ymd."000000");
        $temp['end'] = strtotime($Ymd."235959");
        $temp['sTime'] = strtotime($Ymd.'000000');
        $temp['eTime'] = strtotime($Ymd.'2359559');
        return $temp;
    }
    function betweenYesterday()
    {
        $Ymd = date('Ymd',strtotime("-1 day"));
        $temp = array();
        $temp['begin'] = strtotime($Ymd."000000");
        $temp['end'] = strtotime($Ymd."235959");
        $temp['sTime'] = strtotime($Ymd.'000000');
        $temp['eTime'] = strtotime($Ymd.'2359559');
        return $temp;
    }
    function betweenThisWeek()
    {
        $temp = array();
        $temp['begin'] = strtotime('this week 000000');
        $temp['end'] = strtotime('+6 day 235959',$temp['begin']);
        return $temp;
    }
    function betweenLastWeek()
    {
        $temp = array();
        $temp['begin'] = strtotime('last week 000000');
        $temp['end'] = strtotime('+6 day 235959',$temp['begin']);
        return $temp;
    }

    function betweenThisMonth()
    {
        $temp = array();
        $temp['begin'] = strtotime(date('Ym01000000'));
        $temp['end'] = strtotime("+1 month -1 day 235959",$temp['begin']);
        return $temp;
    }
    function betweenLastMonth()
    {
        $temp = array();
        $temp['begin'] = strtotime(date("Ym01000000",strtotime("last month")));
        $temp['end'] = strtotime("+1 month -1 day 235959",$temp['begin']);
        return $temp;
    }

    function betweenYmd($between,$maxIntervalDays=15)
    {
        /* @var \bonjour\core\res $res */
        $res = (object)array('errno'=>0);

        if(empty($between))
        {
            $begin =        strtotime(date('Ymd000000'));
            $end =          strtotime(date('Ymd235959'));
        }else{
            $temp =         explode(' - ',$between);
            if(count($temp) != 2)
            {
                $res->code =       1;
                $res->msg =         '时间区间格式错误!';
                return $res;
            }
            $begin =        strtotime(date('Ymd000000',strtotime($temp[0])));
            $end =          strtotime(date('Ymd235959',strtotime($temp[1])));
            if($begin > $end)
            {
                $res->code =       1;
                $res->msg =         '开始时间不能大于结束时间!';
                return $res;
            }
            if(($end - $begin) > ($maxIntervalDays*86400))
            {
                $res->code =       1;
                $res->msg =         sprintf("时间区间跨度不能超过%u天",$maxIntervalDays);
                return $res;
            }
        }
        $res->data =            array();
        $res->data['begin'] =   $begin;
        $res->data['end'] =     $end;
        return $res;
    }

    // 前端发送给后台的格式必须是 2018-01-01 00:00:00 - 2018-01-02 00:00:00
    public function between($between,$defaultIntervalDays=6,$maxIntervalDays=31)
    {
        $res = new res();

        if(empty($between))
        {
            $now =          time();
            $temp =         strtotime(sprintf("-%s day",$defaultIntervalDays),$now);
            $begin =        strtotime(date("Ymd000000",$temp));
            $end =          strtotime(date("Ymd235959",$now));
        }else{
            $temp =         explode(' - ',$between);
            if(count($temp) != 2)
            {
                $res->code =       1;
                $res->error =       '时间区间格式错误!';
                return $res;
            }
            $begin =        strtotime(date('Ymd000000',strtotime($temp[0])));
            $end =          strtotime(date('Ymd235959',strtotime($temp[1])));
            if($begin > $end)
            {
                $res->code =       1;
                $res->error =       '开始时间不能大于结束时间!';
                return $res;
            }
            if(($end - $begin) > ($maxIntervalDays*86400))
            {
                $res->code =       1;
                $res->error =       sprintf("时间区间跨度不能超过%u天",$maxIntervalDays);
                return $res;
            }
        }
        $res->data =            array();
        $res->data['s_time'] =  $begin;
        $res->data['e_time'] =  $end;
        return $res;
    }

    public function betweenToFront(&$retData,int $sTime,int $eTime,string $format="Y-m-d H:i:s")
    {
        $retData["between"] = sprintf("%s - %s",date($format,$sTime),date($format,$eTime));
    }

    public function genBetweenFormatToFrontend($beginTime,$endTime)
    {
        if(empty($beginTime) || empty($endTime)) return "";
        return sprintf("%s - %s",date('Y-m-d H:i:s',$beginTime),date('Y-m-d H:i:s',$endTime));
    }
    public function genBetweenYmdFormatToFrontend($between)
    {
        if(empty($between)) return "";
        return sprintf("%s - %s",date("Y-m-d",$between['s_time']),date("Y-m-d",$between['e_time']));
    }
    public function between_to_front0(int $s_time,int $e_time,string $format='Y-m-d')
    {
        return sprintf('%s - %s',date($format,$s_time),date($format,$e_time));
    }
    public function between_to_front1(int $s_time,int $e_time,string $format='Y-m-d H:i:s')
    {
        return sprintf('%s - %s',date($format,$s_time),date($format,$e_time));
    }
}