<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2018/12/7
 * Time: 10:46
 */

namespace bonjour\lib;


use bonjour\core\bonjour;
use bonjour\format\res\res;
use \PHPExcel;
use \PHPExcel_IOFactory;
use \PHPExcel_Style_NumberFormat;

class lib_session
{
    // 允许跨域
    public function allow_cross()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST,OPTIONS");
        header('Access-Control-Allow-Headers:x-requested-with,content-type,session-id,token,terminal-type');
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'OPTIONS') exit('OK');
        header('Content-Type:text/html;charset=utf-8');
    }

    public function allow_terminal_type()
    {
        if(isset($_SERVER['HTTP_TERMINAL_TYPE']) == false) exit;
        $terminal_type = $_SERVER['HTTP_TERMINAL_TYPE'] ?? exit;
        if(in_array($terminal_type,['Web','iOS','Android']) == false) exit;
        return $terminal_type;
    }
    
    // 获取前端的接口版本
    public function apiVersion()
    {
        isset($_SERVER["HTTP_API_VERSION"]) ? $apiVersion = $_SERVER["HTTP_API_VERSION"] : $apiVersion = 1.0;
        return $apiVersion;
    }

    public function echoAjax($errno,$error='',$data='')
    {
        header('Content-type: application/json');
        if(is_object($errno))
        {
            if(!empty($errno->errtype)) $errno->errno = 1;
            echo json_encode(array(
                'errno' =>  $errno->errno,
                'msg' =>    isset($errno->error) ? $errno->error : '',
                'dat' =>    isset($errno->data) ? $errno->data : '',
            ));
        } else {
            echo json_encode(array(
                'errno' =>  $errno,
                'msg' =>    $error,
                'dat' =>    $data
            ));
        }
    }

    public function echoAjaxLog($errno,$msg='',$data='')
    {
        header('Content-type: application/json');

        if(is_object($errno))
        {
            if(!empty($errno->errtype)) $errno->errno = 1;
            echo json_encode(array(
                'errno' =>  $errno->errno,
                'msg' =>    isset($errno->error) ? $errno->error : '',
                'dat' =>    isset($errno->data) ? $errno->data : '',
                'log' =>    isset($errno->log) ? $errno->log : '',
            ));
        } else if(is_array($errno))
        {
            echo json_encode(array(
                'errno' =>  $errno['errno'],
                'msg' =>    isset($errno['error']) ? $errno['error'] : '',
                'dat' =>    isset($errno['data']) ? $errno['data'] : '',
                'log' =>    isset($errno['log']) ? $errno['log'] : '',
            ));
        } else {
            echo json_encode(array(
                'errno' =>  $errno,
                'msg' =>    $msg,
                'dat' =>    $data
            ));
        }
    }

    public function ip()
    {
        // 判断服务器是否允许$_SERVER
        if(isset($_SERVER))
        {
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            {
                $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];

            }else if(isset($_SERVER['HTTP_CLIENT_IP']))
            {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            }else{
                $realip = $_SERVER['REMOTE_ADDR'];
            }
        }else{
            // 不允许就使用getenv获取
            if(getenv("HTTP_X_FORWARDED_FOR"))
            {
                $realip = getenv( "HTTP_X_FORWARDED_FOR");
            }else if(getenv("HTTP_CLIENT_IP"))
            {
                $realip = getenv("HTTP_CLIENT_IP");
            }else{
                $realip = getenv("REMOTE_ADDR");
            }
        }

        $ips = explode(',',$realip);
        foreach ($ips as $key=>$ip) $ips[$key] = trim($ip,' ');
        $realip = $ips[0];
        return $realip;
    }
    public function host()
    {
        return $_SERVER['HTTP_HOST'];
    }


    /**
     * @return string
     */
    public function getTerminalType()
    {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        switch ($agent)
        {
            case strpos($agent, 'windows nt') == true:
                $terminal = 'windows';
                break;
            case strpos($agent, 'iphone') == true:
                $terminal = 'iphone';
                break;
            case strpos($agent, 'ipad') == true:
                $terminal = 'ipad';
                break;
            case strpos($agent, 'android') == true:
                $terminal = 'android';
                break;
            default:
                $terminal = 'other';
        }
        return $terminal;
    }

    /**
     * 成一组随机不重复的数
     * @return array
     */
    public function unique_rand($min, $max, $num) {
        $count = 0;
        $return = array();
        while ($count < $num) {
            $return[] = mt_rand($min, $max);
            $return = array_flip(array_flip($return));
            $count = count($return);
        }
        shuffle($return);
        return $return;
    }


    /**
     * excel表格导出
     * @param array $data
     * @return string
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function exportToExcel($data = []){
        $title=array();
        $fileName='';

        $obj = new PHPExcel();

        //横向单元格标识
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

        $obj->getActiveSheet(0)->setTitle('sheet');   //设置sheet名称
        $_row = 1;  //设置纵向单元格标识

        if($title){
            $_cnt = count($title);
            $obj->getActiveSheet(0)->mergeCells('A'.$_row.':'.$cellName[$_cnt-1].$_row);   //合并单元格
            $obj->setActiveSheetIndex(0)->setCellValue('A1'.$_row, '数据导出：'.date('Y-m-d H:i:s'));  //设置合并后的单元格内容
            $_row++;
            $i = 0;
            foreach($title AS $v){   //设置列标题
                $obj->setActiveSheetIndex(0)->setCellValue($cellName[$i].$_row, $v);
                $i++;
            }
            $_row++;
        }


        $obj->getActiveSheet()->getStyle('C')->getNumberFormat()
            ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

        //填写数据
        if($data){
            $i = 0;
            foreach($data AS $_v){
                $j = 0;
                foreach($_v AS $_cell){
                    $obj->getActiveSheet(0)->setCellValue($cellName[$j] . ($i+$_row), $_cell);
                    $j++;
                }
                $i++;
            }
        }

        //文件名处理
        if(!$fileName){
            $fileName = md5(time());
        }

        $objWrite = PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
        $_fileName = iconv("utf-8", "gb2312", $fileName);   //转码
        $_savePath = bonjour_root . "/../tp5/public/excel/".$_fileName.'.xlsx';

        $daochuPath = "https://" . $_SERVER['HTTP_HOST'] . "/excel/" . $_fileName.'.xlsx';

        $objWrite->save($_savePath);

        $this->echoAjax(0, "导出成功", $daochuPath);

    }

    public function exportToCsv($data)
    {
        $res =              new res();
        if (!$data)
        {
            $this->echoAjax(1, "数据为空！");
            exit;
        }

        $file_name = iconv('utf-8','gb2312',md5(time()));
        $file_path = bonjour::$evn->root . '/../tp5/public/excel/' . $file_name . '.csv';
        $file = fopen($file_path,'w');
        $row_count = 0;
        fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF));      //添加BOM头
        foreach ($data as $row)
        {
            fputcsv($file, $row);
            $row_count++;
            if ($row_count%2000 == 0)
            {
                ob_flush();
                flush();
            }
        }
        fclose($file);
        $file_network_path = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/excel/' . $file_name . '.csv';
        $this->echoAjax(0, "导出成功", $file_network_path);
    }

    function isHTTPS()
    {
        if (defined('HTTPS') && HTTPS) return true;
        if (!isset($_SERVER)) return FALSE;
        if (!isset($_SERVER['HTTPS'])) return FALSE;
        if ($_SERVER['HTTPS'] === 1) {  //Apache
            return TRUE;
        } elseif ($_SERVER['HTTPS'] === 'on') { //IIS
            return TRUE;
        } elseif ($_SERVER['SERVER_PORT'] == 443) { //其他
            return TRUE;
        }
        return FALSE;
    }


    /**
     * @param $filename
     * @param array $tileArray
     * @param array $dataArray
     * 生成csv表格下载
     */
    public function exportToExcel2($filename, $tileArray = [], &$dataArray = [])
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 60);
        ob_end_clean();
        ob_start();
        header("Content-Type: text/csv");
        header("Content-Disposition:filename=" . $filename);
        $fp = fopen('php://output', 'w');
        fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));//转码 防止乱码(比如微信昵称(乱七八糟的))
        fputcsv($fp, $tileArray);
        $index = 0;
        foreach ($dataArray as $item) {
            if ($index == 1000) {
                $index = 0;
                ob_flush();
                flush();
            }
            $index++;
            fputcsv($fp, $item);
        }

        ob_flush();
        flush();
        ob_end_clean();
    }

}