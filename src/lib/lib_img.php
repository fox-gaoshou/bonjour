<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/17
 * Time: 19:35
 */

namespace bonjour\lib;

class lib_img
{
    public function genAuthCodeV1(&$img)
    {
        $code = ''; for($i=0;$i<4;$i++)$code.=  dechex(mt_rand(0,9));
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

    public function genAuthCode($imgFileName,$imgFilePath)
    {
        $code = ''; for($i=0;$i<4;$i++)$code.=  dechex(mt_rand(0,9));
        //验证码数组准备完成,开始绘图
        ob_clean();
        //创建一个图形区域.赋值给资源句柄
        $image=imagecreatetruecolor(100,30);
        //在空白的图像区域绘制填充背景
        $blue=imagecolorallocate($image,224,255,255);  //颜色1  背景
        $white=imagecolorallocate($image,0,197,205);  //颜色2   文字
        imagefill($image,0,0,$blue);  //填充颜色
        //生成文本信息.将验证码的字符串写入图片.
        imagestring($image,5,18,5,$code,$white);
        //输出最终图形
        imagepng($image,$imgFilePath .$imgFileName);
        //清除占用的资源
        imagedestroy($image);

        return $code;
    }
    public function genAuthCode1($imgFileName,$imgFilePath)
    {
        // 创建一张宽100高30的图像
        // 为$image设置背景颜色为白色
        // 填充背景颜色
        $image = imagecreatetruecolor(100, 30);
        $bgcolor = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgcolor);

//        $data="1234567890abcdefghigklmnopqrstuvwxyzABCDEFGHIGKLMNOPQRSTUVWXYZ";
        $data="1234567890";
        $code = "";
        for($i=0; $i<4; $i++){
            $fontsize=50;
            $fontcolor = imagecolorallocate($image, rand(0,120), rand(0,120), rand(0, 120));
            // 设置每次产生的字符从$data中每次截取一个字符
            $fontcontent=substr($data, rand(0,strlen($data)), 1);
            // 让产生的四个字符拼接起来
            $code .= $fontcontent;
            // 控制每次出现的字符的坐标防止相互覆盖即x->left y->top
            $x=($i*100/4)+rand(5, 10);
            $y=rand(5, 10);
            // 此函数用来将产生的字符在背景图上画出来
            imagestring($image, $fontsize, $x, $y, $fontcontent, $fontcolor);
        }

        // 用来在背景图片上产生200个干扰点
        for($i=0; $i<200; $i++)
        {
            // 干扰点的颜色
            $pointcolor=imagecolorallocate($image, rand(50,200), rand(50, 200), rand(50, 200));
            // 该函数用来把每个干扰点在背景上描绘出来
            imagesetpixel( $image, rand(1, 99), rand(1,29), $pointcolor);
        }

        // 产生三条干扰线
        for ($i=0; $i <3 ; $i++)
        {
            // 干扰线的颜色
            $linecolor=imagecolorallocate($image, rand(80, 220), rand(80, 220), rand(80, 220));
            // 画出每条干扰线
            imageline($image, rand(1, 99), rand(1, 29), rand(1, 99), rand(1,29), $linecolor);
        }

        imagepng($image,$imgFilePath .$imgFileName);
        imagedestroy($image);

        return $code;
    }
}