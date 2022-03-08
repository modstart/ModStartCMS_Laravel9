<?php

use Module\Cms\Util\CmsCatUtil;
use Module\Cms\Util\CmsContentUtil;
use Module\Member\Auth\MemberUser;


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

    
    public static function nextOne($catId, $recordId)
    {
        return CmsContentUtil::nextOne($catId, $recordId);
    }

    
    public static function prevOne($catId, $recordId)
    {
        return CmsContentUtil::prevOne($catId, $recordId);
    }

    
    public static function canAccessCatContent($cat)
    {
        if ($cat['visitMemberGroupEnable']) {
            if (!MemberUser::isGroup($cat['visitMemberGroups'])) {
                return false;
            }
        }
        if ($cat['visitMemberVipEnable']) {
            if (!MemberUser::isVip($cat['visitMemberVips'])) {
                return false;
            }
        }
        return true;
    }

    
    public static function canPostCat($cat)
    {
        if (!$cat['memberUserPostEnable']) {
            return false;
        }
        if ($cat['postMemberGroupEnable']) {
            if (!MemberUser::isGroup($cat['postMemberGroups'])) {
                return false;
            }
        }
        if ($cat['postMemberVipEnable']) {
            if (!MemberUser::isVip($cat['postMemberVips'])) {
                return false;
            }
        }
        return true;
    }


    public static function getCatTreeWithPost()
    {

    }
}
