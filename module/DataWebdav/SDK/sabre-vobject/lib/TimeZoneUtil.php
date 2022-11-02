<?php

namespace Sabre\VObject;


class TimeZoneUtil
{
    public static $map = null;

    
    public static $microsoftExchangeMap = [
        0 => 'UTC',
        31 => 'Africa/Casablanca',

                                2 => 'Europe/Lisbon',
        1 => 'Europe/London',
        4 => 'Europe/Berlin',
        6 => 'Europe/Prague',
        3 => 'Europe/Paris',
        69 => 'Africa/Luanda',         7 => 'Europe/Athens',
        5 => 'Europe/Bucharest',
        49 => 'Africa/Cairo',
        50 => 'Africa/Harare',
        59 => 'Europe/Helsinki',
        27 => 'Asia/Jerusalem',
        26 => 'Asia/Baghdad',
        74 => 'Asia/Kuwait',
        51 => 'Europe/Moscow',
        56 => 'Africa/Nairobi',
        25 => 'Asia/Tehran',
        24 => 'Asia/Muscat',         54 => 'Asia/Baku',
        48 => 'Asia/Kabul',
        58 => 'Asia/Yekaterinburg',
        47 => 'Asia/Karachi',
        23 => 'Asia/Calcutta',
        62 => 'Asia/Kathmandu',
        46 => 'Asia/Almaty',
        71 => 'Asia/Dhaka',
        66 => 'Asia/Colombo',
        61 => 'Asia/Rangoon',
        22 => 'Asia/Bangkok',
        64 => 'Asia/Krasnoyarsk',
        45 => 'Asia/Shanghai',
        63 => 'Asia/Irkutsk',
        21 => 'Asia/Singapore',
        73 => 'Australia/Perth',
        75 => 'Asia/Taipei',
        20 => 'Asia/Tokyo',
        72 => 'Asia/Seoul',
        70 => 'Asia/Yakutsk',
        19 => 'Australia/Adelaide',
        44 => 'Australia/Darwin',
        18 => 'Australia/Brisbane',
        76 => 'Australia/Sydney',
        43 => 'Pacific/Guam',
        42 => 'Australia/Hobart',
        68 => 'Asia/Vladivostok',
        41 => 'Asia/Magadan',
        17 => 'Pacific/Auckland',
        40 => 'Pacific/Fiji',
        67 => 'Pacific/Tongatapu',
        29 => 'Atlantic/Azores',
        53 => 'Atlantic/Cape_Verde',
        30 => 'America/Noronha',
         8 => 'America/Sao_Paulo',         32 => 'America/Argentina/Buenos_Aires',
        60 => 'America/Godthab',
        28 => 'America/St_Johns',
         9 => 'America/Halifax',
        33 => 'America/Caracas',
        65 => 'America/Santiago',
        35 => 'America/Bogota',
        10 => 'America/New_York',
        34 => 'America/Indiana/Indianapolis',
        55 => 'America/Guatemala',
        11 => 'America/Chicago',
        37 => 'America/Mexico_City',
        36 => 'America/Edmonton',
        38 => 'America/Phoenix',
        12 => 'America/Denver',         13 => 'America/Los_Angeles',         14 => 'America/Anchorage',
        15 => 'Pacific/Honolulu',
        16 => 'Pacific/Midway',
        39 => 'Pacific/Kwajalein',
    ];

    
    public static function getTimeZone($tzid, Component $vcalendar = null, $failIfUncertain = false)
    {
                                                                                                if ('(' !== $tzid[0]) {
                                                                                    $tzIdentifiers = \DateTimeZone::listIdentifiers();

            try {
                if (
                    (in_array($tzid, $tzIdentifiers)) ||
                    (preg_match('/^GMT(\+|-)([0-9]{4})$/', $tzid, $matches)) ||
                    (in_array($tzid, self::getIdentifiersBC()))
                ) {
                    return new \DateTimeZone($tzid);
                }
            } catch (\Exception $e) {
            }
        }

        self::loadTzMaps();

                if (isset(self::$map[$tzid])) {
            return new \DateTimeZone(self::$map[$tzid]);
        }

                                if (preg_match('/^\((UTC|GMT)(\+|\-)[\d]{2}\:[\d]{2}\) (.*)/', $tzid, $matches)) {
            $tzidAlternate = $matches[3];
            if (isset(self::$map[$tzidAlternate])) {
                return new \DateTimeZone(self::$map[$tzidAlternate]);
            }
        }

                        if (preg_match('/^GMT(\+|-)([0-9]{4})$/', $tzid, $matches)) {
                                                                                    return new \DateTimeZone('Etc/GMT'.$matches[1].ltrim(substr($matches[2], 0, 2), '0'));
                    }

        if ($vcalendar) {
                        foreach ($vcalendar->select('VTIMEZONE') as $vtimezone) {
                if ((string) $vtimezone->TZID === $tzid) {
                                        if (isset($vtimezone->{'X-LIC-LOCATION'})) {
                        $lic = (string) $vtimezone->{'X-LIC-LOCATION'};

                                                                                                if ('SystemV/' === substr($lic, 0, 8)) {
                            $lic = substr($lic, 8);
                        }

                        return self::getTimeZone($lic, null, $failIfUncertain);
                    }
                                                            if (isset($vtimezone->{'X-MICROSOFT-CDO-TZID'})) {
                        $cdoId = (int) $vtimezone->{'X-MICROSOFT-CDO-TZID'}->getValue();

                                                if (2 === $cdoId && false !== strpos((string) $vtimezone->TZID, 'Sarajevo')) {
                            return new \DateTimeZone('Europe/Sarajevo');
                        }

                        if (isset(self::$microsoftExchangeMap[$cdoId])) {
                            return new \DateTimeZone(self::$microsoftExchangeMap[$cdoId]);
                        }
                    }
                }
            }
        }

        if ($failIfUncertain) {
            throw new \InvalidArgumentException('We were unable to determine the correct PHP timezone for tzid: '.$tzid);
        }

                return new \DateTimeZone(date_default_timezone_get());
    }

    
    public static function loadTzMaps()
    {
        if (!is_null(self::$map)) {
            return;
        }

        self::$map = array_merge(
            include __DIR__.'/timezonedata/windowszones.php',
            include __DIR__.'/timezonedata/lotuszones.php',
            include __DIR__.'/timezonedata/exchangezones.php',
            include __DIR__.'/timezonedata/php-workaround.php'
        );
    }

    
    public static function getIdentifiersBC()
    {
        return include __DIR__.'/timezonedata/php-bc.php';
    }
}
