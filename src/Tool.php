<?php

namespace PhalApi;

use PhalApi\Exception\BadRequestException;

/**
 * PhalApi_Tool 工具集合类
 * 只提供通用的工具类操作，目前提供的有：
 * - IP地址获取
 * - 随机字符串生成
 * @package     PhalApi\Tool
 * @license     http://www.phalapi.net/license GPL 协议
 * @link        http://www.phalapi.net/
 * @author      dogstar <chanzonghuang@gmail.com> 2015-02-12
 */
class Tool
{

    /**
     * IP地址获取
     * @return string 如：192.168.1.1 失败的情况下，返回空
     */
    public static function getClientIp()
    {
        $unknown = 'unknown';

        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), $unknown)) {
            $ip = getenv('HTTP_CLIENT_IP');
        } else if (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), $unknown)) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } else if (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), $unknown)) {
            $ip = getenv('REMOTE_ADDR');
        } else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '';
        }

        return $ip;
    }

    /**
     * 随机字符串生成
     *
     * @param int $len 需要随机的长度，不要太长
     * @param string $chars 随机生成字符串的范围
     *
     * @return string
     */
    public static function createRandStr($len, $chars = null)
    {
        if (!$chars) {
            $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        return substr(str_shuffle(str_repeat($chars, rand(5, 8))), 0, $len);
    }

    /**
     * 获取数组value值不存在时返回默认值
     * 不建议在大循环中使用会有效率问题
     *
     * @param array $arr 数组实例
     * @param string|int $key 数据key值
     * @param string $default 默认值
     *
     * @return string
     */
    public static function arrIndex($arr, $key, $default = '')
    {

        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * 根据路径创建目录或文件
     *
     * @param string $path 需要创建目录路径
     *
     * @throws PhalApi_Exception_BadRequest
     */
    public static function createDir($path)
    {

        $dir = explode('/', $path);
        $path = '';
        foreach ($dir as $element) {
            $path .= $element . '/';
            if (!is_dir($path) && !mkdir($path)) {
                throw new BadRequestException(
                    T('create file path Error: {filePath}', array('filepath' => $path))
                );
            }
        }
    }

    /**
     * 删除目录以及子目录等所有文件
     *
     * - 请注意不要删除重要目录！
     *
     * @param string $path 需要删除目录路径
     */
    public static function deleteDir($path)
    {

        $dir = opendir($path);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $path . '/' . $file;
                if (is_dir($full)) {
                    self::deleteDir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($path);
    }

    /**
     * 数组转XML格式
     *
     * @param array $arr 数组
     * @param string $root 根节点名称
     * @param int $num 回调次数
     *
     * @return string xml
     */
    public static function arrayToXml($arr, $root = 'xml', $num = 0)
    {
        $xml = '';
        if (!$num) {
            $num += 1;
            $xml .= '<?xml version="1.0" encoding="utf-8"?>';
        }
        $xml .= "<$root>";
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= self::arrayToXml($val, "$key", $num);
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</$root>";
        return $xml;
    }

    /**
     * XML格式转数组
     *
     * @param  string $xml
     *
     * @return mixed|array
     */
    public static function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $arr = json_decode(json_encode($xmlstring), true);
        return $arr;
    }

    /**
     * 去除字符串空格和回车
     *
     * @param  string $str 待处理字符串
     *
     * @return string
     */
    public static function trimSpaceInStr($str)
    {
        $pat = array(" ", "　", "\t", "\n", "\r");
        $string = array("", "", "", "", "",);
        return str_replace($pat, $string, $str);
    }

    /**
     * 检查文件类型
     *
     * @access      public
     * @param       string      filename            文件名
     * @param       string      realname            真实文件名
     * @param       string      limit_ext_types     允许的文件类型
     * @return      string
     */
    public static function check_file_type($filename, $realname = '', $limit_ext_types = '')
    {
        if ($realname) {
            $extname = strtolower(substr($realname, strrpos($realname, '.') + 1));
        } else {
            $extname = strtolower(substr($filename, strrpos($filename, '.') + 1));
        }

        if ($limit_ext_types && stristr($limit_ext_types, '|' . $extname . '|') === false) {
            return '';
        }

        $str = $format = '';

        $file = @fopen($filename, 'rb');
        if ($file) {
            $str = @fread($file, 0x400); // 读取前 1024 个字节
            @fclose($file);
        } else {
            if (stristr($filename, ROOT_PATH) === false) {
                if ($extname == 'jpg' || $extname == 'jpeg' || $extname == 'gif' || $extname == 'png' || $extname == 'doc' ||
                    $extname == 'xls' || $extname == 'txt' || $extname == 'zip' || $extname == 'rar' || $extname == 'ppt' ||
                    $extname == 'pdf' || $extname == 'rm' || $extname == 'mid' || $extname == 'wav' || $extname == 'bmp' ||
                    $extname == 'swf' || $extname == 'chm' || $extname == 'sql' || $extname == 'cert' || $extname == 'pptx' ||
                    $extname == 'xlsx' || $extname == 'docx'
                ) {
                    $format = $extname;
                }
            } else {
                return '';
            }
        }

        if ($format == '' && strlen($str) >= 2) {
            if (substr($str, 0, 4) == 'MThd' && $extname != 'txt') {
                $format = 'mid';
            } elseif (substr($str, 0, 4) == 'RIFF' && $extname == 'wav') {
                $format = 'wav';
            } elseif (substr($str, 0, 3) == "\xFF\xD8\xFF") {
                $format = 'jpg';
            } elseif (substr($str, 0, 4) == 'GIF8' && $extname != 'txt') {
                $format = 'gif';
            } elseif (substr($str, 0, 8) == "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
                $format = 'png';
            } elseif (substr($str, 0, 2) == 'BM' && $extname != 'txt') {
                $format = 'bmp';
            } elseif ((substr($str, 0, 3) == 'CWS' || substr($str, 0, 3) == 'FWS') && $extname != 'txt') {
                $format = 'swf';
            } elseif (substr($str, 0, 4) == "\xD0\xCF\x11\xE0") {   // D0CF11E == DOCFILE == Microsoft Office Document
                if (substr($str, 0x200, 4) == "\xEC\xA5\xC1\x00" || $extname == 'doc') {
                    $format = 'doc';
                } elseif (substr($str, 0x200, 2) == "\x09\x08" || $extname == 'xls') {
                    $format = 'xls';
                } elseif (substr($str, 0x200, 4) == "\xFD\xFF\xFF\xFF" || $extname == 'ppt') {
                    $format = 'ppt';
                }
            } elseif (substr($str, 0, 4) == "PK\x03\x04") {
                if (substr($str, 0x200, 4) == "\xEC\xA5\xC1\x00" || $extname == 'docx') {
                    $format = 'docx';
                } elseif (substr($str, 0x200, 2) == "\x09\x08" || $extname == 'xlsx') {
                    $format = 'xlsx';
                } elseif (substr($str, 0x200, 4) == "\xFD\xFF\xFF\xFF" || $extname == 'pptx') {
                    $format = 'pptx';
                } else {
                    $format = 'zip';
                }
            } elseif (substr($str, 0, 4) == 'Rar!' && $extname != 'txt') {
                $format = 'rar';
            } elseif (substr($str, 0, 4) == "\x25PDF") {
                $format = 'pdf';
            } elseif (substr($str, 0, 3) == "\x30\x82\x0A") {
                $format = 'cert';
            } elseif (substr($str, 0, 4) == 'ITSF' && $extname != 'txt') {
                $format = 'chm';
            } elseif (substr($str, 0, 4) == "\x2ERMF") {
                $format = 'rm';
            } elseif ($extname == 'sql') {
                $format = 'sql';
            } elseif ($extname == 'txt') {
                $format = 'txt';
            } elseif ($extname == 'xlsx') {
                $format = 'xlsx';
            } elseif ($extname == 'xls') {
                $format = 'xls';
            }
        }

        if ($limit_ext_types && stristr($limit_ext_types, '|' . $format . '|') === false) {
            $format = '';
        }

        return $format;
    }

    /**
     * 检查目标文件夹是否存在，如果不存在则自动创建该目录
     *
     * @access      public
     * @param       string      folder     目录路径。不能使用相对于网站根目录的URL
     *
     * @return      bool
     */
    public static function make_dir($folder)
    {
        $reval = mkdir($folder, 0777, true);
        clearstatcache();
        return $reval;
    }

    /**
     * 重构图片实际访问地址
     *
     * @param string $image 原图片地址
     * @param string $defaul 默认图片类型：no_picture 通用 user_head 头像
     * @return string   $url
     */
    public static function get_image_path($image = '', $defaul = 'no_picture')
    {
        if (empty($image)) {
            return \PhalApi\DI()->config->get("sys.{$defaul}");
        } elseif (strpos($image, 'http://') !== false || strpos($image, 'https://') !== false) {
            return $image;
        } elseif (strpos($image, '/') !== false && strpos($image, '/') > 0) {
            return \PhalApi\DI()->config->get('app.file_up.file_domain') . '/' . $image;
        } else {
            return \PhalApi\DI()->config->get('app.file_up.file_domain') . $image;
        }
    }

    /**
     * 取得当前的域名
     * @access  public
     * @return  string      当前的域名
     */
    public static function get_domain()
    {
        if (PHP_SAPI == 'cli') {
            return '';
        }
        /* 协议 */
        $protocol = self::http();

        /* 域名或IP地址 */
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } else {
            /* 端口 */
            if (isset($_SERVER['SERVER_PORT'])) {
                $port = ':' . $_SERVER['SERVER_PORT'];

                if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {
                    $port = '';
                }
            } else {
                $port = '';
            }

            if (isset($_SERVER['SERVER_NAME'])) {
                $host = $_SERVER['SERVER_NAME'] . $port;
            } elseif (isset($_SERVER['SERVER_ADDR'])) {
                $host = $_SERVER['SERVER_ADDR'] . $port;
            }
        }

        return $protocol . $host;
    }

    final static function url()
    {
        $curr = strpos(PHP_SELF, ADMIN_MANAGE_PATH . '/') !== false ?
            preg_replace('/(.*)(' . ADMIN_MANAGE_PATH . ')(\/?)(.)*/i', '\1', dirname(PHP_SELF)) :
            dirname(PHP_SELF);

        $root = str_replace('\\', '/', $curr);

        if (substr($root, -1) != '/') {
            $root .= '/';
        }

        return self::get_domain() . $root;
    }


    final static function http()
    {
        return (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https')) || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
    }

    /**
     * 阿拉伯数字转中文数字
     * @param int $num 阿拉伯数字
     * @return string
     */
    public static function numtochr($num)
    {
        $china = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
        $arr = str_split($num);
        for ($i = 0; $i < count($arr); $i++) {
            $china_num = $china[$arr[$i]];
        }

    }

    /**
     * 手机号验证
     * @param string $mobile
     * @return bool
     */
    public static function checkMobile($mobile)
    {
        if (preg_match('/^1[0-9]{10}$/', $mobile) > 0) {
            return true;
        }
        return false;
    }

    /**
     * 返回今日开始和结束的时间戳
     *
     * @return array
     */
    public static function today_stamp()
    {
        return [
            mktime(0, 0, 0, date('m'), date('d'), date('Y')),
            mktime(23, 59, 59, date('m'), date('d'), date('Y'))
        ];
    }

    /**
     * 返回昨天开始和结束的时间戳
     *
     * @return array
     */
    public static function yesterday_stamp()
    {
        return [
            mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')),
            mktime(23, 59, 59, date('m'), date('d') - 1, date('Y'))
        ];
    }

    /**
     * 返回近几天开始和结束的时间戳
     * @param int $day 近几天的天数
     * @return array
     */
    public static function lastday_stamp($day = 1)
    {
        return [
            mktime(0, 0, 0, date('m'), date('d') - $day + 1, date('Y')),
            mktime(23, 59, 59, date('m'), date('d'), date('Y')),
        ];
    }

    /**
     * 返回本周开始和结束的时间戳
     *
     * @return array
     */
    public static function thisweek_stamp()
    {
        $timestamp = time();
        return [
            strtotime(date('Y-m-d', strtotime("this week Monday", $timestamp))),
            strtotime(date('Y-m-d', strtotime("this week Sunday", $timestamp))) + 24 * 3600 - 1
        ];
    }

    /**
     * 返回上周开始和结束的时间戳
     *
     * @return array
     */
    public static function lastWeek_stamp()
    {
        $timestamp = time();
        return [
            strtotime(date('Y-m-d', strtotime("last week Monday", $timestamp))),
            strtotime(date('Y-m-d', strtotime("last week Sunday", $timestamp))) + 24 * 3600 - 1
        ];
    }

    /**
     * 返回本年开始和结束的时间戳
     *
     * @param string $sel_year 指定的年份
     * @return array
     */
    public static function thisyear_stamp($sel_year = null)
    {
        //默认为当前年份
        $year = $sel_year > 0 ? $sel_year : date('Y');
        return [
            mktime(0, 0, 0, 1, 1, $year),
            mktime(23, 59, 59, 12, 31, $year)
        ];
    }

    /**
     * 返回最近一年的开始和结束的时间戳
     * @return array
     */
    public static function lastyear_stamp()
    {
        return [
            strtotime(date('Y-m-d', strtotime('-1 year'))),
            mktime(23, 59, 59, date('m'), date('t'), date('Y')),
        ];
    }

    /**
     * 返回本月开始和结束的时间戳
     * @param string $sel_month 指定的月份
     * @return array
     */
    public static function thismonth_stamp($sel_month = null)
    {
        //默认为当前月份
        $month = $sel_month > 0 ? $sel_month : date('m');
        return [
            mktime(0, 0, 0, $month, 1, date('Y')),
            mktime(23, 59, 59, $month, date('t'), date('Y'))
        ];
    }

    /**
     * 返回指定年份的月起止时间戳,默认返回上一个月起止时间
     *
     * @return array
     */
    public static function lastmonth_stamp($sel_month = null, $sel_year = null)
    {
        //默认为当前月份
        $month = $sel_month > 0 ? $sel_month : date('m');
        //默认为当前年份
        $year = $sel_year > 0 ? $sel_year : date('Y');
        return [
            mktime(0, 0, 0, $month, 1, $year),
            mktime(23, 59, 59, $month + 1, 0, $year)
        ];
    }

    /**
     * 格式化商品价格
     *
     * @param float $price 商品价格
     * @param string $type 格式化类型
     * @param string $currency_format 价格前缀
     * @return mixed
     */
    public static function price_format($price, $type = '0', $currency_format = '')
    {
        if ($price === '') {
            $price = 0;
        }
        switch ($type) {
            case 0: //四舍五入保留两位小数
                $price = number_format($price, 2, '.', '');
                break;
            case 1: // 保留不为 0 的尾数
                $price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));
                if (substr($price, -1) == '.') {
                    $price = substr($price, 0, -1);
                }
                break;
            case 2: // 不四舍五入，保留1位
                $price = substr(number_format($price, 2, '.', ''), 0, -1);
                break;
            case 3: // 直接取整
                $price = intval($price);
                break;
            case 4: // 四舍五入，保留 1 位
                $price = number_format($price, 1, '.', '');
                break;
            case 5: // 先四舍五入，不保留小数
                $price = round($price);
                break;
        }
        return $currency_format . $price;
    }

    /**
     * 加入APP样式
     * @param string $source 内容
     * @param string $type 样式类型：art 文章
     * @return string
     */
    public static function app_content($source, $type = '')
    {
        $class_style = '';
        if ($type == 'art') { //添加文章内容样式
            $class_style = 'art_content';
        }

        $source = '<style>' . file_get_contents(ROOT_PATH . 'public/' . "/app.css") . "</style><div class='{$class_style}'>" . $source . '</div>';
        return $source;
    }

    /**
     * 时间格式处理
     * @param string $time 要处理的时间戳
     * @param string $format 时间格式
     * @param bool $autoformat 是否开启自动转换
     * @return bool|string
     */
    public static function formatTime($time, $format = "Y-m-d", $autoformat = true)
    {
        if ($autoformat) {
            //已过去的时间戳
            $timestmp = time() - $time;
            if ($timestmp < 60 * 5) {    //刚刚
                $str = '刚刚';
            } elseif ($timestmp < 60 * 60 && $timestmp >= 60 * 5) {   //多少分钟前
                $min = floor($timestmp / 60);
                $str = $min . '分钟前';
            } elseif ($timestmp < 60 * 60 * 24 && $timestmp >= 60 * 60) {  //多少小时前
                $h = floor($timestmp / (60 * 60));
                $str = $h . '小时前 ';
            } elseif ($timestmp < 60 * 60 * 24 * 365) {    //昨天到今年开始
                $str = date("m-d H:i", $time);
            } elseif ($timestmp < 60 * 60 * 24 * 3) {  //昨天，前天
                $d = floor($timestmp / (60 * 60 * 24));
                if ($d == 1) {
                    $str = '昨天 ' . date("H:i", $time);
                } else {
                    $str = '前天 ' . date("H:i", $time);
                }
            } elseif ($timestmp < 60 * 60 * 24 * 8) {  //星期几
                $weekarray = array("日", "一", "二", "三", "四", "五", "六");
                $str = "星期" . $weekarray[date("w", $time)];
            } elseif ($timestmp < 60 * 60 * 24 * 30) {  //几周前
                $str = ceil($timestmp / 60 * 60 * 24 * 7) . "周前";
            } else {
                $str = date($format, $time);
            }
            return $str;
        } else {
            return date($format, $time);
        }
    }
}
