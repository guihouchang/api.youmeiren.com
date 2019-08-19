<?php

namespace common;
use common\define\D_COMM_DEFINE;
use common\define\D_COMM_STATUS_CODE;
use Phalcon\Http\Response;

class util
{
    public static function printFoZhu()
    {
        printf('
    //                            _ooOoo_  
    //                           o8888888o  
    //                           88" . "88  
    //                           (| -_- |)  
    //                            O\ = /O  
    //                        ____/`---\'\____  
    //                      .   \' \\| |// `.  
    //                       / \\||| : |||// \  
    //                     / _||||| -:- |||||- \  
    //                       | | \\\ - /// | |  
    //                     | \_| \'\'\---/\'\' | |  
    //                      \ .-\__ `-` ___/-. /  
    //                   ___`. .\' /--.--\ `. . __  
    //                ."" \'< `.___\_<|>_/___.\' >\'"".  
    //               | | : `- \`.;`\ _ /`;.`/ - ` : | |  
    //                 \ \ `-. \_ __\ /__ _/ .-` / /  
    //         ======`-.____`-.___\_____/___.-`____.-\'======  
    //                            `=---=\'  
    //  
    //         .............................................  
    //               佛祖保佑                 永无bug  
    //   
    ');
    printf("\n");
    }

    public static function createToken($phone)
    {
        return md5($phone . uniqid() . time());
    }

    public static function Date($time, $format = 'Y-m-d H:i:s')
    {
        if (!$time) $time = time();
        return date($format, $time);
    }

    /**
     * 获取图片高宽
     * @param $path
     * @return array|null
     */
    public static function getImageSize($path)
    {
        $data = getimagesize($path);
        if (!$data) return null;

        return ["width" => $data[0], "height" => $data[1]];
    }

    /**
     * 移动文件
     * @param $src
     * @param $des
     * @return bool
     */
    public static function moveFile($src, $des)
    {
        return rename($src, $des);
    }

    public static function AllowFetch()
    {
        header("Access-Control-Allow-Origin: http://localhost:3000");

        //判断请求，options是浏览器的跨域运行判断请求，只发送header
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header("Access-Control-Allow-Methods: POST,GET,PUT");
            header("Access-Control-Allow-Headers: " . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
            exit; //结束，只需要返回头部即可
        }
    }

    public static function createFolder($dir)
    {
        return is_dir($dir) or (self::createFolder(dirname($dir)) and mkdir($dir, 0777));
    }

    public static function checkArg($class, $method, $needArg, $msg)
    {
        $msg = self::getMsgHeader($class, $method);
        if (!isset($needArg)) {
            $msg['msg'] = $msg;
            self::sendMsg($msg);
        }

        return true;
    }

    public static function getArg($key, $data)
    {
        if (!key_exists($key, $data)) {
            return false;
        }

        return $data[$key];
    }

    public static function createImageFromBase64($path, $data)
    {
        if (!$path) return false;
        return file_put_contents($path, base64_decode($data));
    }

    public static function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode, true)) return TRUE;
        if (!mkdirs(dirname($dir), $mode, true)) return FALSE;

        return @mkdir($dir, $mode, true);
    }

    public static function delDir($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        $handle = opendir($dir);
        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != "..") {
                is_dir("$dir/$file") ? self::delDir("$dir/$file") : @unlink("$dir/$file");
            }
        }
        if (readdir($handle) == false) {
            closedir($handle);
            @rmdir($dir);
            return true;
        }

        return false;
    }

    /**
     * Checks if a phone number is valid.
     * 匹配格式：
     * 11位手机号码
     * 3-4位区号，7-8位直播号码，1－4位分机号
     * '400' =>  '/^400(-\d{3,4}){2}$/',
     * 如：12345678901、1234-12345678-1234
     * @param string $phone number to check
     * @param int $lengths 检测一个电话号码的长度是否符合要求
     * @return  boolean
     */
    public static function checkPhone($number, $lengths = 11)
    {
        if (is_numeric($lengths)) {
            return ctype_digit($number) && strlen($number) == $lengths;
        } else {
            $reg = '/^[1](([3][0-9])|([4][5-9])|([5][0-3,5-9])|([6][5,6])|([7][0-8])|([8][0-9])|([9][1,8,9]))[0-9]{8}$/';
            return (bool)preg_match($reg, $number);
        }
    }

    public static function checkCode($code, $length = 4)
    {
        return strlen($code) == $length;
    }

    public static function checkPasswordLength($password, $min = 6, $max = 30)
    {
        $length = strlen($password);
        if ($length >= $min && $length <= 30) {
            return true;
        }

        return false;
    }

    public static function checkDate($data)
    {
        return date("Y-m-d H:i:s", strtotime($data)) == $data;
    }

    /**
     * 获取图片文件类型
     *
     * @param unknown $path
     * @param string $fixOrientation
     * @throws ImageWorkshopException
     * @return ImageWorkshopLayer
     */
    public static function getImageExtType($path)
    {
        if (false === filter_var($path, FILTER_VALIDATE_URL) && !file_exists($path)) {
            return '';
        }

        if (false === ($imageSizeInfos = @getImageSize($path))) {
            return '';
        }

        $mimeContentType = explode('/', $imageSizeInfos['mime']);
        if (!$mimeContentType || !isset($mimeContentType[1])) {
            return '';
        }

        $mimeContentType = $mimeContentType[1];

        $ext = '';

        switch ($mimeContentType) {
            case 'jpeg':
                $ext = 'jpg';
                break;
            case 'gif':
                $ext = 'gif';
                break;
            case 'png':
                $ext = 'png';
                break;

            default:
                $ext = '';
                break;
        }

        return $ext;
    }

    /**
     * 通过生日获取年龄
     * @param $birthday
     * @return bool|false|int
     */
    public static function getAge($birthday){
        $age = strtotime($birthday);
        if($age === false){
            return false;
        }
        list($y1,$m1,$d1) = explode("-",date("Y-m-d",$age));
        $now = strtotime("now");
        list($y2,$m2,$d2) = explode("-",date("Y-m-d",$now));
        $age = $y2 - $y1;
        if((int)($m2.$d2) < (int)($m1.$d1))
            $age -= 1;
        return $age;
    }

    /**
     * 调用sendMsg快捷方式
     * @param $data
     * @param null $flag
     */
    public static function C($data, $flag = null)
    {
        return self::sendMsg($data, $flag);
    }


    public static function CC ($array, $msg, $type, $flag = null) {
        switch ($type) {
            case D_DEFINE_ERROR_TYPE::ERROR:
                $array['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
                break;
            case D_DEFINE_ERROR_TYPE::OK:
                $array['code'] = D_COMM_STATUS_CODE::OK;
                break;
        }
        $array['msg'] = $msg;
        return self::sendMsg($array, $flag);
    }

    public static function sendMsg($data, $flag = null)
    {
        if (!$flag)
            $flag = JSON_NUMERIC_CHECK | JSON_BIGINT_AS_STRING;

        $outStr = json_encode($data, $flag);
        RuntimeLogger::Debug($outStr);

        // Getting a response instance
        $response = new Response();

        if (DEBUG_MODE)
        {
            $response->setHeader('Access-Control-Allow-Origin', '*');
        }
        else
        {
            // 如果是发布环境那么请把domain改成后台的域名
            $response->setHeader('Access-Control-Allow-Origin', 'domain');
        }

        $response->setHeader('Access-Control-Allow-Headers', 'Keep-Alive,User-Agent,X-Requested-With,Cache-Control,Content-Type,token');
        $response->setHeader('Access-Control-Allow-Methods', 'POST,GET,OPTION');

        $response->setJsonContent($data);
        $response->setStatusCode(200, '');
        $response->send();
        return ;
    }

    public static function getMsgHeader($class = __CLASS__, $method = __METHOD__)
    {
        $list = explode("\\", $class);
        $count = count($list);
        if ($count == 0) {
            return [];
        }

        $class = $list[$count - 1];

        $explodeList = explode("::", $method);

        $count = count($explodeList);

        if ($count == 0) {
            return [];
        }

        $real_method = $explodeList[$count - 1];

        return ["obj" => str_replace("Controller", "", $class), "act" => str_replace("Action", "", $real_method), "code" => D_COMM_STATUS_CODE::GENERAL_ERROR];
    }

    /*
     * 将时间转换为几分钟前或者几秒前
     */
    public static function timeTransform($the_time)
    {
        $now_time = date("Y-m-d H:i:s", time());
        $now_time = strtotime($now_time);
        $show_time = strtotime($the_time);
        $dur = $now_time - $show_time;
        if ($dur < 0) {
            return $the_time;
        } else {
            if ($dur < 60) {
                return '刚刚';
            } else {
                if ($dur < 3600) {
                    return floor($dur / 60) . '分钟前';
                } else {
                    if ($dur < 86400) {
                        return floor($dur / 3600) . '小时前';
                    } else {
                        if ($dur < 259200) {//3天内
                            return floor($dur / 86400) . '天前';
                        } else {
                            return $the_time;
                        }
                    }
                }
            }
        }
    }

    public static function numberFormat($var)
    {
        return number_format($var, 2,'.', '');
    }

    public static function convertHeadPic($headPic)
    {
        return HEAD_PIC_PATH . $headPic;
    }

    public static function convertPlatformPic($platformPic)
    {
        return PLATFORM_PIC_PATH . $platformPic;
    }

    public static function getUniqueID()
    {
        return date("YmdHis") . rand(0, 1000000);
    }

    public static function checkBanArea($country, $city)
    {
        $banArea = false;

        if ($country != "中国") {
            $banArea = true;
        } else {
            if ($city == "背景" || $city == "贵阳" || $city == "安顺" || $city == "香港" || $city == "台湾") {
                $banArea = true;
            }
        }

        return $banArea;
    }

    public static function checkBanAreaByIp($ip)
    {
        $data = Ip::find($ip);
        $country = $data[0];
        $province = $data[1];
        $city = $data[2];
        return self::checkBanArea($country, $city);
    }

    /**
     * 转换数组中的key下划线为驼峰
     * @param $arr
     * @return array
     */
    public static function convertUnderlineArray($arr)
    {

        $retArr = [];
        foreach ($arr as $key => $value)
        {
            $retArr[self::convertUnderline($key)] = $value;
        }

        return $retArr;
    }

    /**
     * 下划线转驼峰
     * @param string $str
     * @return string
     */
    public static function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }

    /**
     * 驼峰转下划线
     * @param string $str
     * @return string
     */
    public static function humpToLine($str)
    {
        $str = str_replace("_", "", $str);
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        return ltrim($str, "_");
    }

    /**
     * 转化为客户端所用的数据
     * @param $arr
     * @return array $newData
     */
    public static function convertClientData($arr)
    {
        $newData = [];
        foreach ($arr as $key => $value)
        {
            $newKey = self::convertUnderline($key);
            $newData[$newKey] = $value;
        }

        return $newData;
    }

    /**
     * 随机字符串
     * @param int $length
     * @return string
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    /**
     * 签名结果
     * @param $params
     * @param $appkey
     * @return string
     */
    public static function getReqSign($params /* 关联数组 */, $appkey /* 字符串*/)
    {
        // 1. 字典升序排序
        ksort($params);// 2. 拼按URL键值对
        $str = '';
        foreach ($params as $key => $value) {
            if ($value !== '') {
                $str .= $key . '=' . urlencode($value) . '&';
            }
        }// 3. 拼接app_key
        $str .= 'app_key=' . $appkey;
        // 4. MD5运算+转换大写，得到请求签名
        $sign = strtoupper(md5($str));
        return $sign;
    }


    /**
     * 根据唯一的id生成对应的码
     * @param $id
     * @return string
     */
    public static function createInvitCode($id)
    {
        $source_string = 'E5FCDG3HQA4B1NOPIJ2RSTUV67MWX89KLYZ';
        $num = $id;
        $code = '';
        while ( $num > 0) {
        $mod = $num % 35;
        $num = ($num - $mod) / 35;
        $code = $source_string[$mod].$code;
        }
        if(empty($code[3]))
            $code = str_pad($code,4,'0',STR_PAD_LEFT);
        return $code;

    }
}