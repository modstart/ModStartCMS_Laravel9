<?php

namespace Sabre\DAV;


class StringUtil {

    
    static function textMatch($haystack, $needle, $collation, $matchType = 'contains') {

        switch ($collation) {

            case 'i;ascii-casemap' :
                                                $haystack = str_replace(range('a', 'z'), range('A', 'Z'), $haystack);
                $needle = str_replace(range('a', 'z'), range('A', 'Z'), $needle);
                break;

            case 'i;octet' :
                                break;

            case 'i;unicode-casemap' :
                $haystack = mb_strtoupper($haystack, 'UTF-8');
                $needle = mb_strtoupper($needle, 'UTF-8');
                break;

            default :
                throw new Exception\BadRequest('Collation type: ' . $collation . ' is not supported');

        }

        switch ($matchType) {

            case 'contains' :
                return strpos($haystack, $needle) !== false;
            case 'equals' :
                return $haystack === $needle;
            case 'starts-with' :
                return strpos($haystack, $needle) === 0;
            case 'ends-with' :
                return strrpos($haystack, $needle) === strlen($haystack) - strlen($needle);
            default :
                throw new Exception\BadRequest('Match-type: ' . $matchType . ' is not supported');

        }

    }

    
    static function ensureUTF8($input) {

        $encoding = mb_detect_encoding($input, ['UTF-8', 'ISO-8859-1'], true);

        if ($encoding === 'ISO-8859-1') {
            return utf8_encode($input);
        } else {
            return $input;
        }

    }

}
