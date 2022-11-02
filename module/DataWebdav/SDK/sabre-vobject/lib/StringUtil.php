<?php

namespace Sabre\VObject;


class StringUtil
{
    
    public static function isUTF8($str)
    {
                if (preg_match('%[\x00-\x08\x0B-\x0C\x0E\x0F]%', $str)) {
            return false;
        }

        return (bool) preg_match('%%u', $str);
    }

    
    public static function convertToUTF8($str)
    {
        $encoding = mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'WINDOWS-1252'], true);

        switch ($encoding) {
            case 'ISO-8859-1':
                $newStr = utf8_encode($str);
                break;
            
            default:
                 $newStr = $str;
        }

                return preg_replace('%(?:[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F])%', '', $newStr);
    }
}
