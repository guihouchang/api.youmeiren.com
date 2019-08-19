<?php
namespace app\controllers\admin;

use common\define\D_COMM_STATUS_CODE;
use common\util;
use MongoDB\BSON\UTCDateTime;

class UserController extends ControllerAdminBase
{
    protected $filterList = [
        "login",
        "logout",
        "index"
    ]; // 不要token的action

    public function indexAction()
    {
        $admin = 'admin';
        echo password_hash($admin, PASSWORD_DEFAULT);
    }

    /**
     * 后台用户登录
     * username :
     * password:
     */
    public function loginAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $account = $this->getPost('username');
        $password = $this->getPost('password');

        if (!isset($account))
        {
            $msg['msg'] = '账号为空';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        if (!isset($password))
        {
            $msg['msg'] = '密码为空';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        $adminUser = \UsrAdminUser::findFirst([
            "account = ?0 AND status = 1",
            "bind" => [
                $account,
            ],
        ]);

        if (!$adminUser)
        {
            $msg['msg'] = '账号密码不正确';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        if (!password_verify($password, $adminUser->getPassword()))
        {
            $msg['msg'] = '用户密码不正确';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        if (password_needs_rehash($adminUser->getPassword(), PASSWORD_DEFAULT))
        {
            $adminUser->setPassword(password_hash($password, PASSWORD_DEFAULT));
        }

        $adminUser->setToken(util::createToken($account));
        if (!$adminUser->save())
        {
            $msg['msg'] = '保存数据失败';
            $msg['code'] = D_COMM_STATUS_CODE::DATABASE_OPERATE_ERROR;
            util::C($msg);
            return ;
        }

        $msg['code'] = D_COMM_STATUS_CODE::OK;
        $msg['data'] = [
            "token" => $adminUser->getToken(),
        ];

        util::C($msg);
    }

    public function infoAction()
    {
        $a = [
            "roles" => ['admin'],
    "introduction"=> 'I am a super administrator',
    "avatar" => 'https://wpimg.wallstcn.com/f778738c-e4f8-4870-b634-56703b4acafe.gif',
    "name" => 'Super Admin'
        ];

        $msg['code'] = D_COMM_STATUS_CODE::OK;
        $msg['data'] = $a;
        util::C($msg);
    }

}

