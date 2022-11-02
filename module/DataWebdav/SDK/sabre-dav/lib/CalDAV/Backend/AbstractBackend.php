<?php

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV;
use Sabre\VObject;


abstract class AbstractBackend implements BackendInterface {

    
    function updateCalendar($calendarId, \Sabre\DAV\PropPatch $propPatch) {

    }

    
    function getMultipleCalendarObjects($calendarId, array $uris) {

        return array_map(function($uri) use ($calendarId) {
            return $this->getCalendarObject($calendarId, $uri);
        }, $uris);

    }

    
    function calendarQuery($calendarId, array $filters) {

        $result = [];
        $objects = $this->getCalendarObjects($calendarId);

        foreach ($objects as $object) {

            if ($this->validateFilterForObject($object, $filters)) {
                $result[] = $object['uri'];
            }

        }

        return $result;

    }

    
    protected function validateFilterForObject(array $object, array $filters) {

                                if (!isset($object['calendardata'])) {
            $object = $this->getCalendarObject($object['calendarid'], $object['uri']);
        }

        $vObject = VObject\Reader::read($object['calendardata']);

        $validator = new CalDAV\CalendarQueryValidator();
        $result = $validator->validate($vObject, $filters);

                $vObject->destroy();

        return $result;

    }

    
    function getCalendarObjectByUID($principalUri, $uid) {

                        foreach ($this->getCalendarsForUser($principalUri) as $calendar) {

                        if ($calendar['principaluri'] !== $principalUri) {
                continue;
            }

                        if (isset($calendar['{http://sabredav.org/ns}owner-principal']) && $calendar['{http://sabredav.org/ns}owner-principal'] !== $principalUri) {
                continue;
            }

            $results = $this->calendarQuery(
                $calendar['id'],
                [
                    'name'         => 'VCALENDAR',
                    'prop-filters' => [],
                    'comp-filters' => [
                        [
                            'name'           => 'VEVENT',
                            'is-not-defined' => false,
                            'time-range'     => null,
                            'comp-filters'   => [],
                            'prop-filters'   => [
                                [
                                    'name'           => 'UID',
                                    'is-not-defined' => false,
                                    'time-range'     => null,
                                    'text-match'     => [
                                        'value'            => $uid,
                                        'negate-condition' => false,
                                        'collation'        => 'i;octet',
                                    ],
                                    'param-filters' => [],
                                ],
                            ]
                        ]
                    ],
                ]
            );
            if ($results) {
                                return $calendar['uri'] . '/' . $results[0];
            }

        }

    }

}
