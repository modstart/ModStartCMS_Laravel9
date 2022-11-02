<?php

namespace Sabre\VObject;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;


class DateTimeParser
{
    
    public static function parseDateTime($dt, DateTimeZone $tz = null)
    {
                $result = preg_match('/^([0-9]{4})([0-1][0-9])([0-3][0-9])T([0-2][0-9])([0-5][0-9])([0-5][0-9])([Z]?)$/', $dt, $matches);

        if (!$result) {
            throw new InvalidDataException('The supplied iCalendar datetime value is incorrect: '.$dt);
        }

        if ('Z' === $matches[7] || is_null($tz)) {
            $tz = new DateTimeZone('UTC');
        }

        try {
            $date = new DateTimeImmutable($matches[1].'-'.$matches[2].'-'.$matches[3].' '.$matches[4].':'.$matches[5].':'.$matches[6], $tz);
        } catch (\Exception $e) {
            throw new InvalidDataException('The supplied iCalendar datetime value is incorrect: '.$dt);
        }

        return $date;
    }

    
    public static function parseDate($date, DateTimeZone $tz = null)
    {
                $result = preg_match('/^([0-9]{4})([0-1][0-9])([0-3][0-9])$/', $date, $matches);

        if (!$result) {
            throw new InvalidDataException('The supplied iCalendar date value is incorrect: '.$date);
        }

        if (is_null($tz)) {
            $tz = new DateTimeZone('UTC');
        }

        try {
            $date = new DateTimeImmutable($matches[1].'-'.$matches[2].'-'.$matches[3], $tz);
        } catch (\Exception $e) {
            throw new InvalidDataException('The supplied iCalendar date value is incorrect: '.$date);
        }

        return $date;
    }

    
    public static function parseDuration($duration, $asString = false)
    {
        $result = preg_match('/^(?<plusminus>\+|-)?P((?<week>\d+)W)?((?<day>\d+)D)?(T((?<hour>\d+)H)?((?<minute>\d+)M)?((?<second>\d+)S)?)?$/', $duration, $matches);
        if (!$result) {
            throw new InvalidDataException('The supplied iCalendar duration value is incorrect: '.$duration);
        }

        if (!$asString) {
            $invert = false;

            if ('-' === $matches['plusminus']) {
                $invert = true;
            }

            $parts = [
                'week',
                'day',
                'hour',
                'minute',
                'second',
            ];

            foreach ($parts as $part) {
                $matches[$part] = isset($matches[$part]) && $matches[$part] ? (int) $matches[$part] : 0;
            }

                                    $duration = 'P';
            $days = $matches['day'];

            if ($matches['week']) {
                $days += $matches['week'] * 7;
            }

            if ($days) {
                $duration .= $days.'D';
            }

            if ($matches['minute'] || $matches['second'] || $matches['hour']) {
                $duration .= 'T';

                if ($matches['hour']) {
                    $duration .= $matches['hour'].'H';
                }

                if ($matches['minute']) {
                    $duration .= $matches['minute'].'M';
                }

                if ($matches['second']) {
                    $duration .= $matches['second'].'S';
                }
            }

            if ('P' === $duration) {
                $duration = 'PT0S';
            }

            $iv = new DateInterval($duration);

            if ($invert) {
                $iv->invert = true;
            }

            return $iv;
        }

        $parts = [
            'week',
            'day',
            'hour',
            'minute',
            'second',
        ];

        $newDur = '';

        foreach ($parts as $part) {
            if (isset($matches[$part]) && $matches[$part]) {
                $newDur .= ' '.$matches[$part].' '.$part.'s';
            }
        }

        $newDur = ('-' === $matches['plusminus'] ? '-' : '+').trim($newDur);

        if ('+' === $newDur) {
            $newDur = '+0 seconds';
        }

        return $newDur;
    }

    
    public static function parse($date, $referenceTz = null)
    {
        if ('P' === $date[0] || ('-' === $date[0] && 'P' === $date[1])) {
            return self::parseDuration($date);
        } elseif (8 === strlen($date)) {
            return self::parseDate($date, $referenceTz);
        } else {
            return self::parseDateTime($date, $referenceTz);
        }
    }

    
    public static function parseVCardDateTime($date)
    {
        $regex = '/^
            (?:  # date part
                (?:
                    (?: (?<year> [0-9]{4}) (?: -)?| --)
                    (?<month> [0-9]{2})?
                |---)
                (?<date> [0-9]{2})?
            )?
            (?:T  # time part
                (?<hour> [0-9]{2} | -)
                (?<minute> [0-9]{2} | -)?
                (?<second> [0-9]{2})?

                (?: \.[0-9]{3})? # milliseconds
                (?P<timezone> # timezone offset

                    Z | (?: \+|-)(?: [0-9]{4})

                )?

            )?
            $/x';

        if (!preg_match($regex, $date, $matches)) {
                        $regex = '/^
                (?: # date part
                    (?: (?<year> [0-9]{4}) - | -- )
                    (?<month> [0-9]{2}) -
                    (?<date> [0-9]{2})
                )?
                (?:T # time part

                    (?: (?<hour> [0-9]{2}) : | -)
                    (?: (?<minute> [0-9]{2}) : | -)?
                    (?<second> [0-9]{2})?

                    (?: \.[0-9]{3})? # milliseconds
                    (?P<timezone> # timezone offset

                        Z | (?: \+|-)(?: [0-9]{2}:[0-9]{2})

                    )?

                )?
                $/x';

            if (!preg_match($regex, $date, $matches)) {
                throw new InvalidDataException('Invalid vCard date-time string: '.$date);
            }
        }
        $parts = [
            'year',
            'month',
            'date',
            'hour',
            'minute',
            'second',
            'timezone',
        ];

        $result = [];
        foreach ($parts as $part) {
            if (empty($matches[$part])) {
                $result[$part] = null;
            } elseif ('-' === $matches[$part] || '--' === $matches[$part]) {
                $result[$part] = null;
            } else {
                $result[$part] = $matches[$part];
            }
        }

        return $result;
    }

    
    public static function parseVCardTime($date)
    {
        $regex = '/^
            (?<hour> [0-9]{2} | -)
            (?<minute> [0-9]{2} | -)?
            (?<second> [0-9]{2})?

            (?: \.[0-9]{3})? # milliseconds
            (?P<timezone> # timezone offset

                Z | (?: \+|-)(?: [0-9]{4})

            )?
            $/x';

        if (!preg_match($regex, $date, $matches)) {
                        $regex = '/^
                (?: (?<hour> [0-9]{2}) : | -)
                (?: (?<minute> [0-9]{2}) : | -)?
                (?<second> [0-9]{2})?

                (?: \.[0-9]{3})? # milliseconds
                (?P<timezone> # timezone offset

                    Z | (?: \+|-)(?: [0-9]{2}:[0-9]{2})

                )?
                $/x';

            if (!preg_match($regex, $date, $matches)) {
                throw new InvalidDataException('Invalid vCard time string: '.$date);
            }
        }
        $parts = [
            'hour',
            'minute',
            'second',
            'timezone',
        ];

        $result = [];
        foreach ($parts as $part) {
            if (empty($matches[$part])) {
                $result[$part] = null;
            } elseif ('-' === $matches[$part]) {
                $result[$part] = null;
            } else {
                $result[$part] = $matches[$part];
            }
        }

        return $result;
    }

    
    public static function parseVCardDateAndOrTime($date)
    {
                $valueDate = '/^(?J)(?:'.
                         '(?<year>\d{4})(?<month>\d\d)(?<date>\d\d)'.
                         '|(?<year>\d{4})-(?<month>\d\d)'.
                         '|--(?<month>\d\d)(?<date>\d\d)?'.
                         '|---(?<date>\d\d)'.
                         ')$/';

                $valueTime = '/^(?J)(?:'.
                         '((?<hour>\d\d)((?<minute>\d\d)(?<second>\d\d)?)?'.
                         '|-(?<minute>\d\d)(?<second>\d\d)?'.
                         '|--(?<second>\d\d))'.
                         '(?<timezone>(Z|[+\-]\d\d(\d\d)?))?'.
                         ')$/';

                $valueDateTime = '/^(?:'.
                         '((?<year0>\d{4})(?<month0>\d\d)(?<date0>\d\d)'.
                         '|--(?<month1>\d\d)(?<date1>\d\d)'.
                         '|---(?<date2>\d\d))'.
                         'T'.
                         '(?<hour>\d\d)((?<minute>\d\d)(?<second>\d\d)?)?'.
                         '(?<timezone>(Z|[+\-]\d\d(\d\d?)))?'.
                         ')$/';

                
        if (0 === preg_match($valueDate, $date, $matches)
            && 0 === preg_match($valueDateTime, $date, $matches)
            && 0 === preg_match($valueTime, $date, $matches)) {
            throw new InvalidDataException('Invalid vCard date-time string: '.$date);
        }

        $parts = [
            'year' => null,
            'month' => null,
            'date' => null,
            'hour' => null,
            'minute' => null,
            'second' => null,
            'timezone' => null,
        ];

                $parts['date0'] = &$parts['date'];
        $parts['date1'] = &$parts['date'];
        $parts['date2'] = &$parts['date'];
        $parts['month0'] = &$parts['month'];
        $parts['month1'] = &$parts['month'];
        $parts['year0'] = &$parts['year'];

        foreach ($parts as $part => &$value) {
            if (!empty($matches[$part])) {
                $value = $matches[$part];
            }
        }

        unset($parts['date0']);
        unset($parts['date1']);
        unset($parts['date2']);
        unset($parts['month0']);
        unset($parts['month1']);
        unset($parts['year0']);

        return $parts;
    }
}
