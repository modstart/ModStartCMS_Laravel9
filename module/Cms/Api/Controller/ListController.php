<?php


namespace Module\Cms\Api\Controller;


use ModStart\Core\Exception\BizException;
use ModStart\Core\Input\InputPackage;
use ModStart\Core\Input\Response;
use Module\Cms\Util\CmsContentUtil;


class ListController extends BaseCatController
{
    
    public function index()
    {
        $input = InputPackage::buildFromInput();
        $catId = $input->getTrimString('cat');
        BizException::throwsIfEmpty('分类为空', $catId);
        $data = parent::setup($catId);
        $cat = $data['cat'];
        $page = $input->getPage();
        $pageSize = $input->getPageSize('pageSize');
        $searchInput = $input->getAsInput('search');
        $option = [
            'where' => [],
        ];
        $isRecommend = $searchInput->getBoolean('isRecommend');
        if ($isRecommend) {
            $option['where']['isRecommend'] = true;
        }
        $paginateData = CmsContentUtil::paginateCat($cat['id'], $page, $pageSize, $option);
        $viewData = [];
        $viewData['total'] = $paginateData['total'];
        $viewData['cat'] = $cat;
        $viewData['page'] = $page;
        $viewData['pageSize'] = $pageSize;
        $viewData['records'] = $paginateData['records'];
        return Response::generateSuccessData($viewData);
    }
}
