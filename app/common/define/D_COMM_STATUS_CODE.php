<?php
/**
 * Created by PhpStorm.
 * User: guihc
 * Date: 2019/3/25
 * Time: 14:30
 */

namespace common\define;


abstract class D_COMM_STATUS_CODE
{
    const OK = 20000;                               // 正常状态


    // ---- 文件操作相关
    const IMAGE_CREATE_FAILED = 40001;              // 创建图片失败
    const CREATE_FOLDER_FAILED = 40002;             // 创建目录失败

    const TOKEN_ILLEGAL = 50008;                    // 非法token
    const TOKEN_MUTEX = 50012;                      // 其他客户端登陆
    const TOKEN_OUT_TIME = 50014;                   // 超时token

    // ---- 请求验证相关
    const REQUEST_POST_ERROR = 60001;               // 只允许进行POST请求
    const REQUEST_CONTROLLER_ACTION = 60002;        // 请求controller action 不匹配
    const REQUEST_PARAMS_ERROR = 60003;             // 请求参数错误
    const REQUEST_SMS_CODE_TIME_LIMIT = 60004;      // 验证码超时

    // ---- 数据库更新、保存相关
    const DATABASE_OPERATE_ERROR = 70001;          // 数据库操作失败

    // 用户属性相关
    const ACCOUNT_HAS_BAN = 80002;            // 用户被禁用
    const REG_ACCOUNT_FAILED = 80003;            // 注册失败
    const LOGIN_ACCOUNT_FAILED = 80004;         // 登陆失败
    const LOGIN_ACCOUNT_UNREGIST = 80005;       // 用户尚未注册
    const ROLE_TYPE_ERROR = 80006;              // 角色类型错误

    const GENERAL_ERROR = 90001;                   // 通用错误

    // http协议相关
    const HTTP_CODE_OK = 200;
}