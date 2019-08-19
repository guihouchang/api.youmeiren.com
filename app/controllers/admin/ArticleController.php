<?php
namespace app\controllers\admin;

use common\define\D_ARTICLE_DEFINE;
use common\define\D_COMM_STATUS_CODE;
use common\define\D_DEFINE_COMMON_STATE;
use common\util;

class ArticleController extends ControllerAdminBase
{
    protected $filterList = [
        "saveArticle",
        "index"
    ];

    public function indexAction()
    {
        $usrArticle = \UsrArticleInfo::findFirst(13);
        print_r($usrArticle->getUsrUserInfo());
    }

    public function delTypeAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $id = $this->getPost('id');
        if (!isset($id))
        {
            $msg['msg'] = 'id为空';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        $usrTypeConfig = \UsrArticleTypeConfig::findFirst($id);
        if (!$usrTypeConfig)
        {
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            $msg['msg'] = '无法找到对应数据';
            util::C($msg);
            return ;
        }

        $usrTypeConfig->delete();
        $msg['msg'] = "删除成功";
        $msg['code'] = D_COMM_STATUS_CODE::OK;
        util::C($msg);
    }

    public function addTypeAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $name = $this->getPost('name');

        if (!isset($name))
        {
            $msg['msg'] = 'name为空';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        $usrTypeConfig = new \UsrArticleTypeConfig();
        if (!$usrTypeConfig)
        {
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            $msg['msg'] = '无法找到对应数据';
            util::C($msg);
            return ;
        }

        $usrTypeConfig->setName($name);
        $usrTypeConfig->save();
        $msg['msg'] = '更新成功';
        $msg['code'] = D_COMM_STATUS_CODE::OK;
        util::C($msg);
    }


    /**
     * 修改文类型
     */
    public function modifyTypeAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $id = $this->getPost('id');
        $name = $this->getPost('name');
        if (!isset($id))
        {
            $msg['msg'] = 'id为空';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        if (!isset($name))
        {
            $msg['msg'] = 'name为空';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        $usrTypeConfig = \UsrArticleTypeConfig::findFirst($id);
        if (!$usrTypeConfig)
        {
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            $msg['msg'] = '无法找到对应数据';
            util::C($msg);
            return ;
        }

        $usrTypeConfig->setName($name);
        $usrTypeConfig->save();
        $msg['msg'] = '更新成功';
        $msg['code'] = D_COMM_STATUS_CODE::OK;
        util::C($msg);
    }

    /**
     * 获取文章类型列表
     */
    public function getTypeListAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $usrTypeList = \UsrArticleTypeConfig::find([
            "status = 1",
            "order" => 'update_time DESC'
        ]);


        $dataList = [];
        foreach ($usrTypeList as $articleTypeConfig)
        {
            $dataList[] = [
                "id" => $articleTypeConfig->getId(),
                "name" => $articleTypeConfig->getName(),
            ];
        }

        $msg['code'] = D_COMM_STATUS_CODE::OK;
        $msg['data'] = [
            "dataList" => $dataList,
        ];
        util::C($msg);
    }

    /**
     * 修改文章
     * @param id
     * @param data
     */
    public function saveArticleAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $id = $this->getPost('id') ?? 0;
        $data = $this->getPost('data') ?? [];

        if (!isset($id)) {
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            $msg['msg'] = 'id为空';
            util::C($msg);
            return ;
        }

        if (!isset($data)) {
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            $msg['msg'] = '数据错误';
            util::C($msg);
            return ;
        }

        $usrArticleInfo = \UsrArticleInfo::findFirst($id);
        if (!$usrArticleInfo) {
           if ($id != 0)
           {
               $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
               $msg['msg'] = '无法找到对数据';
               util::C($msg);
               return ;
           }

           $usrArticleInfo = new \UsrArticleInfo();
           $usrArticleInfo->setUserId(0);
           $usrArticleInfo->setUpdateTime(util::Date(time()));
           $usrArticleInfo->setCreateTime(util::Date(time()));
           $usrArticleInfo->setStatus(D_DEFINE_COMMON_STATE::NORMAL);
           $usrArticleInfo->setImages([]);
           $usrArticleInfo->setHot(0);
        }

        if (key_exists("title", $data)) {
            $usrArticleInfo->setTitle($data['title']);
        }

        if (key_exists("content", $data)) {
            $usrArticleInfo->setContent($data['content']);
        }

        if (key_exists("type", $data)) {
            $usrArticleInfo->setType($data['type']);
        }

        if (!$usrArticleInfo->save())
        {
            $msg['msg'] = '保存失败';
            $msg['code'] = D_COMM_STATUS_CODE::DATABASE_OPERATE_ERROR;
            util::C($msg);
            return ;
        }

        if (key_exists("images", $data)) {
            $config = $this->config;
            $tempDir = $config->resource->tmpDir;
            $articleDir = $config->resource->articleDir . '/' . $usrArticleInfo->getId();
            util::mkdirs($articleDir);
            util::mkdirs($tempDir);
            $myImages = $usrArticleInfo->getImages() ?? [];
            if ($myImages)
            {
                foreach ($myImages as $name) {
                    if (!in_array($name, $data['images'])) {
                        // 删除文件
                        unlink($articleDir . '/' . $name);
                    }
                }

            }

            if ($data['images'])
            {
                foreach ($data['images'] as $name) {
                    if (!in_array($name, $myImages)) {
                        // mv文件
                        util::moveFile($tempDir . '/' . $name, $articleDir . '/' . $name);
                    }
                }
            }

            $usrArticleInfo->setImages($data['images']);
            if(!$usrArticleInfo->save())
            {
                $msg['code'] = D_COMM_STATUS_CODE::DATABASE_OPERATE_ERROR;
                $msg['msg'] = $usrArticleInfo->getErrorMessage();
                util::C($msg);
                return ;
            }
        }

        $msg['code'] = D_COMM_STATUS_CODE::OK;
        $msg['msg'] = '更新成功';
        util::C($msg);

    }

    /**
     * 删除文章
     * id
     */
    public function delArticleAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $id = $this->getPost('id');

        if (!isset($id))
        {
            $msg['msg'] = 'id为空';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        $usrArticleInfo = \UsrArticleInfo::findFirst($id);
        if (!$usrArticleInfo->delete())
        {
            $usrArticleInfo->getErrorMessage();
            $msg['msg'] = $usrArticleInfo->getErrorMessage();
            $msg['code'] = D_COMM_STATUS_CODE::DATABASE_OPERATE_ERROR;
            util::C($msg);
            return ;
        }

        $userReplyInfoList = \UsrReplyInfo::find([
            "article_id = :id:",
            "bind" => [
                "id" => $id,
            ],
        ]);

        if ($userReplyInfoList)
        {
            foreach ($userReplyInfoList as $usrReplyInfo)
            {
                $usrReplyInfo->delete();
            }
        }

        $msg['code'] = D_COMM_STATUS_CODE::OK;
        $msg['msg'] = '删除成功';
        util::C($msg);
    }

    /**
     * 获取文章列表
     * @param page int
     * @param condition array
     */
    public function getArticleListAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $page = $this->getPost('page') ?? 0;
        $condition = $this->getPost('condition') ?? [];
        $pageSize = 10;
        $start = $page * $pageSize;
        $count = 0;
        $usrArticleList = [];

        if (array_key_exists("type", $condition))
        {
            if ($condition['type'] == D_ARTICLE_DEFINE::SEARCH_TYPE_ID)
            {
                $usrArticleList = \UsrArticleInfo::find($condition['content']);
                $count = 1;
            }
            else if ($condition['type'] == D_ARTICLE_DEFINE::SEARCH_TYPE_USER)
            {

            }
            else if ($condition['type'] == D_ARTICLE_DEFINE::SEARCH_TYPE_TILE)
            {
                $usrArticleList = \UsrArticleInfo::find([
                    "title LIKE :title: AND status = 1",
                    "bind" => [
                        "title" => '%' . $condition['content'] . '%'
                    ],
                    "order" => 'update_time DESC',
                    "offset" => $start,
                    "limit" => $pageSize,
                ]);

                $count = \UsrArticleInfo::count([
                    "title LIKE :title: AND status = 1",
                    "bind" => [
                        "title" => '%' . $condition['content'] . '%'
                    ],
                ]);
            }
        }
        else
        {
            $usrArticleList = \UsrArticleInfo::find([
                "status = 1",
                "order" => "update_time DESC",
                "offset" => $start,
                "limit" => $pageSize,
            ]);

           $count =  \UsrArticleInfo::count([
                "status = 1"
            ]);
        }

        $totalPage = ceil($count / $pageSize);
        $dataList = [];
        $config = $this->config;
        $path = $config->resource->articleUrl;
        foreach ($usrArticleList as $articleInfo)
        {
            $images = $articleInfo->getImages();
            $imageList = [];
            if ($images)
            {
                foreach ($images as $image)
                {
                    $imageList[] = [
                        "name" => $image,
                        "url" => $path . '/' . $articleInfo->getId() . '/' . $image,
                    ];
                }

            }

            $usrUserInfo = $articleInfo->getUsrUserInfo();
            $usrArticleType = $articleInfo->getUsrArticleTypeConfig();
            $dataList[] = [
                "id" => $articleInfo->getId(),
                "account" => $usrUserInfo->getAccount(),
                "type" => $usrArticleType->getName(),
                "title" => $articleInfo->getTitle(),
                "images" => $imageList,
                "updateTime" => $articleInfo->getUpdateTime(),
                "createTime" => $articleInfo->getCreateTime(),
                "content" => $articleInfo->getContent(),
            ];
        }

        $configTypeList = \UsrArticleTypeConfig::find("status = 1");
        $typeList = [];
        foreach ($configTypeList as $articleTypeConfig)
        {
            $typeList[] = [
                "id" => $articleTypeConfig->getId(),
                "name" => $articleTypeConfig->getName(),
            ];
        }

        $msg['code'] = D_COMM_STATUS_CODE::OK;
        $msg['data'] = [
            "dataList" => $dataList,
            "typeList" => $typeList,
            "page" => $page,
            "pageSize" => $pageSize,
            "totalPage" => $totalPage,
            "total" => $count,
        ];

        util::C($msg);
    }

}

