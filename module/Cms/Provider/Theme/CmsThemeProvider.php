<?php


namespace Module\Cms\Provider\Theme;

use Module\Vendor\Provider\ProviderTrait;

class CmsThemeProvider
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