<?php


namespace Module\Vendor\Provider\LBS;


use Module\Vendor\Provider\ProviderTrait;

class IpProvider
{
    use ProviderTrait;

    
    public static function all()
    {
        return self::listAll();
    }

    
    public static function get($name)
    {
        return self::getByName($name);
    }
}
