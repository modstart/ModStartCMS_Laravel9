<?php


namespace Module\Article\Biz;


use Module\Vendor\Provider\BizTrait;

class ArticlePositionBiz
{
    use BizTrait;

    
    public static function all()
    {
        return self::listAll();
    }

    
    public static function get($name)
    {
        return self::getByName($name);
    }
}
