<?php

namespace app\model;
//验证码类
class Validate
{
    private $charset = 'abcdefghkmnprstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789'; //随机因子
    private $code = ''; //验证码
    private $codelen = 4; //验证码长度
    private $width = 130; //宽度
    private $height = 50; //高度
    private $img; //图形资源句柄
    private $font; //指定的字体
    private $fontsize = 20; //指定字体大小
    private $fontcolor; //指定字体颜色

    //构造方法初始化
    public function __construct()
    {
        $candidates = [];
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $candidates[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/static/admin/css/fonts/code.ttc';
        }
        // ThinkPHP 项目根
        if (function_exists('root_path')) {
            $candidates[] = root_path() . 'public/static/admin/css/fonts/code.ttc';
        }
        $candidates[] = dirname(__DIR__, 2) . '/public/static/admin/css/fonts/code.ttc';

        $this->font = '';
        foreach ($candidates as $path) {
            if ($path && is_file($path)) {
                $this->font = $path;
                break;
            }
        }
    }

    //生成随机码
    private function createCode()
    {
        $this->code = '';
        $_len = strlen($this->charset) - 1;
        for ($i = 0; $i < $this->codelen; $i++) {
            $this->code .= $this->charset[mt_rand(0, $_len)];
        }
    }

    //生成背景
    private function createBg()
    {
        $this->img = imagecreatetruecolor($this->width, $this->height);
        $color = imagecolorallocate($this->img, mt_rand(157, 255), mt_rand(157, 255), mt_rand(157, 255));
        imagefilledrectangle($this->img, 0, $this->height, $this->width, 0, $color);
    }

    //生成文字
    private function createFont()
    {
        $_x = $this->width / $this->codelen;
        $useTtf = $this->font !== '' && function_exists('imagettftext');

        for ($i = 0; $i < $this->codelen; $i++) {
            $this->fontcolor = imagecolorallocate($this->img, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            $x = (int) round($_x * $i + mt_rand(1, 5));
            $y = (int) round($this->height / 1.4);

            if ($useTtf) {
                // PHP 8.1+ 要求坐标为 int，否则会触发弃用并可能被当成异常
                imagettftext(
                    $this->img,
                    $this->fontsize,
                    mt_rand(-30, 30),
                    $x,
                    $y,
                    $this->fontcolor,
                    $this->font,
                    $this->code[$i]
                );
            } else {
                // 无字体文件时降级为内置字体，保证验证码通道可用
                imagestring($this->img, 5, $x, (int) round($this->height / 3), $this->code[$i], $this->fontcolor);
            }
        }
    }

    //生成线条、雪花
    private function createLine()
    {
        for ($i = 0; $i < 6; $i++) {
            $color = imagecolorallocate($this->img, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imageline($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }
        for ($i = 0; $i < 100; $i++) {
            $color = imagecolorallocate($this->img, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
            imagestring($this->img, mt_rand(1, 5), mt_rand(0, $this->width), mt_rand(0, $this->height), '*', $color);
        }
    }

    //对外生成
    public function getImg()
    {
        if (!function_exists('imagecreatetruecolor')) {
            throw new \RuntimeException('PHP GD 扩展未安装，无法生成图形验证码');
        }
        $this->createBg();
        $this->createCode();
        $this->createLine();
        $this->createFont();
        // 必须先开启缓冲，否则 imagepng 直接输出、base64 为空
        ob_start();
        imagepng($this->img);
        $image_data = ob_get_clean();
        imagedestroy($this->img);
        return 'data:image/png;base64,' . base64_encode($image_data ?: '');
    }

    //获取验证码
    public function getCode()
    {
        return strtolower($this->code);
    }

    /**
     * 验证图形验证码
     *
     * @return void
     */
    public function validateImgCode($token, $code)
    {
        if (!$token) {
            return jerr('TOKEN参数丢失');
        }
        if (!$code) {
            return jerr('请输入验证码');
        }
        $code = strtoupper($code);
        $_code = cache($token);
        if (!$_code) {
            return jerr('验证码已过期');
        }
        if ($code != $_code) {
            return jerr('验证码错误');
        }
        // 删除设置的缓存
        cache($token, null);
        return null;
    }
}
