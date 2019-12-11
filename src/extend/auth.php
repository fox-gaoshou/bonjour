<?php
/**
 * 模块的使用
 *
 * 步骤一：
 * 用户登陆，或者注册的时候，需要申请一个验证码
 * $auth->new();
 * 包含生成一个验证码的图片，和绑定到一个session_id，保存在redis里面
 *
 * 步骤二：
 * 用户登陆成功，或者注册成功后
 * 对用户的ID，进行加密 id2token 并且返回给客户端
 *
 * 步骤三：
 * 用户使用 session_id，token就可以正常与服务器进行通信
 *
 * 步骤四：
 * 用户发起的请求，后台必须要验证请求是否正常
 * 每次通信Http_head都带由 session_id，token
 * 要对token进行解密 token2id，再从redis验证用户的session_id是否一致，同时验证token是否一致
 *
 * */

namespace bonjour\extend;


use bonjour\core\bonjour;
use bonjour\format\res\res;


class auth
{
    private $encrypt_key = 'DoReMeFaSo';

    // 会话ID的最少长度，最大长度
    private $session_id_min_length = 20;
    private $session_id_max_length = 32;

    // 验证码的最少长度，最大长度
    private $code_min_length = 4;
    private $code_max_length = 4;

    private $session_id = '';

    // 设置加密秘钥
    public function set_encrypt_key($key)
    {
        $this->encrypt_key = $key;
    }

    // 使用id生成token
    public function id2token($id)
    {
        $str = sprintf("DoReMi:%u:%u:%u", time(), rand(1, 99999), $id);
        return $this->encrypt($str);
    }
    // 使用token还原id
    public function token2id($token)
    {
        if(empty($token)) return new res(1,'the token can not empty!');

        $token =    $this->decrypt($token);
        $list =     explode(':',$token);
        if(count($list) != 4) return new res(1,'the token format is wrong! 0x01');
        list($prefix, $time, $rand, $id) = $list;
        if(empty($prefix) || empty($time) || empty($rand) || empty($id)) return new res(1,'the token format is wrong! 0x02');
        if($prefix != 'DoReMi') return new res(1,'the token format is wrong! 0x03');
        if((is_numeric($time) == false) || (is_numeric($rand) == false) || (is_numeric($id) == false)) return new res(1,'the token format is wrong! 0x04');
        if($rand > 99999) return new res(1,'the token format is wrong! 0x05');
        return new res(0,'',null,$id);
    }

    /**
     * 生成新的验证，返回
     *
     * @param $ex
     * 会话ID和验证码的有效时间 单位 `秒`
     *
     * @return \bonjour\format\res
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

        return new res(0,'',null,$ret_data);
    }

    public function verify($session_id,$code)
    {
        $res = new res();

        $len = strlen($session_id);
        if(($len < 20) || ($len > 32))
        {
            $res->code =           1;
            $res->error =           'session_id格式异常!';
            return $res;
        }
        $len = strlen($code);
        if(($len < 4) || ($len > 8))
        {
            $res->code =           1;
            $res->error =           'code格式异常!';
            return $res;
        }

        $key = 'session:auth_code:'.$session_id;
        $realCode = bonjour::$redis->ins->get($key);
        if($realCode === false)
        {
            $res->code =           2;
            $res->error =           '验证码已经失效!';
            goto operationFailed;
        }
        if(strtolower($code) != strtolower($realCode))
        {
            $res->code =           3;
            $res->error =           '验证码错误!';
            goto operationFailed;
        }
        operationFailed:
        bonjour::$redis->ins->del($key);
        return $res;
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

    public function get_session(string $prefix,int $id)
    {
        $key = sprintf('session:%s:%u',$prefix,$id);
        return bonjour::$redis->ins->hGetAll($key);
    }

    public function set_session(string $prefix,int $id,array $append_data)
    {
        $key = sprintf("session:%s:%u",$prefix,$id);
        $data = array(
            'id' =>         $id,
            'token' =>      $this->id2token($id),
            'session_id' => $this->session_id
        );
        $data = array_merge($data,$append_data);
        bonjour::$redis->ins->hMSet($key,$data);
        return $data;
    }

    public function get_session_id()
    {
        if(isset($_SERVER['HTTP_SESSION_ID']) == false) return new res(1,'loss the session-id field');
        $session_id = $_SERVER['HTTP_SESSION_ID'];
        $this->session_id = $session_id;
        $reg = sprintf("/^[0-9a-zA-Z_]{%u,%u}$/",$this->session_id_min_length,$this->session_id_max_length);
        if(preg_match($reg,$session_id) == false) return new res(1,'session_id format is wrong!');
        return new res(0,'',null,$session_id);
    }

    public function get_token()
    {
        if(isset($_SERVER['HTTP_TOKEN']) == false) return new res(1,'loss the token field');
        $token = $_SERVER['HTTP_TOKEN'];
        $res = $this->token2id($token);
        if($res->code) return $res;
        $ret_data = array('token'=>$token,'id'=>$res->data);
        return new res(0,'',null,$ret_data);
    }

    public function get_auth_code()
    {
        if(isset($_POST['auth_code']) == false) return new res(1,'读取auth_code失败!');
        $auth_code = $_POST['auth_code'];
        $reg = sprintf("/^[0-9]{%u,%u}$/",$this->code_min_length,$this->code_max_length);
        if(preg_match($reg,$auth_code) == false) return new res(1,'auth_code格式错误!');
        return new res(0,'',null,$auth_code);
    }

    public function check_auth_code()
    {
        // ------------------------------------------------------------------------------------------------------------------------------
        $res = $this->get_session_id();
        if($res->code) return $res;
        $session_id = $res->data;
        // ------------------------------------------------------------------------------------------------------------------------------
        $res = $this->get_auth_code();
        if($res->code) return $res;
        $auth_code = $res->data;
        // ------------------------------------------------------------------------------------------------------------------------------
        bonjour::$redis->connect();
        $key = 'session:auth_code:'.$session_id;
        $realCode = bonjour::$redis->ins->get($key);
        if($realCode === false) return new res(2,'验证码已经失效!');
        if(strtolower($auth_code) != strtolower($realCode)) return new res(3,'验证码错误!');
        bonjour::$redis->ins->del($key);
        // ------------------------------------------------------------------------------------------------------------------------------
        return new res(0,'');
    }
    public function check_session(string $prefix)
    {
        $res = $this->get_session_id();
        if($res->code) return $res;
        $session_id = $res->data;
        // ------------------------------------------------------------------------------------------------------------------------------
        $res = $this->get_token();
        if($res->code) return $res;
        $token = $res->data['token'];
        $id = $res->data['id'];
        // ------------------------------------------------------------------------------------------------------------------------------
        // 校验缓存的session-id,token是否一致!
        bonjour::$redis->connect();
        $cache = $this->get_session($prefix,$id);
        if(is_bool($cache)) return new res(1,'');

        if(isset($cache['token']) == false) return new res(2,'会话已经过期!');
        if(isset($cache['session_id']) == false) return new res(2,'会话已经过期!');

        if($cache['token'] != $token) return new res(2,'会话已经过期!');
        if($cache['session_id'] != $session_id) return new res(2,'会话已经过期!');
        // ------------------------------------------------------------------------------------------------------------------------------
        return new res(0,'',null,$cache);
    }
}