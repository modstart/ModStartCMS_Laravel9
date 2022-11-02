<?php

namespace Sabre\VObject;


class UUIDUtil
{
    
    public static function getUUID()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),

                        mt_rand(0, 0xffff),

                                    mt_rand(0, 0x0fff) | 0x4000,

                                                mt_rand(0, 0x3fff) | 0x8000,

                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    
    public static function validateUUID($uuid)
    {
        return 0 !== preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i',
            $uuid
        );
    }
}
