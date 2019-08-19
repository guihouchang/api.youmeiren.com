<?php


namespace app\controllers\admin;

use common\define\D_COMM_STATUS_CODE;
use common\define\D_DEFINE_ADMIN_CONTROLLER_FILTER;
use common\util;

class ControllerAdminBase extends \ControllerBase
{
    protected function getTokenUser($token)
    {
        $usrInfo = \UsrAdminUser::findFirst([
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
}