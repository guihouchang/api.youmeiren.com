<?php
use common\util;
use common\define\D_COMM_STATUS_CODE;
use common\define\D_DEFINE_USER_INFO;
use common\define\D_SMS_CODE_TYPE;
use ucpaas\UcpaasObject;


class UserController extends ControllerBase
{
    protected $filterList = [
        "register",
        "login",
        "resetPassword",
        "sendSms",
        "index",
        "logEvent"
    ];

    /**
     * 记录事件
     * eventName
     */
    public function logEventAction()
    {
       $eventName = $this->getPost('eventName');
       if (!isset($eventName))
       {
           return ;
       }

       $ip = $this->request->getClientAddress();
       $areaData = \Zhuzhichao\IpLocationZh\Ip::find($ip);
       $usrEventLog = new UsrEventLog();
       $usrEventLog->setEventName($eventName);
       $usrEventLog->setIp($ip);
       $usrEventLog->setIpInfo($areaData);
       $usrEventLog->save();

    }

    public function getCenterAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);

        $msg['data'] = [
            "name" => $this->userInfo->getName(),
            "icon" => $this->config->resource->iconUrl . '/' . $this->userInfo->getIcon(),
        ];

        $msg['code'] = D_COMM_STATUS_CODE::OK;
        util::C($msg);
    }

    /**
     * 编辑头像和名称
     * name:
     * file:
     */
    public function editProfileAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $name = $this->getPost('name');
        $userInfo = $this->userInfo;

        $uploadFiles = $this->request->getUploadedFiles();
        if (count($uploadFiles) > 0)
        {
            $config = $this->config;
            $path = $config->resource->iconDir;
            util::mkdirs($path);

            $file = $uploadFiles[0];
            $fileName = uniqid() . "." . $file->getExtension();
            if (!$file->moveTo($path . '/' . $fileName))
            {
                $msg['msg'] = '保存文件失败';
                $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
                util::C($msg);
                return ;
            }

            $userInfo->setIcon($fileName);
        }

        if (isset($name))
        {
            $userInfo->setName($name);
        }

        if (!$userInfo->save())
        {
            $msg['code'] = D_COMM_STATUS_CODE::DATABASE_OPERATE_ERROR;
            $msg['msg'] = $userInfo->getErrorMessage();
            util::C($msg);
            return ;
        }

        $msg['code'] = D_COMM_STATUS_CODE::OK;
        $msg['msg'] = "修改成功";
        util::C($msg);
    }


    public function indexAction()
    {
        // UcpaasObject::getInstance();
    }

    /**
     * 重置密码
     * phone:
     * password:
     * code:
     */
    public function resetPasswordAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $phone = $this->getPost('phone');
        $password = $this->getPost('password');
        $code = $this->getPost('code');

        if (!util::checkPhone($phone)) {
            $msg['msg'] = '手机格式不正确';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return;
        }

        $passwordLen = strlen($password);
        if ($passwordLen < D_DEFINE_USER_INFO::MIN_PASSWORD_LENGTH
            || $passwordLen > D_DEFINE_USER_INFO::MAX_PASSWORD_LENGTH)
        {
            $msg['msg'] = '密码格式不正确';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        $codeLen = strlen($code);
        if ($codeLen != D_SMS_CODE_TYPE::SMS_CODE_LENGTH)
        {
            $msg['msg'] = '验证码格式不正确';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        $userInfo = UsrUserInfo::findFirst([
            "account = ?0 AND status = 1",
            "bind" => [
                $phone,
            ],
        ]);

        if (!$userInfo) {
            $msg['msg'] = '该用户尚未注册';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return;
        }

        if ($userInfo->getStatus() == \common\define\D_DEFINE_COMMON_STATE::ABNORMAL)
        {
            $msg['code'] = D_COMM_STATUS_CODE::ACCOUNT_HAS_BAN;
            $msg['msg'] = '账号已经封停';
            util::C($msg);
            return ;
        }

        $userInfo->setPassword(password_hash($password, PASSWORD_DEFAULT));
        if (!$userInfo->save())
        {
            $msg['msg'] = $userInfo->getErrorMessage();
            $msg['code'] = D_COMM_STATUS_CODE::DATABASE_OPERATE_ERROR;
            util::C($msg);
            return ;
        }

        $msg['code'] = D_COMM_STATUS_CODE::OK;
        $msg['msg'] = '设置成功';
        util::C($msg);
    }

    /**
     * 发送短信
     * phone: 手机号
     * type: 1 注册 2 手机快速登录 3 修改密码
     */
    public function sendSmsAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $phone = $this->getPost('phone');
        $type = $this->getPost('type') ?? D_SMS_CODE_TYPE::REGISTER;

        if (!util::checkPhone($phone)) {
            $msg['msg'] = '手机格式不正确';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return;
        }

        if ($type > D_SMS_CODE_TYPE::FORGET_WITHDRAW_PASSWORD
            || $type < D_SMS_CODE_TYPE::REGISTER)
        {
            $msg['msg'] = '验证码类型不正确';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        $usrSms = UsrSmsCode::findFirst([
            "phone = ?0 AND type = ?1",
            "bind" => [
                $phone,
                $type,
            ],
        ]);

        $code = sprintf('%06d', rand(0, 999999));
        UcpaasObject::getInstance()->sendSms($phone, $code);

        if (!$usrSms)
        {
            $usrSms = new UsrSmsCode();
            $usrSms->setCode($code);
            $usrSms->setPhone($phone);
            $usrSms->setType($type);
        }
        else
        {
            $usrSms->setCode($code);
        }

        if (!$usrSms->save())
        {
            $msg['msg'] = $usrSms->getErrorMessage();
            $msg['code'] = D_COMM_STATUS_CODE::DATABASE_OPERATE_ERROR;
            util::C($msg);
            return ;
        }

        $msg['code'] = D_COMM_STATUS_CODE::OK;
        $msg['msg'] = '发送短信成功';
        util::C($msg);

    }

    /**
     * 用户登录
     * phone:
     * password:
     */
    public function loginAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $phone = $this->getPost('phone');
        $password = $this->getPost('password');

        if (!util::checkPhone($phone)) {
            $msg['msg'] = '手机格式不正确';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return;
        }

        $passwordLen = strlen($password);
        if ($passwordLen < D_DEFINE_USER_INFO::MIN_PASSWORD_LENGTH
            || $passwordLen > D_DEFINE_USER_INFO::MAX_PASSWORD_LENGTH
        ) {
            $msg['msg'] = '密码格式不正确';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return;
        }

        $userInfo = UsrUserInfo::findFirst([
            "account = ?0 AND status = 1",
            "bind" => [
                $phone,
            ],
        ]);

        if (!$userInfo) {
            $msg['msg'] = '该用户尚未注册';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return;
        }

        if ($userInfo->getStatus() == \common\define\D_DEFINE_COMMON_STATE::ABNORMAL)
        {
            $msg['code'] = D_COMM_STATUS_CODE::ACCOUNT_HAS_BAN;
            $msg['msg'] = '账号已经封停';
            util::C($msg);
            return ;
        }

        if (!password_verify($password, $userInfo->getPassword()))
        {
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            $msg['msg'] = '用户名密码不正确';
            util::C($msg);
            return ;
        }

        if (password_needs_rehash($userInfo->getPassword(), PASSWORD_DEFAULT))
        {
            $userInfo->setPassword(password_hash($password, PASSWORD_DEFAULT));
        }

        $userInfo->setToken(util::createToken($phone));
        if (!$userInfo->save())
        {
            $msg['msg'] = $userInfo->getErrorMessage();
            $msg['code'] = D_COMM_STATUS_CODE::DATABASE_OPERATE_ERROR;
            util::C($msg);
            return ;
        }

        $msg['msg'] = '登录成功';
        $msg['code'] = D_COMM_STATUS_CODE::OK;
        $msg['data'] = [
            "token" => $userInfo->getToken(),
        ];

        util::C($msg);
    }

    /**
     * 用户注册
     * phone:
     * password:
     * code:
     */
    public function registerAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $phone = $this->getPost('phone');
        $password = $this->getPost('password');
        $code = $this->getPost('code');

        if (!util::checkPhone($phone))
        {
            $msg['msg'] = '手机格式不正确';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        $passwordLen = strlen($password);
        if ($passwordLen < D_DEFINE_USER_INFO::MIN_PASSWORD_LENGTH
            || $passwordLen > D_DEFINE_USER_INFO::MAX_PASSWORD_LENGTH)
        {
            $msg['msg'] = '密码格式不正确';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        $codeLen = strlen($code);
        if ($codeLen != D_SMS_CODE_TYPE::SMS_CODE_LENGTH)
        {
            $msg['msg'] = '验证码格式不正确';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        $userInfo = UsrUserInfo::findFirstByAccount($phone);
        if ($userInfo)
        {
            $msg['msg'] = '该手机已经注册';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        if (!$this->checkSmsCode($phone, $code, D_SMS_CODE_TYPE::REGISTER))
        {
            $msg['msg'] = '验证码不正确';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        // 检查完毕，进行创建账号
        $userInfo = new UsrUserInfo();
        $userInfo->setAccount($phone);
        $userInfo->setPassword(password_hash($password, PASSWORD_DEFAULT));
        $userInfo->setIcon(D_DEFINE_USER_INFO::DEFAULT_ICON);
        $userInfo->setToken(util::createToken($phone));
        if (!$userInfo->save())
        {
            $msg['msg'] = '注册账号失败';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        $msg['msg'] = '注册成功';
        $msg['code'] = D_COMM_STATUS_CODE::OK;
        util::C($msg);
    }
}

