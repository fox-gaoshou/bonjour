<?php


namespace bonjour\extend;


use bonjour\core\bonjour;
use bonjour\format\res\res;
use bonjour\format\res\res_session;


define('session_is_timeout',            100);


class session
{
    // 会话加密的密钥
    private $encrypt_key = 'DoReMeFaSo';
    // 令牌的前缀
    private $token_prefix = 'DoReMi';

    // 会话ID的最少长度，最大长度
    private $session_id_min_length = 20;
    private $session_id_max_length = 32;

    // 验证码的最少长度，最大长度
    private $code_min_length = 4;
    private $code_max_length = 4;

    private $session_id = '';

    private $cache = null;

    // 加密的底层实现
    private function encrypt_low($input,$key)
    {
        # Input must be of even length.
        if (strlen($input) % 2)
        {
            //$input .= '0';
        }

        # Keys longer than the input will be truncated.
        if (strlen($key) > strlen($input))
        {
            $key = substr($key, 0, strlen($input));
        }

        # Keys shorter than the input will be padded.
        if (strlen($key) < strlen($input))
        {
            $key = str_pad($key, strlen($input), '0', STR_PAD_RIGHT);
        }

        # Now the key and input are the same length.
        # Zero is used for any trailing padding required.

        # Simple XOR'ing, each input byte with each key byte.
        $result = '';
        for ($i = 0; $i < strlen($input); $i++)
        {
            $result .= $input{$i} ^ $key{$i};
        }
        return $result;
    }

    // url传输需要替换部分字符
    private function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // url传输需要替换部分字符
    private function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    // 加密
    private function encrypt($str)
    {
        $hashKey = $this->base64url_encode($this->encrypt_low($str,$this->encrypt_key));
        return $hashKey;
    }

    // 解密
    private function decrypt($hashKey)
    {
        $contents = $this->encrypt_low($this->base64url_decode($hashKey),$this->encrypt_key);
        return $contents;
    }

    // 使用id生成token
    private function id2token($id)
    {
        $str = sprintf("%s:%u:%u:%u",$this->token_prefix,time(), rand(1, 99999), $id);
        return $this->encrypt($str);
    }

    // 使用token还原id
    private function token2id($token)
    {
        if(empty($token)) throw new \Exception('The token can not empty');

        $token =    $this->decrypt($token);
        $list =     explode(':',$token);
        if(count($list) != 4) throw new \Exception('The token format is wrong! 0x01');
        list($prefix, $time, $rand, $id) = $list;
        if(empty($prefix) || empty($time) || empty($rand) || empty($id)) throw new \Exception('The token format is wrong! 0x02');
        if($prefix != $this->token_prefix) throw new \Exception('The token format is wrong! 0x03');
        if((is_numeric($time) == false) || (is_numeric($rand) == false) || (is_numeric($id) == false)) throw new \Exception('The token format is wrong! 0x04');
        if($rand > 99999) throw new \Exception('The token format is wrong! 0x05');
        return $id;
    }

    // 生成验证码图像
    private function img(&$img)
    {
        $code = '';
        for($i=0;$i<$this->code_max_length;$i++) $code .= dechex(mt_rand(0,9));
        //验证码数组准备完成,开始绘图
        ob_clean();
        ob_start();
        //创建一个图形区域.赋值给资源句柄
        $image = imagecreatetruecolor(75,30);
        //在空白的图像区域绘制填充背景
        $blue = imagecolorallocate($image,191,239,255);  //颜色1  背景
        $white = imagecolorallocate($image,0,197,205);  //颜色2   文字
        imagefill($image,0,0,$blue);  //填充颜色
        //生成文本信息.将验证码的字符串写入图片.
        imagestring($image,5,18,5,$code,$white);
        ob_start();
        imagepng($image);
        $img = base64_encode(ob_get_contents());
        ob_end_clean();

        return $code;
    }

    /**
     * 获取session-id
     *
     * @return mixed
     * @throws \Exception
     */
    private function get_session_id()
    {
        if(isset($_SERVER['HTTP_SESSION_ID']) == false) throw new \Exception('Loss the session-id');
        $session_id = $_SERVER['HTTP_SESSION_ID'];
        $this->session_id = $session_id;
        $reg = sprintf("/^[0-9a-zA-Z_]{%u,%u}$/",$this->session_id_min_length,$this->session_id_max_length);
        if(preg_match($reg,$session_id) == false) throw new \Exception('The session-id format is wrong!');
        return $session_id;
    }

    /**
     * 获取token
     *
     * @return mixed
     * @throws \Exception
     */
    private function get_token()
    {
        if(isset($_SERVER['HTTP_TOKEN']) == false) throw new \Exception('Loss the token');
        $token = $_SERVER['HTTP_TOKEN'];
        return $token;
    }

    // 获取验证码
    private function get_auth_code()
    {
        if(isset($_POST['auth_code']) == false) throw new \Exception('Loss the auth_code field');
        $auth_code = $_POST['auth_code'];
        $reg = sprintf("/^[0-9]{%u,%u}$/",$this->code_min_length,$this->code_max_length);
        if(preg_match($reg,$auth_code) == false) throw new \Exception('The auth_code format is wrong!');
        return $auth_code;
    }

    /**
     * 新会话的建立
     * 每个会话都需要获取一个session_id以及一个验证码图片
     *
     * @param $ex
     * 会话ID和验证码的有效时间 单位 `秒`
     *
     * @return res
     * data : sessionID 会话ID
     * data : img 验证码的图像
     *
     * */
    public function new($ex=60)
    {
        $ret_data = array();
        $ret_data['session_id'] =   &$session_id;
        $ret_data['img'] =          &$img;

        $session_id = session_create_id();
        $img = '';

        $code = $this->img($img);
        $key = 'session:auth_code:'.$session_id;
        bonjour::$redis->ins->set($key,$code,['ex'=>$ex]);

        return new res(0,'',$ret_data);
    }

    public function get(string $prefix,int $id)
    {
        $key = sprintf('session:%s:%u',$prefix,$id);
        return bonjour::$redis->ins->hGetAll($key);
    }
    public function set(string $prefix,int $id,array $append_data)
    {
        $key = sprintf("session:%s:%u",$prefix,$id);
        $data = array(
            'id' =>         $id,
            'token' =>      $this->id2token($id),
            'session_id' => $this->session_id
        );
        $data = array_merge($data,$append_data);
        bonjour::$redis->ins->hMSet($key,$data);
        $this->set_cache($data);
        return $data;
    }
    public function del(string $prefix,int $id)
    {
        $key = sprintf("session:%s:%u",$prefix,$id);
        return bonjour::$redis->ins->del($key);
    }

    // 设置会话的缓存信息
    public function set_cache($data)
    {
        $this->cache = $data;
    }
    // 获取会话的缓存信息
    public function get_cache()
    {
        return $this->cache;
    }

    /**
     * @return res_session
     */
    public function check_auth_code()
    {
        try
        {
            $session_id =   $this->get_session_id();
            $auth_code =    $this->get_auth_code();

            $key = 'session:auth_code:'.$session_id;
            $real_code = bonjour::$redis->ins->get($key);
            if(is_bool($real_code)) throw new \Exception('验证码已经失效!');
            if(strtolower($auth_code) != strtolower($real_code)) throw new \Exception('验证码错误!');
            bonjour::$redis->ins->del($key);

            return new res_session(0,'验证码校验通过!');
        }catch (\Exception $e)
        {
            return new res_session(1,$e->getMessage());
        }
    }

    /**
     * 检查会话是否有效，并返回用户的缓存信息
     * @param string            $prefix
     * 缓存的前缀，一般用于区分，member,admin，账号的缓存
     * @return res_session
     * */
    public function check_session(string $prefix)
    {
        try
        {
            $session_id =   $this->get_session_id();
            $token =        $this->get_token();
            $id =           $this->token2id($token);
            $cache =        $this->get($prefix,(int)$id);

            if(is_bool($cache))                         return new res_session(session_is_timeout,'会话已经超时!');
            if(isset($cache['session_id']) == false)    return new res_session(session_is_timeout,'会话已经过期!');
            if($cache['session_id'] != $session_id)     return new res_session(session_is_timeout,'其他终端设备已经登陆此账号!');

            $this->set_cache($cache);
            return new res_session(0,'校验会话通过!');
        }catch (\Exception $e)
        {
            return new res_session(1,$e->getMessage());
        }
    }

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
        return strtolower($terminal_type);
    }

    public function ip()
    {
        // 判断服务器是否允许$_SERVER
        if(isset($_SERVER))
        {
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            {
                $real_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }else if(isset($_SERVER['HTTP_CLIENT_IP']))
            {
                $real_ip = $_SERVER['HTTP_CLIENT_IP'];
            }else{
                $real_ip = $_SERVER['REMOTE_ADDR'];
            }
        }else{
            // 不允许就使用getenv获取
            if(getenv("HTTP_X_FORWARDED_FOR"))
            {
                $real_ip = getenv( "HTTP_X_FORWARDED_FOR");
            }else if(getenv("HTTP_CLIENT_IP"))
            {
                $real_ip = getenv("HTTP_CLIENT_IP");
            }else{
                $real_ip = getenv("REMOTE_ADDR");
            }
        }

        $ips = explode(',',$real_ip);
        foreach ($ips as $key=>$ip) $ips[$key] = trim($ip,' ');
        $real_ip = $ips[0];
        return $real_ip;
    }

    /**
     * @param object                        $res
     * @param bool                          $force_out_log
     * 是否强制输出log
     * */
    public function echo($res,bool $force_out_log=false)
    {
        header('Content-type: application/json');

//        if($force_out_log == false)
//        {
//            if($this->is_allowing_out_log() == false) $res->log = null;
//        }

        echo json_encode(array(
            'type' => $res->type,
            'code' => $res->code,
            'message' => $res->message,
            'data' => $res->data,
            'log' => $res->log
        ));
    }

    public function host()
    {
        return $_SERVER['HTTP_HOST'];
    }
}
