<?php
/**
 * Created by PhpStorm.
 * User: guihc
 * Date: 2019/3/25
 * Time: 16:02
 */

namespace common;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\File as FileAdapter;
use common\logger\MyLogger;

class RuntimeLogger
{
    public static function InfoAction($msg)
    {
        self::Log(MyLogger::ACTION_INFO, $msg);
    }

    public static function InfoMySql($msg)
    {
        self::Log(MyLogger::MYSQL_INFO, $msg);
    }

    public static function Info($msg)
    {
        self::Log(MyLogger::INFO, $msg);
    }

    public static function Custom($msg)
    {
        self::Log(MyLogger::CUSTOM, $msg);
    }

    public static function Debug($msg)
    {
        self::Log(MyLogger::DEBUG, $msg);
    }

    public static function Warning($msg)
    {
        self::Log(MyLogger::WARNING, $msg);
    }

    public static function Error($msg)
    {
        self::Log(MyLogger::ERROR, $msg);
    }

    public static function Alert($msg)
    {
        self::Log(MyLogger::ALERT, $msg);
    }

    public static function Critical($msg)
    {
        self::Log(MyLogger::CRITICAL, $msg);
    }

    public static function Emergence($msg)
    {
        self::Log(MyLogger::EMERGENCE, $msg);
    }

    public static function Log($type, $msg)
    {
        $di = \Phalcon\Di::getDefault();
        $config = $di->getConfig();
        $logDir = $config->application->logDir;
        is_dir($logDir) OR mkdir($logDir, 0777, true);
        $logger = null;

        switch ($type)
        {
            case MyLogger::INFO:
                $logger = new FileAdapter(BASE_PATH .'/logs/info_' . date("Y-m-d", time()) . ".log");
                break;
            case MyLogger::CUSTOM:
                $logger = new FileAdapter(BASE_PATH .'/logs/custom_' . date("Y-m-d", time()) . ".log");
                break;
            case MyLogger::DEBUG:
                $logger = new FileAdapter(BASE_PATH .'/logs/debug_' . date("Y-m-d", time()) . ".log");
                break;
            case MyLogger::WARNING:
                $logger = new FileAdapter(BASE_PATH .'/logs/warning_' . date("Y-m-d", time()) . ".log");
                break;
            case MyLogger::ERROR:
                $logger = new FileAdapter(BASE_PATH .'/logs/error_' . date("Y-m-d", time()) . ".log");
                break;
            case MyLogger::ALERT:
                $logger = new FileAdapter(BASE_PATH .'/logs/alert_' . date("Y-m-d", time()) . ".log");
                break;
            case MyLogger::CRITICAL:
                $logger = new FileAdapter(BASE_PATH .'/logs/critical_' . date("Y-m-d", time()) . ".log");
                break;
            case MyLogger::EMERGENCE:
                $logger = new FileAdapter(BASE_PATH .'/logs/emergence_' . date("Y-m-d", time()) . ".log");
                break;
            case MyLogger::MYSQL_INFO:
                $type = MyLogger::DEBUG;
                $logger = new FileAdapter(BASE_PATH . '/logs/mysql_' . date("Y-m-d", time()) . ".log");
                break;
            case MyLogger::ACTION_INFO:
                $type = MyLogger::DEBUG;
                $logger = new FileAdapter(BASE_PATH . '/logs/action_' . date("Y-m-d", time()) . ".log");
                break;
            default:
                $logger = new FileAdapter(BASE_PATH .'/logs/info_' . date("Y-m-d", time()) . ".log");
        }

        $logger->begin();
        $logger->log($type, $msg);
        $logger->commit();
    }

}