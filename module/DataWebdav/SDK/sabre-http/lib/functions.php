<?php

namespace Sabre\HTTP;

use DateTime;




function parseDate($dateString) {

        $month = '(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)';
    $weekday = '(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)';
    $wkday = '(Mon|Tue|Wed|Thu|Fri|Sat|Sun)';
    $time = '([0-1]\d|2[0-3])(\:[0-5]\d){2}';
    $date3 = $month . ' ([12]\d|3[01]| [1-9])';
    $date2 = '(0[1-9]|[12]\d|3[01])\-' . $month . '\-\d{2}';
        $date1 = '(0[1-9]|[12]\d|3[01]) ' . $month . ' [1-9]\d{3}';

            $asctime_date = $wkday . ' ' . $date3 . ' ' . $time . ' [1-9]\d{3}';
        $rfc850_date = $weekday . ', ' . $date2 . ' ' . $time . ' GMT';
        $rfc1123_date = $wkday . ', ' . $date1 . ' ' . $time . ' GMT';
        $HTTP_date = "($rfc1123_date|$rfc850_date|$asctime_date)";

        $dateString = trim($dateString, ' ');
    if (!preg_match('/^' . $HTTP_date . '$/', $dateString))
        return false;

        if (strpos($dateString, ' GMT') === false)
        $dateString .= ' GMT';

    try {
        return new DateTime($dateString, new \DateTimeZone('UTC'));
    } catch (\Exception $e) {
        return false;
    }

}


function toDate(DateTime $dateTime) {

            $dateTime = clone $dateTime;
    $dateTime->setTimezone(new \DateTimeZone('GMT'));
    return $dateTime->format('D, d M Y H:i:s \G\M\T');

}


function negotiateContentType($acceptHeaderValue, array $availableOptions) {

    if (!$acceptHeaderValue) {
                return reset($availableOptions);
    }

    $proposals = array_map(
        'Sabre\HTTP\parseMimeType',
        explode(',', $acceptHeaderValue)
    );

        $availableOptions = array_values($availableOptions);

    $options = array_map(
        'Sabre\HTTP\parseMimeType',
        $availableOptions
    );

    $lastQuality = 0;
    $lastSpecificity = 0;
    $lastOptionIndex = 0;
    $lastChoice = null;

    foreach ($proposals as $proposal) {

                if (is_null($proposal)) continue;

                if ($proposal['quality'] < $lastQuality) {
            continue;
        }

        foreach ($options as $optionIndex => $option) {

            if ($proposal['type'] !== '*' && $proposal['type'] !== $option['type']) {
                                continue;
            }
            if ($proposal['subType'] !== '*' && $proposal['subType'] !== $option['subType']) {
                                continue;
            }

                                    foreach ($option['parameters'] as $paramName => $paramValue) {
                if (!array_key_exists($paramName, $proposal['parameters'])) {
                    continue 2;
                }
                if ($paramValue !== $proposal['parameters'][$paramName]) {
                    continue 2;
                }
            }

                                                $specificity =
                ($proposal['type'] !== '*' ? 20 : 0) +
                ($proposal['subType'] !== '*' ? 10 : 0) +
                count($option['parameters']);


                        if (
                ($proposal['quality'] > $lastQuality) ||
                ($proposal['quality'] === $lastQuality && $specificity > $lastSpecificity) ||
                ($proposal['quality'] === $lastQuality && $specificity === $lastSpecificity && $optionIndex < $lastOptionIndex)
            ) {

                $lastQuality = $proposal['quality'];
                $lastSpecificity = $specificity;
                $lastOptionIndex = $optionIndex;
                $lastChoice = $availableOptions[$optionIndex];

            }

        }

    }

    return $lastChoice;

}


function parsePrefer($input) {

    $token = '[!#$%&\'*+\-.^_`~A-Za-z0-9]+';

        $word = '(?: [a-zA-Z0-9]+ | "[a-zA-Z0-9]*" )';

    $regex = <<<REGEX
/
^
(?<name> $token)      # Prefer property name
\s*                   # Optional space
(?: = \s*             # Prefer property value
   (?<value> $word)
)?
(?: \s* ; (?: .*))?   # Prefer parameters (ignored)
$
/x
REGEX;

    $output = [];
    foreach (getHeaderValues($input) as $value) {

        if (!preg_match($regex, $value, $matches)) {
                        continue;
        }

                switch ($matches['name']) {
            case 'return-asynch' :
                $output['respond-async'] = true;
                break;
            case 'return-representation' :
                $output['return'] = 'representation';
                break;
            case 'return-minimal' :
                $output['return'] = 'minimal';
                break;
            case 'strict' :
                $output['handling'] = 'strict';
                break;
            case 'lenient' :
                $output['handling'] = 'lenient';
                break;
            default :
                if (isset($matches['value'])) {
                    $value = trim($matches['value'], '"');
                } else {
                    $value = true;
                }
                $output[strtolower($matches['name'])] = empty($value) ? true : $value;
                break;
        }

    }

    return $output;

}


function getHeaderValues($values, $values2 = null) {

    $values = (array)$values;
    if ($values2) {
        $values = array_merge($values, (array)$values2);
    }
    foreach ($values as $l1) {
        foreach (explode(',', $l1) as $l2) {
            $result[] = trim($l2);
        }
    }
    return $result;

}


function parseMimeType($str) {

    $parameters = [];
        $quality = 1;

    $parts = explode(';', $str);

        $mimeType = array_shift($parts);

    $mimeType = explode('/', trim($mimeType));
    if (count($mimeType) !== 2) {
                return null;
    }
    list($type, $subType) = $mimeType;

    foreach ($parts as $part) {

        $part = trim($part);
        if (strpos($part, '=')) {
            list($partName, $partValue) =
                explode('=', $part, 2);
        } else {
            $partName = $part;
            $partValue = null;
        }

                                        if ($partName !== 'q') {
            $parameters[$partName] = $part;
        } else {
            $quality = (float)$partValue;
            break;         }

    }

    return [
        'type'       => $type,
        'subType'    => $subType,
        'quality'    => $quality,
        'parameters' => $parameters,
    ];

}


function encodePath($path) {

    return preg_replace_callback('/([^A-Za-z0-9_\-\.~\(\)\/:@])/', function($match) {

        return '%' . sprintf('%02x', ord($match[0]));

    }, $path);

}


function encodePathSegment($pathSegment) {

    return preg_replace_callback('/([^A-Za-z0-9_\-\.~\(\):@])/', function($match) {

        return '%' . sprintf('%02x', ord($match[0]));

    }, $pathSegment);
}


function decodePath($path) {

    return decodePathSegment($path);

}


function decodePathSegment($path) {

    $path = rawurldecode($path);
    $encoding = mb_detect_encoding($path, ['UTF-8', 'ISO-8859-1']);

    switch ($encoding) {

        case 'ISO-8859-1' :
            $path = utf8_encode($path);

    }

    return $path;

}
