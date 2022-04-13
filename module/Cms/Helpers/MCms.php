<?php

use Module\Cms\Util\CmsCatUtil;
use Module\Cms\Util\CmsContentUtil;
use Module\Cms\Util\CmsMemberPermitUtil;


class MCms
{

    
    public static function getCatByUrl($catUrl)
    {
        return CmsCatUtil::getByUrl($catUrl);
    }

    
    public static function getCat($catId)
    {
        return CmsCatUtil::get($catId);
    }

    
    public static function listChildrenCatByUrl($catUrl)
    {
        $cat = CmsCatUtil::getByUrl($catUrl);
        return self::listChildrenCat($cat['id']);
    }

    
    public static function listChildrenCat($catId)
    {
        return CmsCatUtil::children($catId);
    }

    
    public static function paginateCatByUrl($catUrl, $page = 1, $pageSize = 10, $option = [])
    {
        $cat = CmsCatUtil::getByUrl($catUrl);
        if (empty($cat)) {
            return [];
        }
        $paginateData = CmsContentUtil::paginateCat($cat['id'], $page, $pageSize, $option);
        return $paginateData['records'];
    }

    
    public static function paginateCat($catId, $page = 1, $pageSize = 10, $option = [])
    {
        $paginateData = CmsContentUtil::paginateCat($catId, $page, $pageSize, $option);
        return $paginateData['records'];
    }

    
    public static function latestContentByCatUrl($cateUrl, $limit = 10)
    {
        $cat = self::getCatByUrl($cateUrl);
        return self::latestCat($cat['id'], $limit);
    }

    
    public static function latestContentByCat($catId, $limit = 10)
    {
        $paginateData = CmsContentUtil::paginateCat($catId, 1, $limit);
        $latestRecords = $paginateData['records'];
        return $latestRecords;
    }

    public static function latestCat($catId, $limit = 10)
    {
        return self::latestContentByCat($catId, $limit);
    }

    
    public static function getContentData($record)
    {
        if (empty($record)) {
            return null;
        }
        $cat = self::getCat($record['catId']);
        return CmsContentUtil::getData($cat, $record['id']);
    }

    
    public static function getContentDataField($record, $fieldName, $default = null)
    {
        static $pool = [];
        if (empty($record)) {
            return null;
        }
        if (isset($pool[$record['id']])) {
            $data = $pool[$record['id']];
        } else {
            $data = self::getContentData($record);
            $pool[$record['id']] = $data;
        }
        return isset($data[$fieldName]) ? $data[$fieldName] : $default;
    }

    
    public static function nextOne($catId, $recordId)
    {
        return CmsContentUtil::nextOne($catId, $recordId);
    }

    
    public static function prevOne($catId, $recordId)
    {
        return CmsContentUtil::prevOne($catId, $recordId);
    }


    
    public static function canVisitCat($cat)
    {
        return CmsMemberPermitUtil::canVisitCat($cat);
    }

    
    public static function canPostCat($cat)
    {
        return CmsMemberPermitUtil::canPostCat($cat);
    }


    public static function getCatTreeWithPost()
    {

    }
}
