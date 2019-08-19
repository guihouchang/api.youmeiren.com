<?php


namespace common\define;


abstract class D_DEFINE_ADMIN_CONTROLLER_FILTER
{
    const WITHOUT_TOKEN_CONTROLLER_LIST = [
        "user" => [
            "login",
            "logout"
        ],
    ];

    public static function NeedCheckToken($controller, $action)
    {
        if (key_exists($controller, self::WITHOUT_TOKEN_CONTROLLER_LIST))
        {
            if (in_array($action, self::WITHOUT_TOKEN_CONTROLLER_LIST[$controller]))
            {
                return true;
            }
        }

        return false;
    }
}