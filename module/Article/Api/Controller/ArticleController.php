<?php


namespace Module\Article\Api\Controller;

use ModStart\Core\Input\InputPackage;
use ModStart\Core\Input\Response;
use ModStart\Module\ModuleBaseController;
use Module\Article\Util\ArticleUtil;


class ArticleController extends ModuleBaseController
{
    
    public function get()
    {
        $input = InputPackage::buildFromInput();
        $id = $input->getTrimString('id');
        if (is_numeric($id)) {
            $article = ArticleUtil::get($id);
        } else {
            $article = ArticleUtil::getByAlias($id);
        }
        return Response::generateSuccessData([
            'article' => $article,
        ]);
    }
}
