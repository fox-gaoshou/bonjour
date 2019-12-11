<?php
/**
 * Created by PhpStorm.
 * User: asus
 * Date: 2019/4/1
 * Time: 20:25
 */

namespace bonjour\lib;


class lib_verify
{
    /**
     * 验证手机号是否正确
     * 移动：134、135、136、137、138、139、150、151、152、157、158、159、182、183、184、187、188、178(4G)、147(上网卡)、148、172、198；
     * 联通：130、131、132、155、156、185、186、176(4G)、145(上网卡)；146、166、171、175
     * 电信：133、153、180、181、189 、177(4G)；149、173、174、199
     * 卫星通信：1349
     * 虚拟运营商：170
     * http://www.cnblogs.com/zengxiangzhan/p/phone.html
     * @author lan
     * @param $mobile
     * @return bool
     */
    function isMobile($mobile='') {
        return preg_match('#^13[\d]{9}$|^14[5,6,7,8,9]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[6]{1}\d{8}$|^17[0,1,2,3,4,5,6,7,8]{1}\d{8}$|^18[\d]{9}$|^19[8,9]{1}\d{8}$#', $mobile) ? true : false;
    }

    /**
     *
     * @param $Ymd
     * 校验是否一个正常的（年月日）时间格式
     *
     * @return boolean
     * */
    public function is_ymd(string $Ymd)
    {
        $match =    "/";
        $match .=   "(([0-9]{3}[1-9]|[0-9]{2}[1-9][0-9]{1}|[0-9]{1}[1-9][0-9]{2}|[1-9][0-9]{3})-(((0[13578]|1[02])-(0[1-9]|[12][0-9]|3[01]))";
        $match .=   "|((0[469]|11)-(0[1-9]|[12][0-9]|30))|(02-(0[1-9]|[1][0-9]|2[0-8]))))|((([0-9]{2})(0[48]|[2468][048]|[13579][26])";
        $match .=   "|((0[48]|[2468][048]|[3579][26])00))-02-29)$/";
        return preg_match($match,$Ymd);
    }

    /**
     * 验证用户名是否正确
     * 用户名由6-24位字母、数字组成
     * @param string $username
     * @return bool
     */
    function is_username($username)
    {
        return preg_match("/^[0-9a-zA-Z]{6,24}$/", $username) ? true : false;
    }

    /**
     * 验证密码是否正确
     * 密码由6-16位大小写字母、数字和下划线组成
     * @author lan
     * @param string $password
     * @return bool
     */
    public function is_password($password)
    {
        return preg_match("/^[0-9a-zA-Z_]{6,16}$/", $password) ? true : false;
    }

    /**
     * 提款密码是否正确
     * 提款密码6位数字的密码
     * @param string    $password
     * @param int       $length
     * @return bool
     */
    public function is_bank_password(string $password,int $length=6)
    {
        return preg_match("/^[0-9]{{$length}}$/", $password) ? true : false;
    }

    function is_numeric_password(string $password,int $len)
    {
        return preg_match("/^[0-9]{{$len}}$/",$password) ? true : false;
    }


    /**
     * 检查内容是否符合银行卡的格式
     * @param string $bankCardNo
     * 银行卡号
     * @return bool true符合  false不符合
     * */
    function is_bank_card_no(string $bank_card_no)
    {
        $strlen = strlen($bank_card_no);
        if($strlen < 15 || $strlen > 19)
        {
            return false;
        }
        return true;
    }
    function isBankCardNo($bankCardNo)
    {
        $strlen = strlen($bankCardNo);
        if($strlen < 15 || $strlen > 19)
        {
            return false;
        }
        return true;

//        if (!preg_match("/^\d{15}$/i",$bankCardNo) && !preg_match("/^\d{16}$/i",$bankCardNo) &&
//            !preg_match("/^\d{17}$/i",$bankCardNo) && !preg_match("/^\d{18}$/i",$bankCardNo) &&
//            !preg_match("/^\d{19}$/i",$bankCardNo))
//        {
//
//            return false;
//        }
//
//        $arr_no = str_split($bankCardNo);
//        $last_n = $arr_no[count($arr_no)-1];
//        krsort($arr_no);
//        $i = 1;
//        $total = 0;
//        foreach ($arr_no as $n)
//        {
//            if($i%2==0)
//            {
//                $ix = $n*2;
//                if($ix>=10)
//                {
//                    $nx = 1 + ($ix % 10);
//                    $total += $nx;
//                }else{
//                    $total += $ix;
//                }
//            }else{
//                $total += $n;
//            }
//            $i++;
//        }
//        $total -= $last_n;
//        $x = 10 - ($total % 10);
//        if($x != $last_n)
//        {
//            return false;
//        }
//
//        return true;
    }

    /**
     * @return bool true符合中文名字格式，false不符合
     * */
    function is_chinese_name(string $name,int $length=30)
    {
        $match = "/^[\x{4e00}-\x{9fa5}+\·]{2,$length}$/u";
        if (!preg_match($match, $name)) return false;
        return true;
    }

    // 是否一个正常的GUID
    public function is_guid(string $guid)
    {
        $match = "/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/u";
        if (!preg_match($match, $guid)) return false;
        return true;
    }

    // 是否一个正常的IP格式
    function is_ip($ip)
    {
        if(preg_match('/^((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1 -9]?\d))))$/', $ip))
        {
            return true;
        }else{

            return false;
        }
    }

    /**
     * 验证身份证号码格式是否正确
     * 仅支持二代身份证
     * @author chiopin
     * @param string $idcard 身份证号码
     * @return boolean
     */
    static function is_id_card($idcard=''){
        // 只能是18位
        if(strlen($idcard)!=18){
            return false;
        }

        $vCity = array(
            '11','12','13','14','15','21','22',
            '23','31','32','33','34','35','36',
            '37','41','42','43','44','45','46',
            '50','51','52','53','54','61','62',
            '63','64','65','71','81','82','91'
        );

        if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $idcard)) return false;

        if (!in_array(substr($idcard, 0, 2), $vCity)) return false;

        // 取出本体码
        $idcard_base = substr($idcard, 0, 17);

        // 取出校验码
        $verify_code = substr($idcard, 17, 1);

        // 加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);

        // 校验码对应值
        $verify_code_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');

        // 根据前17位计算校验码
        $total = 0;
        for($i=0; $i<17; $i++){
            $total += substr($idcard_base, $i, 1)*$factor[$i];
        }

        // 取模
        $mod = $total % 11;

        // 比较校验码
        if($verify_code == $verify_code_list[$mod]){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 验证邮箱是否正确
     * @author lan
     * @param string $email
     * @return bool
     */
    function is_email($email=''){
        return preg_match("/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i", $email) ? true : false;
    }

    /**
     * 验证手机号是否正确
     * 移动：134、135、136、137、138、139、150、151、152、157、158、159、182、183、184、187、188、178(4G)、147(上网卡)、148、172、198；
     * 联通：130、131、132、155、156、185、186、176(4G)、145(上网卡)；146、166、171、175
     * 电信：133、153、180、181、189 、177(4G)；149、173、174、199
     * 卫星通信：1349
     * 虚拟运营商：170
     * http://www.cnblogs.com/zengxiangzhan/p/phone.html
     * @author lan
     * @param $mobile
     * @return bool
     */
    function is_mobile($mobile='') {
        return preg_match('#^13[\d]{9}$|^14[5,6,7,8,9]{1}\d{8}$|^15[^4]{1}\d{8}$|^16[6]{1}\d{8}$|^17[0,1,2,3,4,5,6,7,8]{1}\d{8}$|^18[\d]{9}$|^19[8,9]{1}\d{8}$#', $mobile) ? true : false;
    }

    /**
     * 校验数据是否为布尔型
     * @param $any_value
     * @return bool
     */
    public function is_boolean(&$any_value)
    {
        if(gettype($any_value) == 'boolean') return true;
        return false;
    }
}