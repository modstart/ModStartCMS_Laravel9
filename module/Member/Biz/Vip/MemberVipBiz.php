<?php


namespace Module\Member\Biz\Vip;


use Module\Vendor\Provider\BizTrait;

class MemberVipBiz
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
