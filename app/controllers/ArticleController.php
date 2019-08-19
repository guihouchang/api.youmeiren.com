<?php
use common\util;
use common\define\D_COMM_STATUS_CODE;
use common\define\D_DEFINE_USER_INFO;
use Phalcon\Mvc\Model\Transaction\Failed as TxFailed;

class ArticleController extends ControllerBase
{
	protected $filterList = [
		"getArticleList"
		];

    public function indexAction()
    {

    }

    /**
     * 添加文章
     * title:
     * content:
     * type:
     */
    public function addArticleInfoAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $title = $this->getPost('title');
        $content = $this->getPost('content');
        $type = $this->getPost('type') ?? 1;
        $usrInfo = $this->userInfo;

        if (!isset($title))
        {
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            $msg['msg'] = '标题为空';
            util::C($msg);
            return ;
        }

        if (!isset($content))
        {
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            $msg['msg'] = '内容为空';
            util::C($msg);
            return ;
        }

        $articleTypeInfo = UsrArticleTypeConfig::findFirst($type);
        if (!$articleTypeInfo)
        {
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            $msg['msg'] = '未找到对应类型';
            util::C($msg);
            return ;
        }

        $transaction = $this->transactions->get();
        try{
            $articleInfo = new UsrArticleInfo();
            $articleInfo->setType($type);
            $articleInfo->setUserId($usrInfo->getId());
            $articleInfo->setTitle($title);
            $articleInfo->setContent($content);
            $articleInfo->setStatus(\common\define\D_DEFINE_COMMON_STATE::NORMAL);
            $articleInfo->setUpdateTime(util::Date(time()));
            $articleInfo->setCreateTime($articleInfo->getUpdateTime());

            if (!$articleInfo->save())
            {
                $transaction->rollback($articleInfo->getErrorMessage());
            }

            if ($this->request->hasFiles())
            {
                $config = $this->config;
                $path = $config->resource->articleDir . '/'. $articleInfo->getId();
                if (!util::mkdirs($path))
                {
                    $transaction->rollback('创建目录失败');
                }

                $files = $this->request->getUploadedFiles();
                $imageList = [];
                $maxCount = 2;
                foreach ($files as $key => $file)
                {
                    $fileName = uniqid() . '.' . $file->getExtension();
                    $file->moveTo($path . '/' . $fileName);
                    $imageList[] = $fileName;

                    if (count($imageList) == 2) break;
                }

                if ($imageList)
                {
                    $articleInfo->setImages($imageList);
                    if (!$articleInfo->save())
                    {
                        $transaction->rollback($articleInfo->getErrorMessage());
                    }
                }
            }

            $transaction->commit();
            $msg['msg'] = '发布成功';
            $msg['code'] = D_COMM_STATUS_CODE::OK;
            util::C($msg);
        }
        catch (TxFailed $e)
        {
            $msg['msg'] = $e->getMessage();
            $msg['code'] = D_COMM_STATUS_CODE::DATABASE_OPERATE_ERROR;
            util::C($msg);
        }
    }

    /**
     * 评论文章
     * id:
     * content:
     */
    public function replyAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $id = $this->getPost('id');
        $content = $this->getPost('content');
        $userInfo = $this->userInfo;

        if (!isset($id)) {
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            $msg['msg'] = '文章id为空';
            util::C($msg);
            return;
        }

        if (!isset($content)) {
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            $msg['msg'] = '评论内容为空';
            util::C($msg);
            return;
        }

        $articleInfo = UsrArticleInfo::findFirst($id);
        if (!$articleInfo)
        {
            $msg['msg'] = '无法找到对应文章';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return;
        }

        $replyInfo = new UsrReplyInfo();
        $replyInfo->setUserId($userInfo->getId());
        $replyInfo->setContent($content);
        $replyInfo->setArticleId($id);
        if (!$replyInfo->save())
        {
            $msg['code'] = D_COMM_STATUS_CODE::DATABASE_OPERATE_ERROR;
            $msg['msg'] = $replyInfo->getErrorMessage();
            util::C($msg);
            return ;
        }

        $msg['code'] = D_COMM_STATUS_CODE::OK;
        $msg['msg'] = '评论成功';
        util::C($msg);
    }

    /**
     * 获取评论列表
     * page:
     * id:
     */
    public function getReplyListAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $page = $this->getPost('page') ?? 0;
        $id = $this->getPost('id');
        $pageSize = 5;
        $start = $page * $pageSize;

        if (!isset($id))
        {
            $msg['msg'] = 'id为空';
            $msg['code'] = D_COMM_STATUS_CODE::REQUEST_PARAMS_ERROR;
            util::C($msg);
            return ;
        }

        $replyList = UsrReplyInfo::find([
            "conditions" => "article_id = :id:",
            "bind" => [
                "id" => $id,
            ],
            "order" => "update_time DESC",
            "offset" => $start,
            "limit" => $pageSize
        ]);

        $config = $this->config;
        $iconPath = $config->resource->iconUrl;

        $dataList = [];
        foreach ($replyList as $usrReplyInfo)
        {
            $userInfo = $usrReplyInfo->getUsrUserInfo();
            $dataList [] = [
                "name" => $userInfo->getName(),
                "icon" => $iconPath . '/' . $userInfo->getIcon(),
                "content" => $usrReplyInfo->getContent(),
                "updateTime" => $usrReplyInfo->getUpdateTime(),
            ];
        }

        $msg['data'] = [
            "dataList" => $dataList,
            "page" => $page,
        ];

        $msg['code'] = D_COMM_STATUS_CODE::OK;
        util::C($msg);
    }

    /**
     * 获取文章列表
     * page:0
     * condition: {"type":[1, 2, 3], "order": "hot|time", "orderType": "DESC|ASC" };
     */
    public function getArticleListAction()
    {
        $msg = util::getMsgHeader(__CLASS__, __METHOD__);
        $page = $this->getPost('page') ?? 0;
        $condition = json_decode($this->getPost('condition'), true) ?? [];
        $pageSize = 10;
        $start = $pageSize * $page;

        $query = UsrArticleInfo::query()->where('status = 1');

        if (key_exists('type', $condition))
        {
            if ($condition['type'] && in_array($condition['type']))
            {
                $query->inWhere('type', $condition['type']);
            }
        }

        if (key_exists('order', $condition))
        {
            $orderType = 'DESC';

            if (key_exists('orderType', $condition))
            {
                if ($condition['orderType'] == 'ASC')
                {
                    $orderType = 'ASC';
                }
            }

            if ($condition['order'] == 'hot')
            {
                $query->orderBy('hot', $orderType);
            }
            else
            {
                $query->orderBy('update_time', $orderType);
            }
        }
        else
        {
            $query->orderBy('update_time DESC');
        }

        $query->limit($pageSize, $start);

        $articleList = $query->execute();
        $config = $this->config;
        $articlePath = $config->resource->articleUrl;
        $iconPath = $config->resource->iconUrl;

        $dataList = [];
        foreach ($articleList as $article)
        {
            $imagesList = $article->getImages();
            $imagesUrlList = [];
            if ($imagesList)
            {
                foreach ($imagesList as $item)
                {
                    $imagesUrlList[] = $articlePath . '/' . $article->getId() . '/' . $item;
                }
            }


            $tmpUserInfo = $article->getUsrUserInfo();
            $icon = $tmpUserInfo->getIcon();

            $dataList[] = [
                "id" => $article->getId(),
                "title" => $article->getTitle(),
                "content" => $article->getContent(),
                "hot" => $article->getHot(),
                "images" => $imagesUrlList,
                "icon" => $iconPath . '/' . $icon,
                "name" => $tmpUserInfo->getName(),
                "replyCount" => $article->countUsrReplyInfo(),
            ];
        }

        $configTypeList = UsrArticleTypeConfig::find("status = 1");
        $typeList = [];
        foreach ($configTypeList as $articleTypeConfig)
        {
            $typeList[] = [
                "id" => $articleTypeConfig->getId(),
                "name" => $articleTypeConfig->getName(),
            ];
        }

        $msg['data'] = [
            "dataList" => $dataList,
            "typeList" => $typeList,
            "page" => $page,
        ];

        $msg['code'] = D_COMM_STATUS_CODE::OK;

        util::C($msg);
    }

}

