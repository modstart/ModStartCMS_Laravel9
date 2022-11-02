<?php

namespace Sabre\HTTP;

use Sabre\URI;


class URLUtil {

    
    static function encodePath($path) {

        return encodePath($path);

    }

    
    static function encodePathSegment($pathSegment) {

        return encodePathSegment($pathSegment);

    }

    
    static function decodePath($path) {

        return decodePath($path);

    }

    
    static function decodePathSegment($path) {

        return decodePathSegment($path);

    }

    
    static function splitPath($path) {

        return Uri\split($path);

    }

    
    static function resolve($basePath, $newPath) {

        return Uri\resolve($basePath, $newPath);

    }

}
