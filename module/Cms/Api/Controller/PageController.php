<?php


namespace Module\Cms\Api\Controller;


use ModStart\Core\Input\InputPackage;
use ModStart\Core\Input\Response;
use Module\Cms\Util\CmsContentUtil;


class PageController extends BaseCatController
{
    
    public function index()
    {
        $input = InputPackage::buildFromInput();
        $catId = $input->getTrimString('cat');
        $data = parent::setup($catId);
        $view = $this->getView($data, 'pageTemplate');
        $cat = $data['cat'];
        $viewData = $data;
        $records = CmsContentUtil::allCat($cat['id']);
        $viewData['record'] = isset($records[0]) ? $records[0] : null;
        $viewData['records'] = $records;
        $viewData['view'] = $view;
        return Response::generateSuccessData($viewData);
    }
}