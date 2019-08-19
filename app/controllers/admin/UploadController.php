<?php

namespace app\controllers\admin;

use common\define\D_COMM_STATUS_CODE;
use common\util;

class UploadController extends ControllerAdminBase
{

    protected $filterList = [
        "uploadImage"
    ];

    public function indexAction()
    {

    }

    /**
     * 上传文件
     */
    public function uploadImageAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);

        if ($this->request->hasFiles())
        {
            $uploadFiles = $this->request->getUploadedFiles();

            $config = $this->config;
            $tempPath = $config->resource->tmpDir;
            $tempUrl = $config->resource->tmpUrl;
            util::mkdirs($tempPath);

            $fileCount = count($uploadFiles);
            if ($fileCount > 0)
            {
                foreach ($uploadFiles as $file)
                {
                    $filename = uniqid("image_") . "." . $file->getExtension();
                    if ($file->moveTo($tempPath . '/' . $filename))
                    {
                        $msg['code'] = D_COMM_STATUS_CODE::OK;
                        $msg['data'] = [
                            "name" => $filename,
                            "url" => $tempUrl . '/' . $filename
                        ];

                        util::C($msg);
                        return ;
                    }
                }
            }
        }

        $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
        $msg['msg'] = '上传失败';
        util::C($msg);
    }

}

