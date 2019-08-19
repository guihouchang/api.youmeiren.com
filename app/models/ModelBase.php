<?php
/**
 * Created by PhpStorm.
 * User: guihc
 * Date: 2019/3/25
 * Time: 16:33
 * 模型层基类
 */

use common\RuntimeLogger;

class ModelBase extends Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->useDynamicUpdate(true);
    }

    public function notSaved()
    {
        $messages = $this->getMessages();

        $arr = debug_backtrace();
        $class = $arr[5]['class'];
        $action = $arr[5]['function'];

        // Show validation messages
        foreach ($messages as $message) {
            $msg = [
                'obj' => $class,
                'act' => $action,
                'msg' => $message->getMessage(),
            ];
            RuntimeLogger::Error(json_encode($msg));
        }
    }

    public function notDeleted()
    {
        $messages = $this->getMessages();

        $arr = debug_backtrace();
        $class = $arr[5]['class'];
        $action = $arr[5]['function'];

        // Show validation messages
        foreach ($messages as $message) {
            $msg = [
                'obj' => $class,
                'act' => $action,
                'msg' => $message->getMessage(),
            ];
            RuntimeLogger::Error(json_encode($msg));
        }
    }

    public function getErrorMessage()
    {
        $strErrInfo = '';
        $messages = $this->getMessages();
        foreach ($messages as $message)
        {
            $strErrInfo .= $message->getMessage() . ',';
        }

        return substr($strErrInfo, 0 , -1);
    }
}