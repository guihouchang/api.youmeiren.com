<?php

use Phalcon\Mvc\Controller;
use common\util;
use common\Define\D_COMM_STATUS_CODE;
use common\Define\D_SMS_CODE_TYPE;
use common\RuntimeLogger;

/**
 * Class ControllerBase
 * @property UsrUserInfo $userInfo
 * @property \Phalcon\Config $config
 * @property Phalcon\Mvc\Model\Transaction\Manager $transactions
 * @method Phalcon\Mvc\Model\Transaction\Manager getTransactions()
 */
class ControllerBase extends Controller
{
    private $startExecuteTime = 0;
    protected $filterList = []; // 不要token的action


    protected function checkRoleType($roleType, $msg)
    {

    }

    public function beforeExecuteRoute($dispatcher)
    {
        $this->startExecuteTime = microtime(true);
        return $this->checkControllerToken();
    }

    public function afterExecuteRoute($dispatcher)
    {
        $endTime = microtime(true);
        $actionProfiles = [
            "controller" => $dispatcher->getControllerName(),
            "action" => $dispatcher->getActionName(),
            "startTime" => $this->startExecuteTime,
            "endTime" => $endTime,
            "elapsedTime" => $endTime - $this->startExecuteTime,
        ];

        RuntimeLogger::InfoAction(json_encode($actionProfiles));

        $profiles = $this->getDI()->get('profiler')->getProfiles();

        $strLog = '';

        if (!$profiles) return;
        foreach ($profiles as $profile) {
            $strLog .= 'SQL Statement: ' . $profile->getSQLStatement() . "\n";
            $strLog .= 'Start Time: ' . $profile->getInitialTime() . "\n";
            $strLog .= 'Final Time: ' . $profile->getFinalTime() . "\n";
            $strLog .= 'Total Elapsed Time: ' . $profile->getTotalElapsedSeconds() . "\n";
        }

        RuntimeLogger::InfoMySql($strLog);

        $controllerName = $dispatcher->getControllerName();
        $actionName = $dispatcher->getActionName();

        if (!$controllerName || !$actionName)
        {
            $msg = Util::getMsgHeader(__CLASS__, __METHOD__);
            $msg['msg'] = 'object or action failed';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_CONTROLLER_ACTION;
            Util::sendMsg($msg);
            return ;
        }

        $queryData = $_REQUEST;
        unset($queryData['_url']);

        $msg = [
            "controller" => $controllerName,
            "action" => $actionName,
        ];
        $msg = array_merge($msg, $queryData);
        RuntimeLogger::Info(json_encode($msg));
    }

    public function getHeader($key)
    {
        $data = $this->request->getHeader($key);
        if (!$data) return null;
        return $data;
    }

    public function getPost($key)
    {
        $data = $this->request->getPost($key);

        if ($data === 0) return 0;

        if (!$data) return null;

        if (is_string($data))
        {
            $jsonData = json_decode($data, true);
            if ($jsonData) return $jsonData;
        }

        return $data;
    }

    public function onConstruct()
    {

    }

    protected function getTokenUser($token)
    {
        $usrInfo = \UsrUserInfo::findFirst([
            "token = :token: AND status = 1",
            "bind" => [
                "token" => $token,
            ],
        ]);

        if (!$usrInfo) {
            return null;
        }

        return $usrInfo;
    }

    final protected function checkControllerToken()
    {
        // 检查token
        $dispatcher = $this->dispatcher;
        $controllerName = $dispatcher->getControllerName();
        $actionName = $dispatcher->getActionName();

        if (!in_array($actionName, $this->filterList)) {
            $msg = util::getMsgHeader(__CLASS__, __METHOD__);
            $token = $this->getHeader('token');
            if (!isset($token)) {
                $msg['msg'] = 'token参数为空';
                $msg['code'] = D_COMM_STATUS_CODE::TOKEN_ILLEGAL;
                util::C($msg);
                return false;
            }

            $usrInfo = $this->getTokenUser($token);
            if (!$usrInfo)
            {
                $msg['msg'] = '错误的token';
                $msg['code'] = D_COMM_STATUS_CODE::TOKEN_ILLEGAL;
                util::C($msg);
                return false;
            }

            $this->getDI()->setShared("userInfo", function () use ($usrInfo) {
                return $usrInfo;
            });

            return true;
        }
    }

    protected function checkToken($token, $msg = [])
    {
        if (!isset($token))
        {
            return false;
        }

        $usrAccountInfo = UsrUserInfo::findFirst([
            'status = 1 AND token = :token:',
            "bind" => [
                "token" => $token,
            ],
        ]);

        if (!$usrAccountInfo)
        {
            return false;
        }

        return $usrAccountInfo;
    }

    public function checkSmsCode($phone, $code, $type)
    {

        $usrSmsCode = UsrSmsCode::findFirst([
            "status = :status: AND code = :code: AND phone = :phone: AND type = :type:",
            "bind" => [
                "status" => D_SMS_CODE_TYPE::SMS_CODE_STATE_UNUSED,
                "code" => $code,
                "phone" => $phone,
                "type" => $type,
            ],
        ]);

        if (!$usrSmsCode)
        {
            return false;
        }

        $curTime = time();
        $updateTimeStamp = strtotime($usrSmsCode->getUpdateTime());
        if ($curTime - $updateTimeStamp > D_SMS_CODE_TYPE::SMS_TIME_LIMIT)
        {
            return false;
        }

        $usrSmsCode->setStatus(\common\define\D_DEFINE_COMMON_STATE::ABNORMAL);

        if (!$usrSmsCode->save())
        {
           return false;
        }
        return true;
    }
}
