<?php
/**
 * Created by PhpStorm.
 * User: guihouchang
 * Date: 2018/12/29
 * Time: 6:39 PM
 */

namespace common\define;


abstract class D_SMS_CODE_TYPE
{
    const ADD_BANK_CARD = 4;        // 添加收款账号验证
    const FORGET_PASSWORD = 3;      //修改密码
    const LOGIN = 2;
    const REGISTER = 1;
    const FORGET_WITHDRAW_PASSWORD = 5; // 忘记提现密码

    const SMS_TIME_LIMIT = 5 * 60;
    const SMS_CODE_LENGTH = 6;

    const SMS_CODE_STATE_UNUSED = 1;
    const SMS_CODE_STATE_USED = 0;

    const APP_ID = 1400148854;
    const APP_KEY = '4d72884dcedada9667ca7ef7baedab7c';
    const TEMPLATE_ID = 208456;
    const REQUEST_URL = 'https://yun.tim.qq.com/v5/tlssmssvr/sendsms?sdkappid=' . self::APP_ID . "&random=%s";
    const NATION_CODE_LIST = [
        "CN" =>  86,
    ];
}
