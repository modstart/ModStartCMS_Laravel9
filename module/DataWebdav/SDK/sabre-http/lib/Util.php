<?php

namespace Sabre\HTTP;


class Util {

    
    static function negotiateContentType($acceptHeaderValue, array $availableOptions) {

        return negotiateContentType($acceptHeaderValue, $availableOptions);

    }

    
    static function negotiate($acceptHeaderValue, array $availableOptions) {

        return negotiateContentType($acceptHeaderValue, $availableOptions);

    }

    
    static function parseHTTPDate($dateHeader) {

        return parseDate($dateHeader);

    }

    
    static function toHTTPDate(\DateTime $dateTime) {

        return toDate($dateTime);

    }
}
