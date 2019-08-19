<?php

namespace ucpaas;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/3
 * Time: 23:37
 */
class UcpaasObject
{
    private $m_ucpass;
    private static $_instace;
    const APPID = "0524b39eef8d408b8e28c86a49cd7bdc";
    const TEMPLATEID = 46897;

    public function sendSms($to_phone_num, $code = null)
    {
        $param = $code;
        $this->m_ucpass->templateSMS(self::APPID, $to_phone_num, self::TEMPLATEID, $param);

        return $param;
    }

    // 获得实例
    public static function getInstance()
    {
        if (! (self::$_instace instanceof self)) {
            self::$_instace = new self();
        }

        return self::$_instace;
    }

    public function __construct()
    {
        $options['accountsid']='b2aaa366c7f6544589c79101c0932a6b';
        $options['token']='6e6bdd47beed2de20775706ad5cd1c8c';
        $this->m_ucpass = new Ucpaas($options);
    }

}
